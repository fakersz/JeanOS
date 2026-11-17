<?php
$todayLabel = date('d/m/Y');
$taskTotal = max(1, (int) $taskOpenCount);
$productivity = min(100, (int) round(((int) $taskDoneCount / $taskTotal) * 100));
$nodeUrl = static fn (array $node): string => life_entity_url((string) $node['type'], (int) $node['id']);
$workedHours = intdiv((int) $workedTodayMinutes, 60);
$workedMinutes = (int) $workedTodayMinutes % 60;
$workedLabel = $workedHours > 0 ? $workedHours . 'h ' . str_pad((string) $workedMinutes, 2, '0', STR_PAD_LEFT) : $workedMinutes . 'min';
?>

<section class="home-hero">
    <div>
        <p class="eyebrow"><?= e($todayLabel) ?></p>
        <h2><?= e($greeting) ?>, <?= e($firstName) ?> 👋</h2>
        <p class="home-hero-sub">Seu cérebro digital para rotina, projetos, clientes, conhecimento e execução.</p>
    </div>
    <div class="home-actions">
        <a href="/brain/create" class="btn btn-primary">Nova nota</a>
        <a href="/tasks/create" class="btn btn-ghost">Nova tarefa</a>
        <a href="/agenda/create" class="btn btn-ghost">Novo evento</a>
    </div>
</section>

<div class="life-metrics">
    <a href="/tasks" class="metric-card">
        <span>Foco hoje</span>
        <strong><?= count($focusTasks) ?></strong>
        <small>Tarefas em Hoje ou Em foco</small>
    </a>
    <a href="/projects" class="metric-card">
        <span>Projetos ativos</span>
        <strong><?= (int) $projectCount ?></strong>
        <small>Em andamento agora</small>
    </a>
    <a href="/orders" class="metric-card">
        <span>Assistência</span>
        <strong><?= (int) $openOrders ?></strong>
        <small>OS em aberto</small>
    </a>
    <a href="/tasks" class="metric-card">
        <span>Tempo hoje</span>
        <strong><?= e($workedLabel) ?></strong>
        <small>Registrado em tarefas</small>
    </a>
    <a href="/financial" class="metric-card">
        <span>Resumo financeiro</span>
        <strong><?= money($financeSummary['profit']) ?></strong>
        <small><?= e(date('m/Y')) ?></small>
    </a>
</div>

<div class="home-grid">
    <section class="card home-card home-card-wide">
        <div class="card-header">
            <h2>Hoje</h2>
            <a href="/agenda" class="btn btn-sm btn-ghost">Agenda</a>
        </div>
        <?php if (!$todayAgenda && !$focusTasks): ?>
            <p class="empty">Nada fixo para hoje. Use a Home para puxar o que merece foco.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($todayAgenda as $item): ?>
                    <a href="<?= e($nodeUrl($item)) ?>" class="life-row">
                        <span class="life-dot life-dot-agenda"></span>
                        <span>
                            <strong><?= e($item['title']) ?></strong>
                            <small><?= br_date($item['starts_at'], true) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($focusTasks as $item): ?>
                    <a href="<?= e($nodeUrl($item)) ?>" class="life-row">
                        <span class="life-dot life-dot-task"></span>
                        <span>
                            <strong><?= e($item['title']) ?></strong>
                            <small><?= life_status_label($item['status'], 'task') ?><?= $item['due_at'] ? ' · prazo ' . br_date($item['due_at']) : '' ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header"><h2>Meta de produtividade</h2></div>
        <div class="progress-ring" style="--value: <?= $productivity ?>;">
            <strong><?= $productivity ?>%</strong>
            <span>tarefas concluídas</span>
        </div>
        <div class="compact-stats">
            <span><?= (int) $taskDoneCount ?> concluídas</span>
            <span><?= (int) $taskOpenCount ?> registradas</span>
        </div>
    </section>

    <section class="card home-card">
        <div class="card-header">
            <h2>Projetos ativos</h2>
            <a href="/projects" class="btn btn-sm btn-ghost">Ver</a>
        </div>
        <?php if (!$activeProjects): ?>
            <p class="empty">Nenhum projeto ativo ainda.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($activeProjects as $project): ?>
                    <a href="/projects/view?id=<?= $project['id'] ?>" class="life-row">
                        <span class="life-dot life-dot-project"></span>
                        <span>
                            <strong><?= e($project['title']) ?></strong>
                            <small><?= e($project['category'] ?: 'Sem categoria') ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header">
            <h2>Tarefas atrasadas</h2>
            <a href="/tasks" class="btn btn-sm btn-ghost">Tasks</a>
        </div>
        <?php if (!$overdueTasks): ?>
            <p class="empty">Nenhuma tarefa atrasada.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($overdueTasks as $task): ?>
                    <a href="/tasks/view?id=<?= $task['id'] ?>" class="life-row">
                        <span class="life-dot life-dot-danger"></span>
                        <span>
                            <strong><?= e($task['title']) ?></strong>
                            <small>Prazo <?= br_date($task['due_at']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header">
            <h2>Notas recentes</h2>
            <a href="/brain" class="btn btn-sm btn-ghost">Brain</a>
        </div>
        <?php if (!$recentNotes): ?>
            <p class="empty">O Brain ainda está vazio.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($recentNotes as $note): ?>
                    <a href="<?= e($nodeUrl($note)) ?>" class="life-row">
                        <span class="life-dot life-dot-note"></span>
                        <span>
                            <strong><?= e($note['title']) ?></strong>
                            <small><?= br_date($note['updated_at'], true) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header">
            <h2>Ideias recentes</h2>
            <a href="/ideas" class="btn btn-sm btn-ghost">Ideias</a>
        </div>
        <?php if (!$recentIdeas): ?>
            <p class="empty">Nenhuma ideia capturada.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($recentIdeas as $idea): ?>
                    <a href="/ideas/view?id=<?= $idea['id'] ?>" class="life-row">
                        <span class="life-dot life-dot-idea"></span>
                        <span>
                            <strong><?= e($idea['title']) ?></strong>
                            <small><?= e($idea['tags'] ?: 'Sem tags') ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header">
            <h2>Documentos</h2>
            <a href="/documents" class="btn btn-sm btn-ghost">Biblioteca</a>
        </div>
        <?php if (!$recentDocs): ?>
            <p class="empty">Nenhum documento salvo.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($recentDocs as $doc): ?>
                    <a href="/documents/view?id=<?= $doc['id'] ?>" class="life-row">
                        <span class="life-dot life-dot-doc"></span>
                        <span>
                            <strong><?= e($doc['title']) ?></strong>
                            <small><?= e($doc['category'] ?: ($doc['mime_type'] ?: 'Arquivo')) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header">
            <h2>Próximos pagamentos</h2>
            <a href="/financial" class="btn btn-sm btn-ghost">Financeiro</a>
        </div>
        <?php if (!$upcomingPayments): ?>
            <p class="empty">Nenhum pagamento futuro registrado.</p>
        <?php else: ?>
            <div class="life-list">
                <?php foreach ($upcomingPayments as $payment): ?>
                    <a href="/financial?month=<?= e(substr($payment['entry_date'], 0, 7)) ?>" class="life-row">
                        <span class="life-dot life-dot-danger"></span>
                        <span>
                            <strong><?= e($payment['description']) ?></strong>
                            <small><?= br_date($payment['entry_date']) ?> · <?= money($payment['amount']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card home-card-wide">
        <div class="card-header">
            <h2>Timeline</h2>
            <a href="/timeline" class="btn btn-sm btn-ghost">Ver tudo</a>
        </div>
        <?php if (!$timeline): ?>
            <p class="empty">A timeline começa quando você cria ou altera algo.</p>
        <?php else: ?>
            <div class="timeline-mini">
                <?php foreach ($timeline as $event): ?>
                    <div class="timeline-item">
                        <span class="timeline-pin"></span>
                        <div>
                            <strong><?= e($event['title']) ?></strong>
                            <small><?= br_date($event['occurred_at'], true) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card home-card">
        <div class="card-header"><h2>Sistema</h2></div>
        <div class="compact-stats compact-stats-stack">
            <a href="/inventory?low=1">Estoque baixo <strong><?= (int) $lowStockCount ?></strong></a>
            <a href="/orders">OS abertas <strong><?= (int) $openOrders ?></strong></a>
            <a href="/financial">Lucro do mês <strong><?= money($financeSummary['profit']) ?></strong></a>
        </div>
    </section>
</div>
