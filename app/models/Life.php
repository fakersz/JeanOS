<?php
/** Model: LifeOS — nós conectados, relações, timeline e índice de IA. */

function life_node_types(): array
{
    return [
        'note'         => 'Nota',
        'knowledge'    => 'Conhecimento',
        'idea'         => 'Ideia',
        'diary'        => 'Diário',
        'document'     => 'Documento',
        'project'      => 'Projeto',
        'task'         => 'Tarefa',
        'agenda_event' => 'Agenda',
        'vehicle'      => 'Automóvel',
    ];
}

function life_entity_types(): array
{
    return life_node_types() + [
        'client'          => 'Cliente',
        'device'          => 'Equipamento',
        'service_order'   => 'OS',
        'financial_entry' => 'Financeiro',
        'inventory_item'  => 'Estoque',
        'schematic'       => 'Esquema',
    ];
}

function life_task_statuses(): array
{
    return [
        'backlog'  => 'Backlog',
        'today'    => 'Hoje',
        'doing'    => 'Em foco',
        'waiting'  => 'Aguardando',
        'done'     => 'Concluído',
    ];
}

function life_project_statuses(): array
{
    return [
        'active'  => 'Ativo',
        'paused'  => 'Pausado',
        'waiting' => 'Aguardando',
        'done'    => 'Finalizado',
    ];
}

function life_event_statuses(): array
{
    return [
        'scheduled' => 'Agendado',
        'done'      => 'Concluído',
        'canceled'  => 'Cancelado',
    ];
}

function life_vehicle_statuses(): array
{
    return [
        'active'      => 'Ativo',
        'maintenance' => 'Manutenção',
        'sold'        => 'Vendido',
    ];
}

function life_priorities(): array
{
    return ['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
}

function life_default_status(string $type): ?string
{
    return match ($type) {
        'task'         => 'backlog',
        'project'      => 'active',
        'agenda_event' => 'scheduled',
        'vehicle'      => 'active',
        default        => null,
    };
}

function life_statuses_for(string $type): array
{
    return match ($type) {
        'task'         => life_task_statuses(),
        'project'      => life_project_statuses(),
        'agenda_event' => life_event_statuses(),
        'vehicle'      => life_vehicle_statuses(),
        default        => [],
    };
}

function life_type_label(string $type): string
{
    return life_entity_types()[$type] ?? ucfirst(str_replace('_', ' ', $type));
}

function life_status_label(?string $status, string $type = ''): string
{
    if (!$status) {
        return '—';
    }
    return life_statuses_for($type)[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function life_priority_label(?string $priority): string
{
    if (!$priority) {
        return '—';
    }
    return life_priorities()[$priority] ?? ucfirst($priority);
}

function life_priority_badge(?string $priority): string
{
    if (!$priority) {
        return '';
    }
    return '<span class="badge life-priority life-priority-' . e($priority) . '">' . e(life_priority_label($priority)) . '</span>';
}

function life_status_badge(?string $status, string $type): string
{
    if (!$status) {
        return '';
    }
    return '<span class="badge life-status life-status-' . e($status) . '">' . e(life_status_label($status, $type)) . '</span>';
}

function life_tags_list(?string $raw): array
{
    if (!$raw) {
        return [];
    }
    $tags = array_map('trim', explode(',', $raw));
    $tags = array_filter($tags, static fn ($tag) => $tag !== '');
    return array_values(array_unique($tags));
}

function life_normalize_tags(?string $raw): ?string
{
    $tags = life_tags_list($raw);
    return $tags ? implode(', ', $tags) : null;
}

function life_metadata(array $node): array
{
    $raw = $node['metadata'] ?? [];
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function life_json(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
}

function life_clean_datetime(?string $raw): ?string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    $raw = str_replace('T', ' ', $raw);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        $raw .= ' 00:00:00';
    }
    $ts = strtotime($raw);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

function life_next_position(string $type, ?string $status = null): int
{
    $sql = 'SELECT COALESCE(MAX(position) + 1, 0) FROM life_nodes WHERE type = ?';
    $params = [$type];
    if ($status !== null) {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function life_nodes_all(array $filters = []): array
{
    $sql = "SELECT n.*,
               (SELECT COUNT(*) FROM entity_links l
                WHERE (l.source_type = n.type AND l.source_id = n.id)
                   OR (l.target_type = n.type AND l.target_id = n.id)) AS relation_count,
               (SELECT COUNT(*) FROM life_comments lc WHERE lc.node_id = n.id) AS comment_count
            FROM life_nodes n
            WHERE 1 = 1";
    $params = [];

    if (!empty($filters['type'])) {
        $types = is_array($filters['type']) ? $filters['type'] : [$filters['type']];
        $placeholders = [];
        foreach (array_values($types) as $i => $type) {
            $key = 'type_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $type;
        }
        $sql .= ' AND n.type IN (' . implode(', ', $placeholders) . ')';
    }
    if (array_key_exists('status', $filters) && $filters['status'] !== '' && $filters['status'] !== null) {
        $sql .= ' AND n.status = :status';
        $params['status'] = $filters['status'];
    }
    if (!empty($filters['category'])) {
        $sql .= ' AND LOWER(n.category) = LOWER(:category)';
        $params['category'] = $filters['category'];
    }
    if (!empty($filters['favorite'])) {
        $sql .= ' AND n.favorite = TRUE';
    }
    if (!empty($filters['q'])) {
        $sql .= " AND (
            to_tsvector('simple',
                COALESCE(n.title, '') || ' ' ||
                COALESCE(n.summary, '') || ' ' ||
                COALESCE(n.body, '') || ' ' ||
                COALESCE(n.category, '') || ' ' ||
                COALESCE(n.tags, '')
            ) @@ plainto_tsquery('simple', :qts)
            OR (COALESCE(n.title, '') || ' ' || COALESCE(n.summary, '') || ' ' ||
                COALESCE(n.body, '') || ' ' || COALESCE(n.category, '') || ' ' ||
                COALESCE(n.tags, '')) ILIKE :qlike
        )";
        $params['qts'] = $filters['q'];
        $params['qlike'] = '%' . $filters['q'] . '%';
    }

    $order = $filters['order'] ?? 'updated';
    $sql .= match ($order) {
        'agenda' => ' ORDER BY n.starts_at NULLS LAST, n.created_at DESC',
        'due'    => ' ORDER BY n.due_at NULLS LAST, n.position, n.updated_at DESC',
        'tasks'  => " ORDER BY CASE n.status
                         WHEN 'today' THEN 0 WHEN 'doing' THEN 1
                         WHEN 'waiting' THEN 2 WHEN 'backlog' THEN 3
                         WHEN 'done' THEN 4 ELSE 5 END, n.position, n.due_at NULLS LAST",
        'oldest' => ' ORDER BY n.created_at ASC',
        default  => ' ORDER BY n.favorite DESC, n.updated_at DESC',
    };

    if (!empty($filters['limit'])) {
        $sql .= ' LIMIT ' . max(1, min(200, (int) $filters['limit']));
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function life_node_find(int $id, ?string $type = null): ?array
{
    $sql = 'SELECT * FROM life_nodes WHERE id = ?';
    $params = [$id];
    if ($type !== null) {
        $sql .= ' AND type = ?';
        $params[] = $type;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

function life_node_count(array $filters = []): int
{
    $sql = 'SELECT COUNT(*) FROM life_nodes WHERE 1 = 1';
    $params = [];
    if (!empty($filters['type'])) {
        $sql .= ' AND type = :type';
        $params['type'] = $filters['type'];
    }
    if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
        $sql .= ' AND status = :status';
        $params['status'] = $filters['status'];
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function life_node_create(array $data): int
{
    $type = $data['type'];
    $status = $data['status'] ?? life_default_status($type);
    $position = $data['position'] ?? life_next_position($type, $status);

    $stmt = db()->prepare(
        'INSERT INTO life_nodes
            (type, title, summary, body, status, priority, category, tags, favorite,
             source_url, file_path, mime_type, file_size, starts_at, due_at,
             completed_at, time_minutes, position, metadata)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb)
         RETURNING id'
    );
    $stmt->execute([
        $type,
        $data['title'],
        $data['summary'] ?? null,
        $data['body'] ?? null,
        $status,
        $data['priority'] ?? null,
        $data['category'] ?? null,
        life_normalize_tags($data['tags'] ?? null),
        !empty($data['favorite']) ? 'true' : 'false',
        $data['source_url'] ?? null,
        $data['file_path'] ?? null,
        $data['mime_type'] ?? null,
        $data['file_size'] ?? null,
        $data['starts_at'] ?? null,
        $data['due_at'] ?? null,
        $data['completed_at'] ?? null,
        max(0, (int) ($data['time_minutes'] ?? 0)),
        $position,
        life_json($data['metadata'] ?? []),
    ]);
    $id = (int) $stmt->fetchColumn();
    life_log_event('created', $type, $id, 'Criou ' . mb_strtolower(life_type_label($type)) . ': ' . $data['title']);
    life_ai_index_node($id);
    return $id;
}

function life_node_update(int $id, array $data): void
{
    $node = life_node_find($id);
    if (!$node) {
        return;
    }

    $stmt = db()->prepare(
        'UPDATE life_nodes
         SET title = ?, summary = ?, body = ?, status = ?, priority = ?, category = ?,
             tags = ?, favorite = ?, source_url = ?, file_path = ?, mime_type = ?,
             file_size = ?, starts_at = ?, due_at = ?, completed_at = ?,
             time_minutes = ?, metadata = ?::jsonb, updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([
        $data['title'],
        $data['summary'] ?? null,
        $data['body'] ?? null,
        $data['status'] ?? null,
        $data['priority'] ?? null,
        $data['category'] ?? null,
        life_normalize_tags($data['tags'] ?? null),
        !empty($data['favorite']) ? 'true' : 'false',
        $data['source_url'] ?? null,
        $data['file_path'] ?? $node['file_path'],
        $data['mime_type'] ?? $node['mime_type'],
        $data['file_size'] ?? $node['file_size'],
        $data['starts_at'] ?? null,
        $data['due_at'] ?? null,
        $data['completed_at'] ?? null,
        max(0, (int) ($data['time_minutes'] ?? 0)),
        life_json($data['metadata'] ?? []),
        $id,
    ]);
    life_log_event('updated', (string) $node['type'], $id, 'Atualizou ' . mb_strtolower(life_type_label((string) $node['type'])) . ': ' . $data['title']);
    life_ai_index_node($id);
}

function life_node_delete(int $id): void
{
    $node = life_node_find($id);
    if (!$node) {
        return;
    }
    if ($node['file_path']) {
        $file = UPLOADS_PATH . '/' . basename((string) $node['file_path']);
        if (is_file($file)) {
            unlink($file);
        }
    }
    $stmt = db()->prepare('DELETE FROM life_nodes WHERE id = ?');
    $stmt->execute([$id]);
    $stmt = db()->prepare(
        'DELETE FROM entity_links
         WHERE (source_type = ? AND source_id = ?) OR (target_type = ? AND target_id = ?)'
    );
    $stmt->execute([$node['type'], $id, $node['type'], $id]);
    $stmt = db()->prepare('DELETE FROM ai_context_sources WHERE entity_type = ? AND entity_id = ?');
    $stmt->execute([$node['type'], $id]);
    life_log_event('deleted', (string) $node['type'], $id, 'Removeu ' . mb_strtolower(life_type_label((string) $node['type'])) . ': ' . $node['title']);
}

function life_task_move(int $taskId, string $status, array $order = []): void
{
    if (!array_key_exists($status, life_task_statuses())) {
        return;
    }
    $task = life_node_find($taskId, 'task');
    if (!$task) {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE life_nodes
             SET status = ?,
                 completed_at = CASE WHEN ? = 'done' THEN COALESCE(completed_at, NOW()) ELSE NULL END,
                 updated_at = NOW()
             WHERE id = ? AND type = 'task'"
        );
        $stmt->execute([$status, $status, $taskId]);

        $upd = $pdo->prepare("UPDATE life_nodes SET position = ?, updated_at = NOW() WHERE id = ? AND type = 'task' AND status = ?");
        $pos = 0;
        foreach ($order as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $upd->execute([$pos++, $id, $status]);
            }
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    life_log_event('moved', 'task', $taskId, 'Moveu tarefa para ' . life_status_label($status, 'task') . ': ' . $task['title']);
    life_ai_index_node($taskId);
}

function life_comments_for(int $nodeId): array
{
    $stmt = db()->prepare('SELECT * FROM life_comments WHERE node_id = ? ORDER BY created_at DESC');
    $stmt->execute([$nodeId]);
    return $stmt->fetchAll();
}

function life_comment_create(int $nodeId, string $body): void
{
    if (!life_node_find($nodeId)) {
        return;
    }
    $stmt = db()->prepare('INSERT INTO life_comments (node_id, body) VALUES (?, ?)');
    $stmt->execute([$nodeId, $body]);
    $node = life_node_find($nodeId);
    if ($node) {
        life_log_event('commented', (string) $node['type'], $nodeId, 'Comentou em ' . $node['title']);
    }
}

function life_comment_delete(int $commentId): void
{
    $stmt = db()->prepare('DELETE FROM life_comments WHERE id = ?');
    $stmt->execute([$commentId]);
}

function life_entity_exists(string $type, int $id): bool
{
    if ($id <= 0) {
        return false;
    }
    if (array_key_exists($type, life_node_types())) {
        $stmt = db()->prepare('SELECT 1 FROM life_nodes WHERE id = ? AND type = ?');
        $stmt->execute([$id, $type]);
        return (bool) $stmt->fetchColumn();
    }

    $table = match ($type) {
        'client'          => 'clients',
        'device'          => 'devices',
        'service_order'   => 'service_orders',
        'financial_entry' => 'financial_entries',
        'inventory_item'  => 'inventory',
        'schematic'       => 'schematics',
        default           => null,
    };
    if ($table === null) {
        return false;
    }
    $stmt = db()->prepare("SELECT 1 FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function life_entity_title(string $type, int $id): string
{
    if (array_key_exists($type, life_node_types())) {
        $stmt = db()->prepare('SELECT title FROM life_nodes WHERE id = ? AND type = ?');
        $stmt->execute([$id, $type]);
        return (string) ($stmt->fetchColumn() ?: life_type_label($type) . ' #' . $id);
    }

    $sql = match ($type) {
        'client'          => 'SELECT name FROM clients WHERE id = ?',
        'device'          => "SELECT TRIM(COALESCE(category, '') || ' ' || COALESCE(model_code, '') || ' ' || COALESCE(model, '')) FROM devices WHERE id = ?",
        'service_order'   => "SELECT '#OS ' || id || ' — ' || title FROM service_orders WHERE id = ?",
        'financial_entry' => "SELECT description FROM financial_entries WHERE id = ?",
        'inventory_item'  => 'SELECT name FROM inventory WHERE id = ?',
        'schematic'       => 'SELECT title FROM schematics WHERE id = ?',
        default           => null,
    };
    if ($sql === null) {
        return life_type_label($type) . ' #' . $id;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return trim((string) ($stmt->fetchColumn() ?: life_type_label($type) . ' #' . $id));
}

function life_entity_url(string $type, int $id): string
{
    return match ($type) {
        'note'            => '/brain/view?id=' . $id,
        'knowledge'       => '/knowledge/view?id=' . $id,
        'idea'            => '/ideas/view?id=' . $id,
        'diary'           => '/diary/view?id=' . $id,
        'document'        => '/documents/view?id=' . $id,
        'project'         => '/projects/view?id=' . $id,
        'task'            => '/tasks/view?id=' . $id,
        'agenda_event'    => '/agenda/view?id=' . $id,
        'vehicle'         => '/automoveis/view?id=' . $id,
        'client'          => '/clients/view?id=' . $id,
        'device'          => '/devices/edit?id=' . $id,
        'service_order'   => '/orders/view?id=' . $id,
        'financial_entry' => '/financial',
        'inventory_item'  => '/inventory/edit?id=' . $id,
        'schematic'       => '/schematics/view?id=' . $id,
        default           => '#',
    };
}

function life_links_for(string $type, int $id): array
{
    $stmt = db()->prepare(
        'SELECT * FROM entity_links
         WHERE (source_type = ? AND source_id = ?)
            OR (target_type = ? AND target_id = ?)
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute([$type, $id, $type, $id]);

    $links = [];
    foreach ($stmt->fetchAll() as $row) {
        $isSource = $row['source_type'] === $type && (int) $row['source_id'] === $id;
        $otherType = $isSource ? $row['target_type'] : $row['source_type'];
        $otherId = (int) ($isSource ? $row['target_id'] : $row['source_id']);
        $links[] = $row + [
            'other_type'  => $otherType,
            'other_id'    => $otherId,
            'other_label' => life_type_label($otherType),
            'other_title' => life_entity_title($otherType, $otherId),
            'other_url'   => life_entity_url($otherType, $otherId),
        ];
    }
    return $links;
}

function life_link_create(
    string $sourceType,
    int $sourceId,
    string $targetType,
    int $targetId,
    string $relationType = 'relacionado',
    ?string $description = null
): void {
    if (!life_entity_exists($sourceType, $sourceId) || !life_entity_exists($targetType, $targetId)) {
        throw new RuntimeException('A entidade informada para relacionamento não existe.');
    }
    if ($sourceType === $targetType && $sourceId === $targetId) {
        throw new RuntimeException('Uma entidade não pode se relacionar com ela mesma.');
    }

    $stmt = db()->prepare(
        'INSERT INTO entity_links
            (source_type, source_id, target_type, target_id, relation_type, description)
         VALUES (?, ?, ?, ?, ?, ?)
         ON CONFLICT (source_type, source_id, target_type, target_id, relation_type) DO NOTHING'
    );
    $stmt->execute([
        $sourceType,
        $sourceId,
        $targetType,
        $targetId,
        $relationType ?: 'relacionado',
        $description,
    ]);
    life_log_event(
        'linked',
        $sourceType,
        $sourceId,
        'Relacionou ' . life_entity_title($sourceType, $sourceId) . ' com ' . life_entity_title($targetType, $targetId)
    );
}

function life_link_delete(int $linkId, string $type, int $id): void
{
    $stmt = db()->prepare(
        'DELETE FROM entity_links
         WHERE id = ?
           AND ((source_type = ? AND source_id = ?) OR (target_type = ? AND target_id = ?))'
    );
    $stmt->execute([$linkId, $type, $id, $type, $id]);
}

function life_replace_project_link(string $sourceType, int $sourceId, ?int $projectId): void
{
    $stmt = db()->prepare(
        "DELETE FROM entity_links
         WHERE source_type = ? AND source_id = ?
           AND target_type = 'project' AND relation_type = 'project'"
    );
    $stmt->execute([$sourceType, $sourceId]);

    if ($projectId && life_entity_exists('project', $projectId)) {
        life_link_create($sourceType, $sourceId, 'project', $projectId, 'project');
    }
}

function life_project_options(): array
{
    return life_nodes_all(['type' => 'project', 'order' => 'updated', 'limit' => 200]);
}

function life_selected_project_id(string $type, int $id): ?int
{
    $stmt = db()->prepare(
        "SELECT target_id FROM entity_links
         WHERE source_type = ? AND source_id = ?
           AND target_type = 'project' AND relation_type = 'project'
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$type, $id]);
    $value = $stmt->fetchColumn();
    return $value ? (int) $value : null;
}

function life_log_event(
    string $eventType,
    string $entityType,
    ?int $entityId,
    string $title,
    ?string $body = null,
    array $metadata = []
): void {
    try {
        $user = function_exists('current_user') ? current_user() : null;
        $stmt = db()->prepare(
            'INSERT INTO life_events
                (actor_user_id, event_type, entity_type, entity_id, title, body, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?::jsonb)'
        );
        $stmt->execute([
            $user['id'] ?? null,
            $eventType,
            $entityType,
            $entityId,
            $title,
            $body,
            life_json($metadata),
        ]);
    } catch (Throwable $exception) {
        error_log('[LifeOS timeline] ' . $exception->getMessage());
    }
}

function life_timeline(int $limit = 80): array
{
    $stmt = db()->prepare(
        'SELECT le.*, u.name AS actor_name
         FROM life_events le
         LEFT JOIN users u ON u.id = le.actor_user_id
         ORDER BY le.occurred_at DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, max(1, min(200, $limit)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function life_ai_index_node(int $nodeId): void
{
    try {
        $node = life_node_find($nodeId);
        if (!$node) {
            return;
        }
        $metadata = life_metadata($node);
        $content = trim(implode("\n\n", array_filter([
            $node['summary'] ?? '',
            $node['body'] ?? '',
            $node['category'] ? 'Categoria: ' . $node['category'] : '',
            $node['tags'] ? 'Tags: ' . $node['tags'] : '',
            $metadata ? 'Metadados: ' . life_json($metadata) : '',
        ])));
        $content = $content !== '' ? $content : (string) $node['title'];
        $hash = hash('sha256', $node['title'] . "\n" . $content);

        $stmt = db()->prepare(
            'INSERT INTO ai_context_sources
                (entity_type, entity_id, title, content, metadata, content_hash)
             VALUES (?, ?, ?, ?, ?::jsonb, ?)
             ON CONFLICT (entity_type, entity_id) DO UPDATE
             SET title = EXCLUDED.title,
                 content = EXCLUDED.content,
                 metadata = EXCLUDED.metadata,
                 content_hash = EXCLUDED.content_hash,
                 indexed_at = NOW()'
        );
        $stmt->execute([
            $node['type'],
            $nodeId,
            $node['title'],
            $content,
            life_json($metadata),
            $hash,
        ]);
    } catch (Throwable $exception) {
        error_log('[LifeOS AI index] ' . $exception->getMessage());
    }
}

function life_agenda_events_between(string $start, string $end): array
{
    $stmt = db()->prepare(
        "SELECT * FROM life_nodes
         WHERE type = 'agenda_event'
           AND starts_at >= ?
           AND starts_at < ?
         ORDER BY starts_at, created_at"
    );
    $stmt->execute([$start, $end]);
    return $stmt->fetchAll();
}

function life_agenda_upcoming(int $limit = 10): array
{
    $stmt = db()->prepare(
        "SELECT * FROM life_nodes
         WHERE type = 'agenda_event'
           AND starts_at >= CURRENT_DATE
           AND status IS DISTINCT FROM 'canceled'
         ORDER BY starts_at, created_at
         LIMIT ?"
    );
    $stmt->bindValue(1, max(1, min(50, $limit)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function life_today_agenda(): array
{
    $stmt = db()->prepare(
        "SELECT * FROM life_nodes
         WHERE type = 'agenda_event'
           AND starts_at >= CURRENT_DATE
           AND starts_at < CURRENT_DATE + INTERVAL '1 day'
         ORDER BY starts_at NULLS LAST, created_at DESC
         LIMIT 8"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function life_upcoming_agenda(): array
{
    $stmt = db()->prepare(
        "SELECT * FROM life_nodes
         WHERE type = 'agenda_event'
           AND starts_at >= CURRENT_DATE
           AND status IS DISTINCT FROM 'canceled'
         ORDER BY starts_at NULLS LAST, created_at DESC
         LIMIT 8"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function life_overdue_tasks(): array
{
    $stmt = db()->prepare(
        "SELECT * FROM life_nodes
         WHERE type = 'task'
           AND due_at < CURRENT_DATE
           AND status IS DISTINCT FROM 'done'
         ORDER BY due_at ASC, priority DESC
         LIMIT 8"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function life_focus_tasks(): array
{
    $stmt = db()->prepare(
        "SELECT * FROM life_nodes
         WHERE type = 'task'
           AND status IN ('today', 'doing')
         ORDER BY CASE status WHEN 'doing' THEN 0 ELSE 1 END, position, due_at NULLS LAST
         LIMIT 8"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function life_active_projects(): array
{
    return life_nodes_all(['type' => 'project', 'status' => 'active', 'order' => 'updated', 'limit' => 6]);
}

function life_recent_nodes(array $types, int $limit = 8): array
{
    return life_nodes_all(['type' => $types, 'order' => 'updated', 'limit' => $limit]);
}
