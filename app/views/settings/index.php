<section class="module-head">
    <div>
        <p class="eyebrow">Sistema pessoal</p>
        <h2>Configurações</h2>
        <p>Visão estrutural do LifeOS, entidades conectáveis e base preparada para busca e IA.</p>
    </div>
</section>

<div class="settings-grid">
    <section class="card">
        <div class="card-header"><h2>Perfil</h2></div>
        <dl class="detail-grid">
            <div><dt>Nome</dt><dd><?= e($user['name'] ?? '—') ?></dd></div>
            <div><dt>E-mail</dt><dd><?= e($user['email'] ?? '—') ?></dd></div>
            <div><dt>Desde</dt><dd><?= br_date($user['created_at'] ?? null) ?></dd></div>
        </dl>
    </section>

    <section class="card">
        <div class="card-header"><h2>Camada LifeOS</h2></div>
        <div class="type-cloud">
            <?php foreach ($nodeTypes as $type => $label): ?>
                <span><?= e($label) ?><small><?= e($type) ?></small></span>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h2>Entidades relacionáveis</h2></div>
        <div class="type-cloud">
            <?php foreach ($entityTypes as $type => $label): ?>
                <span><?= e($label) ?><small><?= e($type) ?></small></span>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h2>IA futura</h2></div>
        <p class="empty">Os registros novos são salvos como nós pesquisáveis, com metadados JSON, timeline e índice textual em <code>ai_context_sources</code>. Um chat interno poderá consultar essa camada sem reescrever os módulos.</p>
    </section>
</div>
