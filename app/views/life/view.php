<?php
$tags = life_tags_list($node['tags']);
$actionBase = '/' . $config['route'];
$checklist = $metadata['checklist'] ?? [];
?>

<div class="page-actions">
    <a href="/<?= e($config['route']) ?>" class="btn btn-ghost">Voltar</a>
    <div class="spacer"></div>
    <a href="/<?= e($config['route']) ?>/edit?id=<?= $node['id'] ?>" class="btn btn-ghost">Editar</a>
    <form method="post" action="<?= e($actionBase) ?>/delete" class="inline" data-confirm="Remover este item?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $node['id'] ?>">
        <button type="submit" class="btn btn-danger">Remover</button>
    </form>
</div>

<section class="life-detail-hero">
    <div>
        <p class="eyebrow"><?= e(life_type_label($node['type'])) ?></p>
        <h2><?= e($node['title']) ?></h2>
        <?php if ($node['summary']): ?><p><?= e($node['summary']) ?></p><?php endif; ?>
        <div class="node-badges">
            <?= life_status_badge($node['status'], $node['type']) ?>
            <?= life_priority_badge($node['priority']) ?>
            <?php if ($node['category']): ?><span class="badge"><?= e($node['category']) ?></span><?php endif; ?>
            <?php if ($node['favorite']): ?><span class="badge">Favorito</span><?php endif; ?>
        </div>
    </div>
    <div class="life-detail-meta">
        <span>Criado <?= br_date($node['created_at'], true) ?></span>
        <span>Atualizado <?= br_date($node['updated_at'], true) ?></span>
        <?php if ($node['due_at']): ?><span>Prazo <?= br_date($node['due_at'], true) ?></span><?php endif; ?>
    </div>
</section>

<div class="life-detail-grid">
    <main class="life-detail-main">
        <?php if ($tags): ?>
            <div class="tag-list tag-list-lg">
                <?php foreach ($tags as $tag): ?><span>#<?= e($tag) ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($node['source_url']): ?>
            <p class="external-link"><a href="<?= e($node['source_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($node['source_url']) ?></a></p>
        <?php endif; ?>

        <?php if ($node['file_path']): ?>
            <section class="detail-section">
                <h3>Arquivo</h3>
                <a href="/documents/file?id=<?= $node['id'] ?>" target="_blank" class="file-pill">
                    <span><?= e($metadata['original_name'] ?? basename((string) $node['file_path'])) ?></span>
                    <small><?= e($node['mime_type'] ?: 'arquivo') ?><?= $node['file_size'] ? ' · ' . number_format((int) $node['file_size'] / 1024, 1, ',', '.') . ' KB' : '' ?></small>
                </a>
            </section>
        <?php endif; ?>

        <?php if ($node['type'] === 'task' && $checklist): ?>
            <section class="detail-section">
                <h3>Checklist</h3>
                <ul class="check-list">
                    <?php foreach ($checklist as $item): ?>
                        <li><span></span><?= e(is_array($item) ? ($item['text'] ?? '') : (string) $item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($node['body']): ?>
            <section class="detail-section markdown">
                <?= md($node['body']) ?>
            </section>
        <?php else: ?>
            <section class="detail-section">
                <p class="empty">Sem conteúdo principal.</p>
            </section>
        <?php endif; ?>

        <?php if ($node['type'] === 'diary' && ($metadata['done'] ?? $metadata['learned'] ?? $metadata['improve'] ?? false)): ?>
            <section class="detail-section">
                <h3>Reflexão</h3>
                <dl class="meta-list">
                    <?php if (!empty($metadata['done'])): ?><div><dt>O que fiz</dt><dd><?= nl2br(e($metadata['done'])) ?></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['learned'])): ?><div><dt>O que aprendi</dt><dd><?= nl2br(e($metadata['learned'])) ?></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['improve'])): ?><div><dt>O que melhorar</dt><dd><?= nl2br(e($metadata['improve'])) ?></dd></div><?php endif; ?>
                </dl>
            </section>
        <?php endif; ?>

        <section class="detail-section">
            <h3>Comentários</h3>
            <form method="post" action="<?= e($actionBase) ?>/add_comment" class="comment-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $node['id'] ?>">
                <textarea name="body" rows="3" placeholder="Adicionar comentário em Markdown..." required></textarea>
                <button type="submit" class="btn btn-primary btn-sm">Comentar</button>
            </form>

            <?php if ($comments): ?>
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <article class="comment-item">
                            <div class="comment-head">
                                <span><?= br_date($comment['created_at'], true) ?></span>
                                <form method="post" action="<?= e($actionBase) ?>/delete_comment" class="inline" data-confirm="Excluir comentário?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $node['id'] ?>">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="cd-comment-del" aria-label="Excluir comentário">×</button>
                                </form>
                            </div>
                            <div class="markdown"><?= md($comment['body']) ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="life-side">
        <section class="side-panel">
            <h3>Contexto</h3>
            <dl class="meta-list">
                <?php if ($node['starts_at']): ?><div><dt>Início</dt><dd><?= br_date($node['starts_at'], true) ?></dd></div><?php endif; ?>
                <?php if ($node['due_at']): ?><div><dt>Prazo/Fim</dt><dd><?= br_date($node['due_at'], true) ?></dd></div><?php endif; ?>
                <?php if ($node['time_minutes']): ?><div><dt>Tempo</dt><dd><?= (int) $node['time_minutes'] ?> min</dd></div><?php endif; ?>
                <?php if (!empty($metadata['responsible'])): ?><div><dt>Responsável</dt><dd><?= e($metadata['responsible']) ?></dd></div><?php endif; ?>
                <?php if (!empty($metadata['mood'])): ?><div><dt>Humor</dt><dd><?= e($metadata['mood']) ?></dd></div><?php endif; ?>
                <?php if (isset($metadata['productivity'])): ?><div><dt>Produtividade</dt><dd><?= e((string) $metadata['productivity']) ?>/10</dd></div><?php endif; ?>
                <?php if (!empty($metadata['location'])): ?><div><dt>Local</dt><dd><?= e($metadata['location']) ?></dd></div><?php endif; ?>
                <?php if (!empty($metadata['attendees'])): ?><div><dt>Participantes</dt><dd><?= e($metadata['attendees']) ?></dd></div><?php endif; ?>
                <?php if (!empty($metadata['plate'])): ?><div><dt>Placa</dt><dd><?= e($metadata['plate']) ?></dd></div><?php endif; ?>
                <?php if (!empty($metadata['model'])): ?><div><dt>Modelo</dt><dd><?= e($metadata['model']) ?></dd></div><?php endif; ?>
                <?php if (!empty($metadata['year'])): ?><div><dt>Ano</dt><dd><?= e((string) $metadata['year']) ?></dd></div><?php endif; ?>
                <?php if (!empty($metadata['km'])): ?><div><dt>KM</dt><dd><?= e((string) $metadata['km']) ?></dd></div><?php endif; ?>
            </dl>
        </section>

        <?php if ($node['type'] === 'project'): ?>
            <section class="side-panel">
                <h3>Projeto</h3>
                <dl class="meta-list">
                    <?php if (!empty($metadata['objective'])): ?><div><dt>Objetivo</dt><dd><?= nl2br(e($metadata['objective'])) ?></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['github'])): ?><div><dt>GitHub</dt><dd><a href="<?= e($metadata['github']) ?>" target="_blank" rel="noopener noreferrer">Abrir</a></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['domain'])): ?><div><dt>Domínio</dt><dd><?= e($metadata['domain']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['server'])): ?><div><dt>Servidor</dt><dd><?= e($metadata['server']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['links'])): ?><div><dt>Links</dt><dd><?= e($metadata['links']) ?></dd></div><?php endif; ?>
                    <?php if (!empty($metadata['technologies'])): ?><div><dt>Tecnologias</dt><dd><?= e(implode(', ', $metadata['technologies'])) ?></dd></div><?php endif; ?>
                </dl>
            </section>
        <?php endif; ?>

        <section class="side-panel">
            <h3>Relações</h3>
            <?php if (!$relations): ?>
                <p class="empty">Nenhuma relação criada.</p>
            <?php else: ?>
                <div class="relations-list">
                    <?php foreach ($relations as $relation): ?>
                        <div class="relation-item">
                            <a href="<?= e($relation['other_url']) ?>">
                                <small><?= e($relation['other_label']) ?> · <?= e($relation['relation_type']) ?></small>
                                <strong><?= e($relation['other_title']) ?></strong>
                            </a>
                            <form method="post" action="<?= e($actionBase) ?>/unlink" class="inline" data-confirm="Remover relacionamento?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $node['id'] ?>">
                                <input type="hidden" name="link_id" value="<?= $relation['id'] ?>">
                                <button type="submit" class="cd-comment-del" aria-label="Remover relação">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e($actionBase) ?>/link" class="relation-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $node['id'] ?>">
                <select name="target_type" required>
                    <?php foreach ($entityTypes as $type => $label): ?>
                        <option value="<?= e($type) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="target_id" min="1" placeholder="ID" required>
                <input type="text" name="relation_type" maxlength="60" value="relacionado" placeholder="Tipo">
                <button type="submit" class="btn btn-ghost btn-sm">Relacionar</button>
            </form>
        </section>
    </aside>
</div>
