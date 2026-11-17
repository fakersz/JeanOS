<?php
/** Controller: Home LifeOS */

require APP_PATH . '/models/Life.php';
require APP_PATH . '/models/FinancialEntry.php';
require APP_PATH . '/models/Inventory.php';

function home_greeting(): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Bom dia';
    }
    if ($hour < 18) {
        return 'Boa tarde';
    }
    return 'Boa noite';
}

function home_index(): void
{
    $pdo = db();

    $statusCounts = [];
    $stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM service_orders GROUP BY status');
    foreach ($stmt as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }
    $openStatuses = ['aberta', 'diagnostico', 'aguardando_aprovacao', 'em_reparo', 'pronto', 'aguardando_peca'];
    $openOrders = 0;
    foreach ($openStatuses as $status) {
        $openOrders += $statusCounts[$status] ?? 0;
    }

    $month = date('Y-m');
    $summary = financial_summary($month);
    $stmt = $pdo->query(
        "SELECT * FROM financial_entries
         WHERE type = 'saida' AND entry_date >= CURRENT_DATE
         ORDER BY entry_date ASC, id ASC
         LIMIT 5"
    );
    $upcomingPayments = $stmt->fetchAll();

    render('home', [
        'title'           => 'Home',
        'greeting'        => home_greeting(),
        'firstName'       => strtok((string) (current_user()['name'] ?? 'Usuário'), ' ') ?: 'Usuário',
        'todayAgenda'     => life_today_agenda(),
        'upcomingAgenda'  => life_upcoming_agenda(),
        'focusTasks'      => life_focus_tasks(),
        'overdueTasks'    => life_overdue_tasks(),
        'activeProjects'  => life_active_projects(),
        'recentNotes'     => life_recent_nodes(['note', 'knowledge'], 6),
        'recentIdeas'     => life_recent_nodes(['idea'], 4),
        'recentDocs'      => life_recent_nodes(['document'], 4),
        'timeline'        => life_timeline(8),
        'financeSummary'  => $summary,
        'upcomingPayments'=> $upcomingPayments,
        'openOrders'      => $openOrders,
        'lowStockCount'   => inventory_low_count(),
        'workedTodayMinutes' => (int) $pdo->query(
            "SELECT COALESCE(SUM(time_minutes), 0)
             FROM life_nodes
             WHERE type = 'task' AND updated_at::date = CURRENT_DATE"
        )->fetchColumn(),
        'taskDoneCount'   => life_node_count(['type' => 'task', 'status' => 'done']),
        'taskOpenCount'   => life_node_count(['type' => 'task']),
        'projectCount'    => life_node_count(['type' => 'project', 'status' => 'active']),
    ]);
}
