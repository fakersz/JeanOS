<?php
$statuses = life_statuses_for($config['type']);
$tagsFor = static fn (?string $tags): array => array_slice(life_tags_list($tags), 0, 4);
?>

<section class="module-head">
    <div>
        <p class="eyebrow"><?= e(life_type_label($config['type'])) ?></p>
        <h2><?= e($config['title']) ?></h2>
        <p><?= e($config['description']) ?></p>
    </div>
    <a href="/<?= e($config['route']) ?>/create" class="btn btn-primary">Novo <?= e(mb_strtolower($config['singular'])) ?></a>
</section>

<?php if ($config['route'] === 'brain'): ?>
    <div class="life-hub">
        <a href="/knowledge"><strong>Knowledge</strong><span>Base técnica e aprendizados</span></a>
        <a href="/ideas"><strong>Ideias</strong><span>Captura rápida</span></a>
        <a href="/documents"><strong>Documentos</strong><span>Arquivos e referências</span></a>
        <a href="/timeline"><strong>Timeline</strong><span>Histórico global</span></a>
    </div>
<?php endif; ?>

<div class="page-actions">
    <form method="get" action="/<?= e($config['route']) ?>" class="search-form">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar em <?= e(mb_strtolower($config['title'])) ?>...">
        <?php if ($statuses): ?>
            <select name="status">
                <option value="">Todos os status</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
</div>

<?php if (!$nodes): ?>
    <section class="empty-state">
        <h3>Nada por aqui ainda.</h3>
        <p>Crie o primeiro registro para começar a conectar este módulo ao resto do sistema.</p>
        <a href="/<?= e($config['route']) ?>/create" class="btn btn-primary">Criar agora</a>
    </section>
<?php else: ?>
    <div class="life-grid">
        <?php foreach ($nodes as $node): ?>
            <?php $meta = life_metadata($node); ?>
            <a href="/<?= e($config['route']) ?>/view?id=<?= $node['id'] ?>" class="life-node-card">
                <div class="life-node-top">
                    <span class="node-type"><?= e(life_type_label($node['type'])) ?></span>
                    <?php if ($node['favorite']): ?><span class="node-fav">Favorito</span><?php endif; ?>
                </div>
                <h3><?= e($node['title']) ?></h3>
                <?php if ($node['summary']): ?>
                    <p><?= e($node['summary']) ?></p>
                <?php elseif ($node['body']): ?>
                    <p><?= e(mb_strimwidth(strip_tags($node['body']), 0, 150, '...')) ?></p>
                <?php else: ?>
                    <p class="text-faint"><?= e($config['body_hint']) ?></p>
                <?php endif; ?>
                <div class="node-badges">
                    <?= life_status_badge($node['status'], $node['type']) ?>
                    <?= life_priority_badge($node['priority']) ?>
                    <?php if ($node['category']): ?><span class="badge"><?= e($node['category']) ?></span><?php endif; ?>
                </div>
                <?php if ($tagsFor($node['tags'])): ?>
                    <div class="tag-list">
                        <?php foreach ($tagsFor($node['tags']) as $tag): ?>
                            <span>#<?= e($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="node-footer">
                    <span><?= br_date($node['updated_at'], true) ?></span>
                    <span><?= (int) $node['relation_count'] ?> relações</span>
                    <?php if (!empty($meta['github'])): ?><span>GitHub</span><?php endif; ?>
                    <?php if ($node['file_path']): ?><span>Arquivo</span><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
