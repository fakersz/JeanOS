<?php
/** Model: service_orders (ordens de serviço) */

function orders_all(array $filters = []): array
{
    $sql = 'SELECT so.*, c.name AS client_name,
                   d.category AS device_category, d.model AS device_model, d.model_code
            FROM service_orders so
            JOIN clients c ON c.id = so.client_id
            JOIN devices d ON d.id = so.device_id
            WHERE 1 = 1';
    $params = [];

    if (!empty($filters['status'])) {
        $sql .= ' AND so.status = :status';
        $params['status'] = $filters['status'];
    }
    if (!empty($filters['q'])) {
        $sql .= ' AND (so.title ILIKE :q OR c.name ILIKE :q OR d.model_code ILIKE :q
                       OR CAST(so.id AS TEXT) = :id_exact)';
        $params['q'] = '%' . $filters['q'] . '%';
        $params['id_exact'] = trim($filters['q'], '# ');
    }

    $sql .= " ORDER BY CASE so.priority
                  WHEN 'urgente' THEN 0 WHEN 'alta' THEN 1
                  WHEN 'normal' THEN 2 ELSE 3 END,
              so.opened_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function order_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT so.*, c.name AS client_name, c.phone AS client_phone,
                d.category AS device_category, d.model AS device_model,
                d.model_code, d.serial_number
         FROM service_orders so
         JOIN clients c ON c.id = so.client_id
         JOIN devices d ON d.id = so.device_id
         WHERE so.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** Deriva o status de pagamento a partir dos valores. */
function order_payment_status(float $total, float $paid): string
{
    if ($total > 0 && $paid >= $total) {
        return 'pago';
    }
    return $paid > 0 ? 'parcial' : 'pendente';
}

function order_create(array $data): int
{
    if (!client_find((int) $data['client_id']) || !device_find((int) $data['device_id'])) {
        throw new RuntimeException('Cliente ou equipamento inválido.');
    }

    $total = $data['labor_price'] + $data['parts_price'];
    $stmt = db()->prepare(
        'INSERT INTO service_orders
            (client_id, device_id, title, reported_issue, diagnosis, solution,
             status, priority, labor_price, parts_price, total_price,
             paid_amount, payment_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
    );
    $stmt->execute([
        $data['client_id'], $data['device_id'], $data['title'],
        $data['reported_issue'], $data['diagnosis'], $data['solution'],
        $data['status'], $data['priority'],
        $data['labor_price'], $data['parts_price'], $total,
        $data['paid_amount'], order_payment_status($total, $data['paid_amount']),
    ]);
    return (int) $stmt->fetchColumn();
}

function order_update(int $id, array $data): void
{
    if (!client_find((int) $data['client_id']) || !device_find((int) $data['device_id'])) {
        throw new RuntimeException('Cliente ou equipamento inválido.');
    }

    $total = $data['labor_price'] + $data['parts_price'];
    $stmt = db()->prepare(
        'UPDATE service_orders
         SET client_id = ?, device_id = ?, title = ?, reported_issue = ?,
             diagnosis = ?, solution = ?, status = ?, priority = ?,
             labor_price = ?, parts_price = ?, total_price = ?,
             paid_amount = ?, payment_status = ?,
             closed_at = CASE WHEN ? IN (\'entregue\', \'cancelada\')
                              THEN COALESCE(closed_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([
        $data['client_id'], $data['device_id'], $data['title'],
        $data['reported_issue'], $data['diagnosis'], $data['solution'],
        $data['status'], $data['priority'],
        $data['labor_price'], $data['parts_price'], $total,
        $data['paid_amount'], order_payment_status($total, $data['paid_amount']),
        $data['status'], $id,
    ]);
}

function order_set_status(int $id, string $status): void
{
    $stmt = db()->prepare(
        'UPDATE service_orders
         SET status = ?,
             closed_at = CASE WHEN ? IN (\'entregue\', \'cancelada\')
                              THEN COALESCE(closed_at, NOW()) ELSE NULL END
         WHERE id = ?'
    );
    $stmt->execute([$status, $status, $id]);
}

/**
 * Registra um pagamento na OS e cria o lançamento financeiro vinculado.
 */
function order_register_payment(int $id, float $amount): void
{
    $order = order_find($id); // já filtrado por empresa
    if (!$order || $amount <= 0) {
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $paid = (float) $order['paid_amount'] + $amount;
        $stmt = $pdo->prepare(
            'UPDATE service_orders SET paid_amount = ?, payment_status = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $paid, order_payment_status((float) $order['total_price'], $paid),
            $id,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO financial_entries
                (type, description, amount, category, reference_type, reference_id, entry_date)
             VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE)'
        );
        $stmt->execute([
            'entrada',
            'Pagamento OS #' . $id . ' — ' . $order['title'],
            $amount, 'Serviço', 'service_order', $id,
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function order_delete(int $id): void
{
    $stmt = db()->prepare('DELETE FROM service_orders WHERE id = ?');
    $stmt->execute([$id]);
}
