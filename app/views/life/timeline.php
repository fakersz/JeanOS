<section class="module-head">
    <div>
        <p class="eyebrow">Histórico global</p>
        <h2>Timeline</h2>
        <p>Tudo que acontece no sistema em ordem cronológica: notas, projetos, tarefas, clientes, OS e financeiro.</p>
    </div>
</section>

<section class="timeline-page">
    <?php if (!$events): ?>
        <div class="empty-state">
            <h3>Nenhum evento registrado.</h3>
            <p>A timeline será preenchida conforme você usar o sistema.</p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <article class="timeline-entry">
                <span class="timeline-pin"></span>
                <div>
                    <div class="timeline-entry-head">
                        <strong><?= e($event['title']) ?></strong>
                        <small><?= br_date($event['occurred_at'], true) ?></small>
                    </div>
                    <p><?= e(life_type_label($event['entity_type'])) ?> · <?= e($event['event_type']) ?><?= $event['actor_name'] ? ' · ' . e($event['actor_name']) : '' ?></p>
                    <?php if ($event['entity_id'] && life_entity_exists($event['entity_type'], (int) $event['entity_id'])): ?>
                        <a href="<?= e(life_entity_url($event['entity_type'], (int) $event['entity_id'])) ?>" class="link">Abrir item</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
