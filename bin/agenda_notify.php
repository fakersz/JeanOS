#!/usr/bin/env php
<?php
/**
 * Notificações da Agenda via ntfy (https://ntfy.sh).
 * Avisa 1 dia antes e no dia de cada evento agendado.
 *
 * Uso:  php bin/agenda_notify.php            (rodado pelo cron)
 *       php bin/agenda_notify.php --test     (envia notificação de teste)
 *       php bin/agenda_notify.php --force    (ignora janela de horário)
 *
 * Configuração no .env: NTFY_TOPIC (obrigatório) e NTFY_SERVER (opcional).
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/models/Life.php';

$isTest = in_array('--test', $argv, true);
$force  = in_array('--force', $argv, true);

$ntfyServer = rtrim((string) env('NTFY_SERVER', 'https://ntfy.sh'), '/');
$ntfyTopic  = env('NTFY_TOPIC');
if (!$ntfyTopic) {
    fwrite(STDERR, "NTFY_TOPIC não configurado no .env — nada a fazer.\n");
    exit(1);
}

function notify_log(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
}

function ntfy_send(string $server, string $topic, string $title, string $message, int $priority = 3, array $tags = ['calendar']): bool
{
    $payload = json_encode([
        'topic'    => $topic,
        'title'    => $title,
        'message'  => $message,
        'priority' => $priority,
        'tags'     => $tags,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($server);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status >= 400) {
        notify_log("ERRO ao enviar para ntfy (HTTP $status): $error $response");
        return false;
    }
    return true;
}

/** Descrição curta do evento: hora, local e resumo. */
function notify_event_details(array $event): string
{
    $meta = life_metadata($event);
    $parts = [];
    $time = date('H:i', strtotime((string) $event['starts_at']));
    if ($time !== '00:00') {
        $parts[] = 'às ' . $time;
    }
    if (!empty($meta['location'])) {
        $parts[] = '📍 ' . $meta['location'];
    }
    if (!empty($event['summary'])) {
        $parts[] = (string) $event['summary'];
    }
    return $parts ? implode(' · ', $parts) : 'Sem mais detalhes.';
}

if ($isTest) {
    $ok = ntfy_send(
        $ntfyServer,
        $ntfyTopic,
        APP_NAME . ' — Agenda conectada 🎉',
        'As notificações da sua agenda estão funcionando. Você receberá avisos 1 dia antes e no dia de cada evento.',
        3,
        ['tada']
    );
    notify_log($ok ? 'Notificação de teste enviada para o tópico ' . $ntfyTopic : 'Falha no teste.');
    exit($ok ? 0 : 1);
}

// Evita notificar de madrugada: só envia a partir das 08:00.
if (!$force && (int) date('G') < 8) {
    exit(0);
}

$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$stmt = db()->prepare(
    "SELECT * FROM life_nodes
     WHERE type = 'agenda_event'
       AND status = 'scheduled'
       AND starts_at >= ?
       AND starts_at < ?
     ORDER BY starts_at"
);
$stmt->execute([$today . ' 00:00:00', date('Y-m-d', strtotime('+2 days')) . ' 00:00:00']);
$events = $stmt->fetchAll();

$flagUpdate = db()->prepare('UPDATE life_nodes SET metadata = metadata || ?::jsonb WHERE id = ?');
$sent = 0;

foreach ($events as $event) {
    $meta = life_metadata($event);
    $eventDay = date('Y-m-d', strtotime((string) $event['starts_at']));

    if ($eventDay === $tomorrow && empty($meta['ntfy_before_at'])) {
        if (ntfy_send($ntfyServer, $ntfyTopic, '📅 Amanhã: ' . $event['title'], notify_event_details($event), 3)) {
            $flagUpdate->execute([life_json(['ntfy_before_at' => date('Y-m-d H:i:s')]), $event['id']]);
            notify_log('Aviso de véspera enviado: #' . $event['id'] . ' ' . $event['title']);
            $sent++;
        }
    }

    if ($eventDay === $today && empty($meta['ntfy_day_at'])) {
        if (ntfy_send($ntfyServer, $ntfyTopic, '📅 Hoje: ' . $event['title'], notify_event_details($event), 4)) {
            $flagUpdate->execute([life_json(['ntfy_day_at' => date('Y-m-d H:i:s')]), $event['id']]);
            notify_log('Aviso do dia enviado: #' . $event['id'] . ' ' . $event['title']);
            $sent++;
        }
    }
}

if ($sent > 0) {
    notify_log("Total: $sent notificação(ões) enviada(s).");
}
