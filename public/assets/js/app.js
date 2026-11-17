/* Peixoto Admin — JS (vanilla) */
(function () {
    'use strict';

    // ---- Sidebar mobile ----
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var toggle  = document.getElementById('menuToggle');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', closeSidebar);
    }

    // ---- Confirmação em formulários destrutivos ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // ---- Auto-hide de alertas ----
    document.querySelectorAll('[data-autohide]').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity .4s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 400);
        }, 5000);
    });

    // ---- Cálculo automático do total da OS ----
    var calcInputs = document.querySelectorAll('input[data-calc]');
    var totalPreview = document.getElementById('totalPreview');

    function formatBRL(value) {
        return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function updateTotal() {
        var total = 0;
        calcInputs.forEach(function (input) {
            total += parseFloat(input.value) || 0;
        });
        if (totalPreview) totalPreview.textContent = formatBRL(total);
    }

    if (calcInputs.length && totalPreview) {
        calcInputs.forEach(function (input) {
            input.addEventListener('input', updateTotal);
        });
        updateTotal();
    }

    // ---- Filtro de equipamentos pelo cliente selecionado (form de OS) ----
    var clientSelect = document.getElementById('client_id');
    var deviceSelect = document.getElementById('device_id');

    if (clientSelect && deviceSelect && deviceSelect.querySelector('option[data-client]')) {
        var allOptions = Array.prototype.slice.call(deviceSelect.options);

        function filterDevices() {
            var clientId = clientSelect.value;
            var selected = deviceSelect.value;

            allOptions.forEach(function (option) {
                if (!option.value) return; // placeholder
                var match = !clientId || option.getAttribute('data-client') === clientId;
                option.hidden = !match;
                if (!match && option.value === selected) {
                    deviceSelect.value = '';
                }
            });
        }

        clientSelect.addEventListener('change', filterDevices);
        filterDevices();
    }
})();

/* Peixoto OS — Tasks LifeOS (drag & drop) */
(function () {
    'use strict';

    var board = document.getElementById('lifeTaskBoard');
    if (!board) return;

    var csrf = board.getAttribute('data-csrf');
    var dragging = null;
    var justDragged = false;

    function afterElement(container, y) {
        var cards = Array.prototype.slice.call(container.querySelectorAll('.task-card:not(.dragging)'));
        var closest = { offset: -Infinity, el: null };
        cards.forEach(function (card) {
            var box = card.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                closest = { offset: offset, el: card };
            }
        });
        return closest.el;
    }

    function persist(container) {
        if (!dragging) return;
        var status = container.getAttribute('data-status');
        var ids = Array.prototype.slice.call(container.querySelectorAll('.task-card'))
            .map(function (card) { return card.getAttribute('data-task'); });

        var body = new URLSearchParams();
        body.append('_csrf', csrf);
        body.append('task_id', dragging.getAttribute('data-task'));
        body.append('status', status);
        ids.forEach(function (id) { body.append('order[]', id); });

        fetch('/tasks/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
    }

    function updateCounts() {
        board.querySelectorAll('.task-column').forEach(function (column) {
            var count = column.querySelectorAll('.task-card').length;
            var pill = column.querySelector('.bcol-count');
            if (pill) pill.textContent = count;
        });
    }

    board.addEventListener('click', function (event) {
        if (justDragged && event.target.closest('.task-card')) {
            event.preventDefault();
        }
    });

    board.addEventListener('dragstart', function (event) {
        var card = event.target.closest('.task-card');
        if (!card) return;
        dragging = card;
        setTimeout(function () { card.classList.add('dragging'); }, 0);
    });

    board.addEventListener('dragend', function () {
        if (dragging) {
            dragging.classList.remove('dragging');
            var container = dragging.closest('.task-dropzone');
            if (container) {
                persist(container);
                updateCounts();
            }
        }
        dragging = null;
        justDragged = true;
        setTimeout(function () { justDragged = false; }, 120);
        board.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); });
    });

    board.addEventListener('dragover', function (event) {
        if (!dragging) return;
        event.preventDefault();
        var rect = board.getBoundingClientRect();
        var edge = 70;
        if (event.clientX < rect.left + edge) {
            board.scrollLeft -= 14;
        } else if (event.clientX > rect.right - edge) {
            board.scrollLeft += 14;
        }
    });

    board.querySelectorAll('.task-dropzone').forEach(function (container) {
        container.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!dragging) return;
            container.classList.add('drag-over');
            var ref = afterElement(container, event.clientY);
            if (ref == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, ref);
            }
        });

        container.addEventListener('dragleave', function (event) {
            if (!container.contains(event.relatedTarget)) {
                container.classList.remove('drag-over');
            }
        });

        container.addEventListener('drop', function (event) {
            event.preventDefault();
            container.classList.remove('drag-over');
        });
    });
})();

/* Peixoto Admin — Trello kanban (drag & drop, composers e modal) */
(function () {
    'use strict';

    var board = document.getElementById('kanban');
    if (!board) return;

    var csrf = board.getAttribute('data-csrf');
    var dragging = null;
    var justDragged = false;

    // ---------- Composers (adicionar cartão / lista) ----------
    function setupComposer(wrap) {
        var btn  = wrap.querySelector('.bcol-add-btn, .bcol-new-btn');
        var form = wrap.querySelector('.bcol-add-form');
        if (!btn || !form) return;

        var field  = form.querySelector('textarea, input[type="text"]');
        var cancel = form.querySelector('.bcol-add-cancel');

        function open() {
            btn.hidden = true;
            form.hidden = false;
            field.focus();
        }
        function close() {
            form.hidden = true;
            btn.hidden = false;
            field.value = '';
        }

        btn.addEventListener('click', open);
        cancel.addEventListener('click', close);
        field.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && field.tagName === 'TEXTAREA') {
                e.preventDefault();
                if (field.value.trim()) form.submit();
            }
            if (e.key === 'Escape') close();
        });
    }

    board.querySelectorAll('.bcol-add').forEach(setupComposer);
    var newList = board.querySelector('.bcol-new');
    if (newList) setupComposer(newList);

    // ---------- Modal de detalhe do cartão ----------
    var overlay = document.getElementById('cardModal');
    var panel   = document.getElementById('cardModalPanel');

    function openModal(cardId) {
        fetch('/trello/card_modal?id=' + encodeURIComponent(cardId))
            .then(function (r) {
                if (!r.ok) throw new Error('http ' + r.status);
                return r.text();
            })
            .then(function (html) {
                panel.innerHTML = html;
                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
            })
            .catch(function () {
                // fallback: abre a página completa do cartão
                window.location.href = '/trello/card?id=' + cardId;
            });
    }

    function closeModal() {
        overlay.hidden = true;
        panel.innerHTML = '';
        document.body.style.overflow = '';
    }

    if (overlay && panel) {
        board.addEventListener('click', function (e) {
            var card = e.target.closest('.bcard');
            if (!card) return;
            e.preventDefault();
            if (justDragged) return;
            openModal(card.getAttribute('data-card'));
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
            if (e.target.closest('[data-close]')) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.hidden) closeModal();
        });

        // Confirmação para forms destrutivos injetados no modal.
        panel.addEventListener('submit', function (e) {
            var form = e.target.closest('form[data-confirm]');
            if (form && !window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    }

    // ---------- Drag & drop ----------
    function afterElement(container, y) {
        var cards = Array.prototype.slice.call(
            container.querySelectorAll('.bcard:not(.dragging)')
        );
        var closest = { offset: -Infinity, el: null };
        cards.forEach(function (card) {
            var box = card.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                closest = { offset: offset, el: card };
            }
        });
        return closest.el;
    }

    function persist(container) {
        var columnId = container.getAttribute('data-column');
        var ids = Array.prototype.slice
            .call(container.querySelectorAll('.bcard'))
            .map(function (c) { return c.getAttribute('data-card'); });

        var body = new URLSearchParams();
        body.append('_csrf', csrf);
        body.append('card_id', dragging ? dragging.getAttribute('data-card') : '');
        body.append('column_id', columnId);
        ids.forEach(function (id) { body.append('order[]', id); });

        fetch('/trello/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
    }

    function updateCounts() {
        board.querySelectorAll('.bcol[data-column]').forEach(function (col) {
            var count = col.querySelectorAll('.bcard').length;
            var pill = col.querySelector('.bcol-count');
            if (pill) pill.textContent = count;
        });
    }

    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.bcard');
        if (!card) return;
        dragging = card;
        setTimeout(function () { card.classList.add('dragging'); }, 0);
    });

    board.addEventListener('dragend', function () {
        if (dragging) {
            dragging.classList.remove('dragging');
            // Persiste onde o card terminou, mesmo se o drop caiu fora
            // da área de cards (header, composer…) — comum no touch.
            var container = dragging.closest('.bcol-cards');
            if (container) {
                persist(container);
                updateCounts();
            }
        }
        dragging = null;
        justDragged = true;
        setTimeout(function () { justDragged = false; }, 80);
        board.querySelectorAll('.drag-over').forEach(function (el) {
            el.classList.remove('drag-over');
        });
    });

    // Auto-scroll horizontal do quadro ao arrastar perto das bordas
    // (Trello faz isso; essencial no iPad e em telas menores).
    board.addEventListener('dragover', function (e) {
        if (!dragging) return;
        e.preventDefault();
        var rect = board.getBoundingClientRect();
        var edge = 70, speed = 16;
        if (e.clientX < rect.left + edge) {
            board.scrollLeft -= speed;
        } else if (e.clientX > rect.right - edge) {
            board.scrollLeft += speed;
        }
    });

    board.querySelectorAll('.bcol-cards').forEach(function (container) {
        container.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragging) return;
            container.classList.add('drag-over');

            // Auto-scroll vertical dentro da lista
            var crect = container.getBoundingClientRect();
            if (e.clientY < crect.top + 48) {
                container.scrollTop -= 12;
            } else if (e.clientY > crect.bottom - 48) {
                container.scrollTop += 12;
            }

            var ref = afterElement(container, e.clientY);
            if (ref == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, ref);
            }
        });

        container.addEventListener('dragleave', function (e) {
            if (!container.contains(e.relatedTarget)) {
                container.classList.remove('drag-over');
            }
        });

        container.addEventListener('drop', function (e) {
            // A persistência acontece no dragend (cobre qualquer alvo de drop).
            e.preventDefault();
            container.classList.remove('drag-over');
        });
    });
})();
