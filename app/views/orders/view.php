<?php
$remaining = max((float) $order['total_price'] - (float) $order['paid_amount'], 0);
?>
<div class="page-actions">
    <a href="/orders" class="btn btn-ghost">← Ordens de serviço</a>
    <div class="spacer"></div>
    <?php if (!in_array($order['status'], ['entregue', 'cancelada'], true)): ?>
        <form method="post" action="/orders/status" class="inline"
              data-confirm="Marcar a OS #<?= $order['id'] ?> como entregue?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <input type="hidden" name="status" value="entregue">
            <button type="submit" class="btn btn-success">✓ Marcar como entregue</button>
        </form>
    <?php endif; ?>
    <a href="/orders/pdf?id=<?= $order['id'] ?>" target="_blank" class="btn btn-ghost">📄 Baixar PDF</a>
    <a href="/orders/edit?id=<?= $order['id'] ?>" class="btn btn-ghost">Editar</a>
    <form method="post" action="/orders/delete" class="inline" data-confirm="Excluir definitivamente a OS #<?= $order['id'] ?>?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $order['id'] ?>">
        <button type="submit" class="btn btn-danger-ghost">Excluir</button>
    </form>
</div>

<div class="panel-grid">
    <section class="card">
        <div class="card-header">
            <h2><?= e($order['title']) ?></h2>
            <div class="badge-row">
                <?= status_badge($order['status']) ?>
                <?= priority_badge($order['priority']) ?>
                <?= payment_badge($order['payment_status']) ?>
            </div>
        </div>
        <dl class="detail-grid">
            <div><dt>Cliente</dt><dd><a href="/clients/view?id=<?= $order['client_id'] ?>" class="link"><?= e($order['client_name']) ?></a></dd></div>
            <div><dt>Telefone</dt><dd><?= e($order['client_phone'] ?: '—') ?></dd></div>
            <div><dt>Equipamento</dt><dd><?= e(trim($order['device_category'] . ' ' . ($order['device_model'] ?: ''))) ?></dd></div>
            <div><dt>Código / Série</dt><dd><?= e(trim(($order['model_code'] ?: '—') . ' · ' . ($order['serial_number'] ?: '—'), ' ·') ?: '—') ?></dd></div>
            <div><dt>Abertura</dt><dd><?= br_date($order['opened_at'], true) ?></dd></div>
            <div><dt>Fechamento</dt><dd><?= br_date($order['closed_at'], true) ?></dd></div>
            <div class="span-all"><dt>Defeito relatado</dt><dd><?= $order['reported_issue'] ? nl2br(e($order['reported_issue'])) : '—' ?></dd></div>
            <div class="span-all"><dt>Diagnóstico</dt><dd><?= $order['diagnosis'] ? nl2br(e($order['diagnosis'])) : '—' ?></dd></div>
            <div class="span-all"><dt>Solução</dt><dd><?= $order['solution'] ? nl2br(e($order['solution'])) : '—' ?></dd></div>
        </dl>

        <div class="card-header"><h2>Alterar status</h2></div>
        <form method="post" action="/orders/status" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <select name="status">
                <?php foreach (os_statuses() as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-ghost">Aplicar</button>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><h2>Valores</h2></div>
        <dl class="money-list">
            <div><dt>Mão de obra</dt><dd><?= money($order['labor_price']) ?></dd></div>
            <div><dt>Peças</dt><dd><?= money($order['parts_price']) ?></dd></div>
            <div class="money-total"><dt>Total</dt><dd><?= money($order['total_price']) ?></dd></div>
            <div><dt>Pago</dt><dd class="text-ok"><?= money($order['paid_amount']) ?></dd></div>
            <div><dt>Restante</dt><dd class="<?= $remaining > 0 ? 'text-danger' : 'text-ok' ?>"><?= money($remaining) ?></dd></div>
        </dl>

        <?php if ($remaining > 0): ?>
            <div class="card-header"><h2>Registrar pagamento</h2></div>
            <form method="post" action="/orders/pay" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <input type="number" step="0.01" min="0.01" max="<?= e(number_format($remaining, 2, '.', '')) ?>"
                       name="amount" value="<?= e(number_format($remaining, 2, '.', '')) ?>" required>
                <button type="submit" class="btn btn-primary">Receber</button>
            </form>
            <small class="hint">O valor é somado ao pago e lançado como entrada no financeiro.</small>
        <?php endif; ?>
    </section>
</div>
