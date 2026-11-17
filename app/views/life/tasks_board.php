<section class="module-head">
    <div>
        <p class="eyebrow">Execução</p>
        <h2>Tasks</h2>
        <p><?= e($config['description']) ?></p>
    </div>
    <a href="/tasks/create" class="btn btn-primary">Nova tarefa</a>
</section>

<div class="page-actions">
    <form method="get" action="/tasks" class="search-form">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar tarefas...">
        <button type="submit" class="btn btn-ghost">Buscar</button>
    </form>
</div>

<div class="task-board" id="lifeTaskBoard" data-csrf="<?= e(csrf_token()) ?>">
    <?php foreach ($statuses as $status => $label): ?>
        <?php $tasks = $tasksByStatus[$status] ?? []; ?>
        <section class="task-column" data-status="<?= e($status) ?>">
            <header>
                <h3><?= e($label) ?></h3>
                <span class="bcol-count"><?= count($tasks) ?></span>
            </header>
            <div class="task-dropzone" data-status="<?= e($status) ?>">
                <?php foreach ($tasks as $task): ?>
                    <a href="/tasks/view?id=<?= $task['id'] ?>" class="task-card" draggable="true" data-task="<?= $task['id'] ?>">
                        <div class="task-card-top">
                            <?= life_priority_badge($task['priority']) ?>
                            <?php if ($task['due_at']): ?><span><?= br_date($task['due_at']) ?></span><?php endif; ?>
                        </div>
                        <strong><?= e($task['title']) ?></strong>
                        <?php if ($task['summary']): ?><p><?= e($task['summary']) ?></p><?php endif; ?>
                        <?php if ($task['tags']): ?>
                            <div class="tag-list">
                                <?php foreach (array_slice(life_tags_list($task['tags']), 0, 3) as $tag): ?>
                                    <span>#<?= e($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="node-footer">
                            <span><?= (int) $task['relation_count'] ?> relações</span>
                            <span><?= (int) $task['comment_count'] ?> comentários</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
