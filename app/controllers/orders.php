<?php
/** Controller: ordens de serviço */

require APP_PATH . '/models/ServiceOrder.php';
require APP_PATH . '/models/Client.php';
require APP_PATH . '/models/Device.php';
require APP_PATH . '/models/Life.php';
require APP_PATH . '/controllers/order_pdf.php';

function orders_form_data(): array
{
    $status   = post_str('status', 'aberta');
    $priority = post_str('priority', 'normal');

    return [
        'client_id'      => post_int('client_id'),
        'device_id'      => post_int('device_id'),
        'title'          => post_str('title'),
        'reported_issue' => post_str('reported_issue'),
        'diagnosis'      => post_str('diagnosis'),
        'solution'       => post_str('solution'),
        'status'         => array_key_exists($status, os_statuses()) ? $status : 'aberta',
        'priority'       => array_key_exists($priority, os_priorities()) ? $priority : 'normal',
        'labor_price'    => post_num('labor_price'),
        'parts_price'    => post_num('parts_price'),
        'paid_amount'    => post_num('paid_amount'),
    ];
}

function orders_index(): void
{
    $filters = [
        'status' => (string) ($_GET['status'] ?? ''),
        'q'      => trim((string) ($_GET['q'] ?? '')),
    ];
    if (!array_key_exists($filters['status'], os_statuses())) {
        $filters['status'] = '';
    }

    render('orders/index', [
        'title'   => 'Ordens de Serviço',
        'orders'  => orders_all($filters),
        'filters' => $filters,
    ]);
}

function orders_create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = orders_form_data();
        if (!$data['client_id'] || !$data['device_id'] || !$data['title']) {
            flash('error', 'Cliente, equipamento e título são obrigatórios.');
        } else {
            $id = order_create($data);
            life_log_event('created', 'service_order', $id, 'Criou OS #' . $id . ': ' . $data['title']);
            flash('success', 'OS #' . $id . ' criada com sucesso.');
            redirect('/orders/view?id=' . $id);
        }
    }
    render('orders/form', [
        'title'   => 'Nova OS',
        'order'   => null,
        'clients' => clients_all(),
        'devices' => devices_all(),
        'preselectedClient' => get_int('client_id'),
        'preselectedDevice' => get_int('device_id'),
    ]);
}

function orders_edit(): void
{
    $order = order_find(get_int('id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = orders_form_data();
        if (!$data['client_id'] || !$data['device_id'] || !$data['title']) {
            flash('error', 'Cliente, equipamento e título são obrigatórios.');
        } else {
            order_update((int) $order['id'], $data);
            life_log_event('updated', 'service_order', (int) $order['id'], 'Atualizou OS #' . $order['id'] . ': ' . $data['title']);
            flash('success', 'OS atualizada.');
            redirect('/orders/view?id=' . $order['id']);
        }
    }

    render('orders/form', [
        'title'   => 'Editar OS #' . $order['id'],
        'order'   => $order,
        'clients' => clients_all(),
        'devices' => devices_all(),
        'preselectedClient' => null,
        'preselectedDevice' => null,
    ]);
}

function orders_view(): void
{
    $order = order_find(get_int('id', 0));
    if (!$order) {
        flash('error', 'OS não encontrada.');
        redirect('/orders');
    }

    render('orders/view', ['title' => 'OS #' . $order['id'], 'order' => $order]);
}

function orders_status(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/orders');
    }
    $id     = post_int('id', 0);
    $status = post_str('status', '');

    if ($id && array_key_exists($status, os_statuses())) {
        order_set_status($id, $status);
        life_log_event('updated', 'service_order', $id, 'Alterou status da OS #' . $id . ' para ' . status_label($status));
        flash('success', 'Status alterado para "' . status_label($status) . '".');
    }
    redirect('/orders/view?id=' . $id);
}

function orders_pay(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/orders');
    }
    $id     = post_int('id', 0);
    $amount = post_num('amount');

    if ($id && $amount > 0) {
        order_register_payment($id, $amount);
        life_log_event('paid', 'service_order', $id, 'Recebeu pagamento da OS #' . $id . ': ' . money($amount), null, [
            'amount' => $amount,
        ]);
        flash('success', 'Pagamento de ' . money($amount) . ' registrado e lançado no financeiro.');
    } else {
        flash('error', 'Informe um valor válido.');
    }
    redirect('/orders/view?id=' . $id);
}

function orders_delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/orders');
    }
    $id = post_int('id', 0);
    $order = $id ? order_find($id) : null;
    order_delete($id);
    if ($order) {
        life_log_event('deleted', 'service_order', $id, 'Removeu OS #' . $id . ': ' . $order['title']);
    }
    flash('success', 'OS excluída.');
    redirect('/orders');
}
