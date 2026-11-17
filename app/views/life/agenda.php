<?php
$today = date('Y-m-d');
$todayTs = time();
$isCurrentMonth = $monthParam === date('Y-m');
$eventTime = static function (array $event): string {
    $ts = strtotime((string) $event['starts_at']);
    return date('H:i', $ts) === '00:00' ? '' : date('H:i', $ts);
};
$ucfirst = static fn (string $text): string => mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
?>

<section class="module-head">
    <div>
        <p class="eyebrow"><?= e(life_type_label($config['type'])) ?></p>
        <h2><?= e($config['title']) ?></h2>
        <p><?= e($config['description']) ?></p>
    </div>
    <a href="/agenda/create" class="btn btn-primary">Novo evento</a>
</section>

<div class="agenda-stats">
    <div class="stat-card agenda-today-card">
        <span class="stat-label">Hoje</span>
        <div class="stat-value agenda-weekday"><?= e($ucfirst(br_weekday_name((int) date('w', $todayTs)))) ?></div>
        <span class="stat-hint"><?= (int) date('j', $todayTs) ?> de <?= e(br_month_name((int) date('n', $todayTs))) ?> de <?= date('Y', $todayTs) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Fim do mês</span>
        <div class="stat-value stat-accent"><?= (int) $daysLeftMonth ?></div>
        <span class="stat-hint"><?= $daysLeftMonth === 1 ? 'dia restante' : 'dias restantes' ?> em <?= e(br_month_name((int) date('n', $todayTs))) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Fim do ano</span>
        <div class="stat-value stat-warn"><?= (int) $daysLeftYear ?></div>
        <span class="stat-hint"><?= $daysLeftYear === 1 ? 'dia restante' : 'dias restantes' ?> em <?= date('Y', $todayTs) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Eventos no mês</span>
        <div class="stat-value stat-ok"><?= (int) $monthEventCount ?></div>
        <span class="stat-hint"><?= e($ucfirst(br_month_name($month))) ?> de <?= $year ?></span>
    </div>
</div>

<section class="card calendar-card">
    <div class="calendar-head">
        <h2 class="calendar-title"><?= e($ucfirst(br_month_name($month))) ?> <span><?= $year ?></span></h2>
        <div class="calendar-nav">
            <a href="/agenda?m=<?= e($prevMonth) ?>" class="btn btn-ghost btn-sm" aria-label="Mês anterior">&larr;</a>
            <?php if (!$isCurrentMonth): ?>
                <a href="/agenda" class="btn btn-ghost btn-sm">Hoje</a>
            <?php endif; ?>
            <a href="/agenda?m=<?= e($nextMonth) ?>" class="btn btn-ghost btn-sm" aria-label="Próximo mês">&rarr;</a>
        </div>
    </div>

    <div class="calendar-grid calendar-dow-row">
        <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dow): ?>
            <div class="calendar-dow"><?= e($dow) ?></div>
        <?php endforeach; ?>
    </div>

    <div class="calendar-grid calendar-days">
        <?php for ($ts = $gridStartTs; $ts <= $gridEndTs; $ts = strtotime('+1 day', $ts)): ?>
            <?php
            $dayKey = date('Y-m-d', $ts);
            $inMonth = (int) date('n', $ts) === $month;
            $isToday = $dayKey === $today;
            $dayEvents = $eventsByDay[$dayKey] ?? [];
            $classes = 'calendar-day' . ($inMonth ? '' : ' is-outside') . ($isToday ? ' is-today' : '');
            ?>
            <div class="<?= $classes ?>">
                <div class="calendar-day-top">
                    <span class="calendar-day-num"><?= (int) date('j', $ts) ?></span>
                    <a href="/agenda/create?date=<?= e($dayKey) ?>" class="calendar-day-add" title="Novo evento em <?= br_date($dayKey) ?>">+</a>
                </div>
                <?php if ($isToday): ?>
                    <span class="calendar-today-tag">Hoje</span>
                <?php endif; ?>
                <div class="calendar-day-events">
                    <?php foreach (array_slice($dayEvents, 0, 3) as $event): ?>
                        <a href="/agenda/view?id=<?= (int) $event['id'] ?>"
                           class="calendar-event calendar-event-<?= e((string) ($event['status'] ?: 'scheduled')) ?>"
                           title="<?= e($event['title']) ?>">
                            <?php if ($eventTime($event)): ?><strong><?= $eventTime($event) ?></strong><?php endif; ?>
                            <span><?= e($event['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($dayEvents) > 3): ?>
                        <span class="calendar-more">+<?= count($dayEvents) - 3 ?> mais</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</section>

<div class="page-actions">
    <form method="get" action="/agenda" class="search-form">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar em agenda...">
        <select name="status">
            <option value="">Todos os status</option>
            <?php foreach (life_event_statuses() as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
</div>

<?php if ($searchResults !== null): ?>
    <section class="card">
        <div class="card-header">
            <h2>Resultados da busca</h2>
            <a href="/agenda" class="btn btn-ghost btn-sm">Limpar</a>
        </div>
        <?php if (!$searchResults): ?>
            <p class="empty">Nenhum evento encontrado.</p>
        <?php else: ?>
            <div class="agenda-list">
                <?php foreach ($searchResults as $event): ?>
                    <a href="/agenda/view?id=<?= (int) $event['id'] ?>" class="agenda-list-item">
                        <div class="agenda-list-date">
                            <strong><?= $event['starts_at'] ? (int) date('j', strtotime((string) $event['starts_at'])) : '—' ?></strong>
                            <span><?= $event['starts_at'] ? e(mb_substr(br_month_name((int) date('n', strtotime((string) $event['starts_at']))), 0, 3)) : '' ?></span>
                        </div>
                        <div class="agenda-list-info">
                            <strong><?= e($event['title']) ?></strong>
                            <span><?= br_date($event['starts_at'], true) ?><?= $event['summary'] ? ' · ' . e($event['summary']) : '' ?></span>
                        </div>
                        <?= life_status_badge($event['status'], 'agenda_event') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <h2>Próximos eventos</h2>
    </div>
    <?php if (!$upcoming): ?>
        <p class="empty">Nenhum evento futuro. Clique em um dia do calendário para criar.</p>
    <?php else: ?>
        <div class="agenda-list">
            <?php foreach ($upcoming as $event): ?>
                <?php
                $ts = strtotime((string) $event['starts_at']);
                $meta = life_metadata($event);
                $dayLabel = date('Y-m-d', $ts) === $today
                    ? 'Hoje'
                    : (date('Y-m-d', $ts) === date('Y-m-d', strtotime('+1 day')) ? 'Amanhã' : $ucfirst(br_weekday_name((int) date('w', $ts))));
                ?>
                <a href="/agenda/view?id=<?= (int) $event['id'] ?>" class="agenda-list-item">
                    <div class="agenda-list-date">
                        <strong><?= (int) date('j', $ts) ?></strong>
                        <span><?= e(mb_substr(br_month_name((int) date('n', $ts)), 0, 3)) ?></span>
                    </div>
                    <div class="agenda-list-info">
                        <strong><?= e($event['title']) ?></strong>
                        <span>
                            <?= e($dayLabel) ?><?= date('H:i', $ts) !== '00:00' ? ' às ' . date('H:i', $ts) : '' ?>
                            <?= !empty($meta['location']) ? ' · ' . e($meta['location']) : '' ?>
                        </span>
                    </div>
                    <?= life_status_badge($event['status'], 'agenda_event') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($ntfyTopic): ?>
    <details class="card agenda-ntfy">
        <summary>🔔 Notificações no celular — como ativar</summary>
        <ol>
            <li>Instale o app <strong>ntfy</strong> no celular (<a href="https://play.google.com/store/apps/details?id=io.heckel.ntfy" target="_blank" rel="noopener">Android</a> · <a href="https://apps.apple.com/app/ntfy/id1625396347" target="_blank" rel="noopener">iPhone</a>).</li>
            <li>Toque em <strong>+ (Subscribe to topic)</strong> e digite o tópico: <code><?= e($ntfyTopic) ?></code></li>
            <li>Pronto — você recebe um aviso <strong>1 dia antes</strong> e <strong>no dia</strong> de cada evento agendado.</li>
        </ol>
        <p class="hint">Também funciona no navegador do computador: <a href="<?= e($ntfyServer . '/' . $ntfyTopic) ?>" target="_blank" rel="noopener"><?= e($ntfyServer . '/' . $ntfyTopic) ?></a>. Mantenha o nome do tópico em segredo — ele funciona como uma senha.</p>
    </details>
<?php endif; ?>
