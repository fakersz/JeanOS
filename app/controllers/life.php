<?php
/** Controller: módulos LifeOS */

require APP_PATH . '/models/Life.php';

const LIFE_UPLOAD_MAX_BYTES = 40 * 1024 * 1024;

function life_modules(): array
{
    return [
        'brain' => [
            'route' => 'brain',
            'type' => 'note',
            'title' => 'Brain',
            'singular' => 'Nota',
            'description' => 'Notas, links, checklists e contexto solto que precisa virar memória pesquisável.',
            'body_label' => 'Conteúdo',
            'body_hint' => 'Markdown, checklists, links internos e raciocínios.',
        ],
        'agenda' => [
            'route' => 'agenda',
            'type' => 'agenda_event',
            'title' => 'Agenda',
            'singular' => 'Evento',
            'description' => 'Compromissos, blocos de rotina, reuniões e lembretes com data.',
            'body_label' => 'Notas do evento',
            'body_hint' => 'Contexto, links e pauta.',
        ],
        'tasks' => [
            'route' => 'tasks',
            'type' => 'task',
            'title' => 'Tasks',
            'singular' => 'Tarefa',
            'description' => 'Kanban de execução diária com prioridade, prazo, projeto e checklist.',
            'body_label' => 'Descrição',
            'body_hint' => 'Critérios, contexto e próximos passos.',
        ],
        'projects' => [
            'route' => 'projects',
            'type' => 'project',
            'title' => 'Projetos',
            'singular' => 'Projeto',
            'description' => 'Objetivos, documentação, links técnicos, financeiro e tarefas conectadas.',
            'body_label' => 'Documentação',
            'body_hint' => 'Decisões, arquitetura, histórico e links importantes.',
        ],
        'knowledge' => [
            'route' => 'knowledge',
            'type' => 'knowledge',
            'title' => 'Knowledge',
            'singular' => 'Registro',
            'description' => 'Base de conhecimento pesquisável e conectada a clientes, OS, modelos e fornecedores.',
            'body_label' => 'Conhecimento',
            'body_hint' => 'Explicação, passos de diagnóstico, fontes e observações.',
        ],
        'ideas' => [
            'route' => 'ideas',
            'type' => 'idea',
            'title' => 'Ideias',
            'singular' => 'Ideia',
            'description' => 'Captura rápida de oportunidades, hipóteses, melhorias e apostas futuras.',
            'body_label' => 'Ideia',
            'body_hint' => 'Problema, solução, impacto e próximos passos.',
        ],
        'diary' => [
            'route' => 'diary',
            'type' => 'diary',
            'title' => 'Diário',
            'singular' => 'Registro diário',
            'description' => 'Reflexão diária ligada automaticamente à sua rotina, projetos e tarefas.',
            'body_label' => 'Notas do dia',
            'body_hint' => 'O que aconteceu, decisões, percepções e contexto pessoal.',
        ],
        'documents' => [
            'route' => 'documents',
            'type' => 'document',
            'title' => 'Documentos',
            'singular' => 'Documento',
            'description' => 'Biblioteca de PDFs, imagens, firmwares, drivers, vídeos e arquivos técnicos.',
            'body_label' => 'Observações',
            'body_hint' => 'Origem, uso, versões e contexto.',
            'has_file' => true,
        ],
        'development' => [
            'route' => 'development',
            'type' => 'project',
            'title' => 'Desenvolvimento',
            'singular' => 'Projeto',
            'description' => 'Projetos de software, servidores, domínios, GitHub, tecnologias e deploys.',
            'body_label' => 'Documentação técnica',
            'body_hint' => 'Arquitetura, deploy, decisões e pendências.',
            'forced_category' => 'Desenvolvimento',
        ],
        'automoveis' => [
            'route' => 'automoveis',
            'type' => 'vehicle',
            'title' => 'Automóveis',
            'singular' => 'Automóvel',
            'description' => 'Veículos, manutenções, documentos, custos e histórico.',
            'body_label' => 'Histórico',
            'body_hint' => 'Manutenções, pendências, observações e registros.',
        ],
    ];
}

function life_config(string $module): array
{
    $modules = life_modules();
    if (!isset($modules[$module])) {
        http_response_code(404);
        render('errors/404', ['title' => 'Módulo não encontrado']);
    }
    return $modules[$module];
}

function life_allowed_upload_extensions(): array
{
    return [
        'pdf', 'txt', 'md', 'csv', 'xlsx', 'doc', 'docx',
        'png', 'jpg', 'jpeg', 'gif', 'webp',
        'mp4', 'mov', 'm4v', 'webm',
        'zip', 'rar', '7z', 'tar', 'gz',
        'iso', 'dmg', 'pkg', 'ipsw', 'bin', 'rom',
        'brd', 'bdv', 'bv', 'cad', 'fz', 'tvw',
    ];
}

function life_handle_document_upload(?array $current = null): array
{
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [
            'file_path' => $current['file_path'] ?? null,
            'mime_type' => $current['mime_type'] ?? null,
            'file_size' => $current['file_size'] ?? null,
            'original_name' => life_metadata($current ?? [])['original_name'] ?? null,
        ];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload do arquivo (código ' . $file['error'] . ').');
    }
    if ($file['size'] > LIFE_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Arquivo muito grande (máximo 40 MB neste servidor).');
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, life_allowed_upload_extensions(), true)) {
        throw new RuntimeException('Formato não aceito (.' . ($ext ?: '?') . ').');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
    $name = 'life_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/' . $name)) {
        throw new RuntimeException('Não foi possível salvar o arquivo no servidor.');
    }

    if ($current && !empty($current['file_path'])) {
        $old = UPLOADS_PATH . '/' . basename((string) $current['file_path']);
        if (is_file($old)) {
            unlink($old);
        }
    }

    return [
        'file_path' => $name,
        'mime_type' => $mime,
        'file_size' => (int) $file['size'],
        'original_name' => (string) $file['name'],
    ];
}

function life_lines_from_text(?string $raw): array
{
    if (!$raw) {
        return [];
    }
    $lines = array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: []);
    $lines = array_filter($lines, static fn ($line) => $line !== '');
    return array_values($lines);
}

function life_lines_to_text(array $lines): string
{
    return implode("\n", array_map(static fn ($line) => is_array($line) ? (string) ($line['text'] ?? '') : (string) $line, $lines));
}

function life_post_data(array $config, ?array $current = null): array
{
    $type = $config['type'];
    $status = post_str('status', $current['status'] ?? life_default_status($type));
    $statuses = life_statuses_for($type);
    if ($statuses && !array_key_exists((string) $status, $statuses)) {
        $status = life_default_status($type);
    }

    $priority = post_str('priority', $current['priority'] ?? 'normal');
    if ($priority && !array_key_exists($priority, life_priorities())) {
        $priority = 'normal';
    }
    if (!in_array($type, ['task', 'project'], true)) {
        $priority = null;
    }

    $startsAt = life_clean_datetime(post_str('starts_at'));
    $dueAt = life_clean_datetime(post_str('due_at'));
    $title = post_str('title');
    if (!$title && $type === 'diary') {
        $title = 'Diário — ' . br_date($startsAt ?: date('Y-m-d'));
    }
    if (!$title && $type === 'document' && !empty($_FILES['file']['name'])) {
        $title = pathinfo((string) $_FILES['file']['name'], PATHINFO_FILENAME);
    }

    $category = $config['forced_category'] ?? post_str('category');
    $metadata = life_metadata($current ?? []);

    if ($type === 'project') {
        $metadata = [
            'objective' => post_str('objective'),
            'github' => post_str('github'),
            'server' => post_str('server'),
            'domain' => post_str('domain'),
            'links' => post_str('links'),
            'technologies' => life_lines_from_text(post_str('technologies')),
        ];
    } elseif ($type === 'task') {
        $metadata = [
            'responsible' => post_str('responsible', current_user()['name'] ?? 'Usuário'),
            'checklist' => array_map(
                static fn ($line) => ['text' => $line, 'done' => false],
                life_lines_from_text(post_str('checklist'))
            ),
        ];
    } elseif ($type === 'diary') {
        $metadata = [
            'mood' => post_str('mood'),
            'productivity' => post_int('productivity'),
            'learned' => post_str('learned'),
            'done' => post_str('done'),
            'improve' => post_str('improve'),
        ];
    } elseif ($type === 'agenda_event') {
        $metadata = [
            'location' => post_str('location'),
            'attendees' => post_str('attendees'),
        ];
    } elseif ($type === 'vehicle') {
        $metadata = [
            'plate' => post_str('plate'),
            'model' => post_str('vehicle_model'),
            'year' => post_int('vehicle_year'),
            'km' => post_int('km'),
            'next_maintenance' => post_str('next_maintenance'),
        ];
    }

    $fileData = [
        'file_path' => $current['file_path'] ?? null,
        'mime_type' => $current['mime_type'] ?? null,
        'file_size' => $current['file_size'] ?? null,
    ];
    if (!empty($config['has_file'])) {
        $upload = life_handle_document_upload($current);
        $fileData = $upload;
        if (!empty($upload['original_name'])) {
            $metadata['original_name'] = $upload['original_name'];
        }
    }

    return [
        'type' => $type,
        'title' => $title,
        'summary' => post_str('summary'),
        'body' => post_str('body'),
        'status' => $status,
        'priority' => $priority,
        'category' => $category,
        'tags' => post_str('tags'),
        'favorite' => isset($_POST['favorite']),
        'source_url' => post_str('source_url'),
        'starts_at' => $startsAt,
        'due_at' => $dueAt,
        'completed_at' => $type === 'task' && $status === 'done'
            ? ($current['completed_at'] ?? date('Y-m-d H:i:s'))
            : null,
        'time_minutes' => post_int('time_minutes', 0),
        'metadata' => array_filter($metadata, static fn ($value) => $value !== null && $value !== '' && $value !== []),
    ] + $fileData;
}

function life_collection_index(string $module): void
{
    $config = life_config($module);
    if ($module === 'tasks') {
        tasks_index();
        return;
    }
    if ($module === 'agenda') {
        agenda_calendar_index();
        return;
    }

    $filters = [
        'type' => $config['type'],
        'q' => trim((string) ($_GET['q'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? '')),
        'order' => $config['type'] === 'agenda_event' ? 'agenda' : 'updated',
    ];
    if (!empty($config['forced_category'])) {
        $filters['category'] = $config['forced_category'];
    }

    render('life/index', [
        'title' => $config['title'],
        'config' => $config,
        'nodes' => life_nodes_all($filters),
        'search' => $filters['q'],
        'status' => $filters['status'],
    ]);
}

function life_collection_create(string $module): void
{
    $config = life_config($module);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = life_post_data($config);
            if (!$data['title']) {
                flash('error', 'O título é obrigatório.');
            } else {
                $id = life_node_create($data);
                $projectId = post_int('project_id');
                if ($projectId && $config['type'] !== 'project') {
                    life_replace_project_link($config['type'], $id, $projectId);
                }
                flash('success', $config['singular'] . ' criado.');
                redirect('/' . $config['route'] . '/view?id=' . $id);
            }
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
    }

    render('life/form', [
        'title' => 'Novo ' . mb_strtolower($config['singular']),
        'config' => $config,
        'node' => null,
        'metadata' => [],
        'projects' => life_project_options(),
        'selectedProjectId' => null,
    ]);
}

function life_collection_edit(string $module): void
{
    $config = life_config($module);
    $node = life_node_find(get_int('id', 0), $config['type']);
    if (!$node) {
        flash('error', $config['singular'] . ' não encontrado.');
        redirect('/' . $config['route']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = life_post_data($config, $node);
            if (!$data['title']) {
                flash('error', 'O título é obrigatório.');
            } else {
                life_node_update((int) $node['id'], $data);
                $projectId = post_int('project_id');
                if ($config['type'] !== 'project') {
                    life_replace_project_link($config['type'], (int) $node['id'], $projectId);
                }
                flash('success', $config['singular'] . ' atualizado.');
                redirect('/' . $config['route'] . '/view?id=' . $node['id']);
            }
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
    }

    render('life/form', [
        'title' => 'Editar ' . mb_strtolower($config['singular']),
        'config' => $config,
        'node' => $node,
        'metadata' => life_metadata($node),
        'projects' => life_project_options(),
        'selectedProjectId' => life_selected_project_id($config['type'], (int) $node['id']),
    ]);
}

function life_collection_view(string $module): void
{
    $config = life_config($module);
    $node = life_node_find(get_int('id', 0), $config['type']);
    if (!$node) {
        flash('error', $config['singular'] . ' não encontrado.');
        redirect('/' . $config['route']);
    }

    render('life/view', [
        'title' => $node['title'],
        'config' => $config,
        'node' => $node,
        'metadata' => life_metadata($node),
        'relations' => life_links_for($config['type'], (int) $node['id']),
        'comments' => life_comments_for((int) $node['id']),
        'entityTypes' => life_entity_types(),
    ]);
}

function life_collection_delete(string $module): void
{
    require_post('/' . $module);
    $config = life_config($module);
    $id = post_int('id', 0);
    if ($id) {
        life_node_delete($id);
        flash('success', $config['singular'] . ' removido.');
    }
    redirect('/' . $config['route']);
}

function life_collection_link(string $module): void
{
    require_post('/' . $module);
    $config = life_config($module);
    $id = post_int('id', 0);
    $targetType = post_str('target_type', '');
    $targetId = post_int('target_id', 0);
    try {
        life_link_create($config['type'], $id, $targetType, $targetId, post_str('relation_type', 'relacionado'), post_str('description'));
        flash('success', 'Relacionamento criado.');
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
    }
    redirect('/' . $config['route'] . '/view?id=' . $id);
}

function life_collection_unlink(string $module): void
{
    require_post('/' . $module);
    $config = life_config($module);
    $id = post_int('id', 0);
    $linkId = post_int('link_id', 0);
    if ($id && $linkId) {
        life_link_delete($linkId, $config['type'], $id);
        flash('success', 'Relacionamento removido.');
    }
    redirect('/' . $config['route'] . '/view?id=' . $id);
}

function life_collection_add_comment(string $module): void
{
    require_post('/' . $module);
    $config = life_config($module);
    $id = post_int('id', 0);
    $body = post_str('body');
    if ($id && $body) {
        life_comment_create($id, $body);
        flash('success', 'Comentário adicionado.');
    }
    redirect('/' . $config['route'] . '/view?id=' . $id);
}

function life_collection_delete_comment(string $module): void
{
    require_post('/' . $module);
    $config = life_config($module);
    $id = post_int('id', 0);
    $commentId = post_int('comment_id', 0);
    if ($commentId) {
        life_comment_delete($commentId);
    }
    redirect('/' . $config['route'] . '/view?id=' . $id);
}

function agenda_calendar_index(): void
{
    $config = life_config('agenda');

    // Mês exibido: ?m=YYYY-MM (padrão: mês atual).
    $monthParam = (string) ($_GET['m'] ?? '');
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
        $monthParam = date('Y-m');
    }
    [$year, $month] = array_map('intval', explode('-', $monthParam));

    $monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $monthStartTs = (int) strtotime($monthStart);
    $daysInMonth = (int) date('t', $monthStartTs);
    $monthEnd = date('Y-m-d 00:00:00', strtotime('+1 month', $monthStartTs));

    // Grade sempre começa no domingo e fecha semana completa.
    $gridStartTs = strtotime('-' . (int) date('w', $monthStartTs) . ' days', $monthStartTs);
    $lastDayTs = strtotime(sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth));
    $gridEndTs = strtotime('+' . (6 - (int) date('w', $lastDayTs)) . ' days', $lastDayTs);

    $eventsByDay = [];
    $gridEvents = life_agenda_events_between(
        date('Y-m-d 00:00:00', $gridStartTs),
        date('Y-m-d 00:00:00', strtotime('+1 day', $gridEndTs))
    );
    foreach ($gridEvents as $event) {
        $day = date('Y-m-d', strtotime((string) $event['starts_at']));
        $eventsByDay[$day][] = $event;
    }

    $monthEventCount = 0;
    foreach ($gridEvents as $event) {
        $ts = strtotime((string) $event['starts_at']);
        if ((int) date('n', $ts) === $month && (int) date('Y', $ts) === $year) {
            $monthEventCount++;
        }
    }

    // Busca opcional continua funcionando sobre a lista de eventos.
    $search = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));
    $searchResults = null;
    if ($search !== '' || $status !== '') {
        $searchResults = life_nodes_all([
            'type' => 'agenda_event',
            'q' => $search,
            'status' => $status,
            'order' => 'agenda',
        ]);
    }

    $now = time();
    render('life/agenda', [
        'title' => $config['title'],
        'config' => $config,
        'year' => $year,
        'month' => $month,
        'monthParam' => sprintf('%04d-%02d', $year, $month),
        'prevMonth' => date('Y-m', strtotime('-1 month', $monthStartTs)),
        'nextMonth' => date('Y-m', strtotime('+1 month', $monthStartTs)),
        'gridStartTs' => $gridStartTs,
        'gridEndTs' => $gridEndTs,
        'eventsByDay' => $eventsByDay,
        'monthEventCount' => $monthEventCount,
        'daysLeftMonth' => (int) date('t', $now) - (int) date('j', $now),
        'daysLeftYear' => (int) (strtotime(date('Y') . '-12-31') - strtotime(date('Y-m-d'))) / 86400,
        'upcoming' => life_agenda_upcoming(10),
        'search' => $search,
        'status' => $status,
        'searchResults' => $searchResults,
        'ntfyTopic' => env('NTFY_TOPIC'),
        'ntfyServer' => rtrim((string) env('NTFY_SERVER', 'https://ntfy.sh'), '/'),
    ]);
}

function tasks_index(): void
{
    $config = life_config('tasks');
    $search = trim((string) ($_GET['q'] ?? ''));
    $tasks = life_nodes_all(['type' => 'task', 'q' => $search, 'order' => 'tasks', 'limit' => 200]);
    $grouped = [];
    foreach ($tasks as $task) {
        $grouped[$task['status'] ?: 'backlog'][] = $task;
    }

    render('life/tasks_board', [
        'title' => 'Tasks',
        'config' => $config,
        'statuses' => life_task_statuses(),
        'tasksByStatus' => $grouped,
        'search' => $search,
    ]);
}

function tasks_move(): void
{
    header('Content-Type: application/json');
    $taskId = post_int('task_id');
    $status = post_str('status', '');
    $order = $_POST['order'] ?? [];
    if (!$taskId || !$status || !is_array($order)) {
        http_response_code(422);
        echo json_encode(['ok' => false]);
        return;
    }
    life_task_move($taskId, $status, $order);
    echo json_encode(['ok' => true]);
}

function documents_file(): void
{
    $node = life_node_find(get_int('id', 0), 'document');
    $file = $node && $node['file_path'] ? UPLOADS_PATH . '/' . basename((string) $node['file_path']) : null;
    if (!$file || !is_file($file)) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }
    $metadata = life_metadata($node);
    $downloadName = $metadata['original_name'] ?? basename((string) $node['file_path']);
    $mime = $node['mime_type'] ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}

function timeline_index(): void
{
    render('life/timeline', [
        'title' => 'Timeline',
        'events' => life_timeline(100),
    ]);
}

function life_current_module(): string
{
    global $resource;
    return (string) ($resource ?? 'brain');
}

function life_index(): void
{
    $module = life_current_module();
    if ($module === 'timeline') {
        timeline_index();
        return;
    }
    life_collection_index($module);
}

function life_create(): void
{
    life_collection_create(life_current_module());
}

function life_edit(): void
{
    life_collection_edit(life_current_module());
}

function life_view(): void
{
    life_collection_view(life_current_module());
}

function life_delete(): void
{
    life_collection_delete(life_current_module());
}

function life_link(): void
{
    life_collection_link(life_current_module());
}

function life_unlink(): void
{
    life_collection_unlink(life_current_module());
}

function life_add_comment(): void
{
    life_collection_add_comment(life_current_module());
}

function life_delete_comment(): void
{
    life_collection_delete_comment(life_current_module());
}

function life_move(): void
{
    if (life_current_module() !== 'tasks') {
        http_response_code(404);
        echo json_encode(['ok' => false]);
        return;
    }
    tasks_move();
}

function life_file(): void
{
    if (life_current_module() !== 'documents') {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }
    documents_file();
}

function brain_index(): void { life_collection_index('brain'); }
function brain_create(): void { life_collection_create('brain'); }
function brain_edit(): void { life_collection_edit('brain'); }
function brain_view(): void { life_collection_view('brain'); }
function brain_delete(): void { life_collection_delete('brain'); }
function brain_link(): void { life_collection_link('brain'); }
function brain_unlink(): void { life_collection_unlink('brain'); }
function brain_add_comment(): void { life_collection_add_comment('brain'); }
function brain_delete_comment(): void { life_collection_delete_comment('brain'); }

function agenda_index(): void { life_collection_index('agenda'); }
function agenda_create(): void { life_collection_create('agenda'); }
function agenda_edit(): void { life_collection_edit('agenda'); }
function agenda_view(): void { life_collection_view('agenda'); }
function agenda_delete(): void { life_collection_delete('agenda'); }
function agenda_link(): void { life_collection_link('agenda'); }
function agenda_unlink(): void { life_collection_unlink('agenda'); }
function agenda_add_comment(): void { life_collection_add_comment('agenda'); }
function agenda_delete_comment(): void { life_collection_delete_comment('agenda'); }

function tasks_create(): void { life_collection_create('tasks'); }
function tasks_edit(): void { life_collection_edit('tasks'); }
function tasks_view(): void { life_collection_view('tasks'); }
function tasks_delete(): void { life_collection_delete('tasks'); }
function tasks_link(): void { life_collection_link('tasks'); }
function tasks_unlink(): void { life_collection_unlink('tasks'); }
function tasks_add_comment(): void { life_collection_add_comment('tasks'); }
function tasks_delete_comment(): void { life_collection_delete_comment('tasks'); }

function projects_index(): void { life_collection_index('projects'); }
function projects_create(): void { life_collection_create('projects'); }
function projects_edit(): void { life_collection_edit('projects'); }
function projects_view(): void { life_collection_view('projects'); }
function projects_delete(): void { life_collection_delete('projects'); }
function projects_link(): void { life_collection_link('projects'); }
function projects_unlink(): void { life_collection_unlink('projects'); }
function projects_add_comment(): void { life_collection_add_comment('projects'); }
function projects_delete_comment(): void { life_collection_delete_comment('projects'); }

function knowledge_index(): void { life_collection_index('knowledge'); }
function knowledge_create(): void { life_collection_create('knowledge'); }
function knowledge_edit(): void { life_collection_edit('knowledge'); }
function knowledge_view(): void { life_collection_view('knowledge'); }
function knowledge_delete(): void { life_collection_delete('knowledge'); }
function knowledge_link(): void { life_collection_link('knowledge'); }
function knowledge_unlink(): void { life_collection_unlink('knowledge'); }
function knowledge_add_comment(): void { life_collection_add_comment('knowledge'); }
function knowledge_delete_comment(): void { life_collection_delete_comment('knowledge'); }

function ideas_index(): void { life_collection_index('ideas'); }
function ideas_create(): void { life_collection_create('ideas'); }
function ideas_edit(): void { life_collection_edit('ideas'); }
function ideas_view(): void { life_collection_view('ideas'); }
function ideas_delete(): void { life_collection_delete('ideas'); }
function ideas_link(): void { life_collection_link('ideas'); }
function ideas_unlink(): void { life_collection_unlink('ideas'); }
function ideas_add_comment(): void { life_collection_add_comment('ideas'); }
function ideas_delete_comment(): void { life_collection_delete_comment('ideas'); }

function diary_index(): void { life_collection_index('diary'); }
function diary_create(): void { life_collection_create('diary'); }
function diary_edit(): void { life_collection_edit('diary'); }
function diary_view(): void { life_collection_view('diary'); }
function diary_delete(): void { life_collection_delete('diary'); }
function diary_link(): void { life_collection_link('diary'); }
function diary_unlink(): void { life_collection_unlink('diary'); }
function diary_add_comment(): void { life_collection_add_comment('diary'); }
function diary_delete_comment(): void { life_collection_delete_comment('diary'); }

function documents_index(): void { life_collection_index('documents'); }
function documents_create(): void { life_collection_create('documents'); }
function documents_edit(): void { life_collection_edit('documents'); }
function documents_view(): void { life_collection_view('documents'); }
function documents_delete(): void { life_collection_delete('documents'); }
function documents_link(): void { life_collection_link('documents'); }
function documents_unlink(): void { life_collection_unlink('documents'); }
function documents_add_comment(): void { life_collection_add_comment('documents'); }
function documents_delete_comment(): void { life_collection_delete_comment('documents'); }

function development_index(): void { life_collection_index('development'); }
function development_create(): void { life_collection_create('development'); }
function development_edit(): void { life_collection_edit('development'); }
function development_view(): void { life_collection_view('development'); }
function development_delete(): void { life_collection_delete('development'); }
function development_link(): void { life_collection_link('development'); }
function development_unlink(): void { life_collection_unlink('development'); }
function development_add_comment(): void { life_collection_add_comment('development'); }
function development_delete_comment(): void { life_collection_delete_comment('development'); }

function automoveis_index(): void { life_collection_index('automoveis'); }
function automoveis_create(): void { life_collection_create('automoveis'); }
function automoveis_edit(): void { life_collection_edit('automoveis'); }
function automoveis_view(): void { life_collection_view('automoveis'); }
function automoveis_delete(): void { life_collection_delete('automoveis'); }
function automoveis_link(): void { life_collection_link('automoveis'); }
function automoveis_unlink(): void { life_collection_unlink('automoveis'); }
function automoveis_add_comment(): void { life_collection_add_comment('automoveis'); }
function automoveis_delete_comment(): void { life_collection_delete_comment('automoveis'); }
