<?php
/**
 * Helpers globais: escape, CSRF, flash, formatação e renderização de views.
 */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Redireciona para a home se a requisição não for POST. */
function require_post(string $to = '/'): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($to);
    }
}

// ---- Flash messages ----
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ---- CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sessão expirada ou token inválido. Volte e tente novamente.');
    }
}

// ---- Entrada sanitizada ----
function post_str(string $key, ?string $default = null): ?string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    return $value === '' ? $default : $value;
}

function post_num(string $key): float
{
    $raw = str_replace(',', '.', (string) ($_POST[$key] ?? '0'));
    return is_numeric($raw) ? round((float) $raw, 2) : 0.0;
}

function post_int(string $key, ?int $default = null): ?int
{
    $raw = trim((string) ($_POST[$key] ?? ''));
    return ctype_digit($raw) ? (int) $raw : $default;
}

function get_int(string $key, ?int $default = null): ?int
{
    $raw = trim((string) ($_GET[$key] ?? ''));
    return ctype_digit($raw) ? (int) $raw : $default;
}

// ---- Formatação ----
function money(float|string|null $value): string
{
    return 'R$ ' . number_format((float) ($value ?? 0), 2, ',', '.');
}

function br_date(?string $value, bool $withTime = false): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '—';
}

/** Nome do mês em português (1 = janeiro). */
function br_month_name(int $month): string
{
    $names = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];
    return $names[$month] ?? '';
}

/** Nome do dia da semana em português (0 = domingo, como date('w')). */
function br_weekday_name(int $weekday): string
{
    $names = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    return $names[$weekday % 7] ?? '';
}

/** Data longa em português: "domingo, 13 de julho de 2026". */
function br_long_date(int $timestamp): string
{
    return br_weekday_name((int) date('w', $timestamp))
        . ', ' . (int) date('j', $timestamp)
        . ' de ' . br_month_name((int) date('n', $timestamp))
        . ' de ' . date('Y', $timestamp);
}

// ---- Domínio: rótulos e badges ----
function os_statuses(): array
{
    return [
        'aberta'               => 'Aberta',
        'diagnostico'          => 'Em diagnóstico',
        'aguardando_aprovacao' => 'Aguardando aprovação',
        'aguardando_peca'      => 'Aguardando peça',
        'em_reparo'            => 'Em reparo',
        'pronto'               => 'Pronto',
        'entregue'             => 'Entregue',
        'cancelada'            => 'Cancelada',
    ];
}

function os_priorities(): array
{
    return ['baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'];
}

function payment_statuses(): array
{
    return ['pendente' => 'Pendente', 'parcial' => 'Parcial', 'pago' => 'Pago'];
}

function device_categories(): array
{
    return ['MacBook', 'iMac', 'Mac mini', 'iPad', 'iPhone', 'Apple Watch', 'Outro'];
}

/** Categorias do catálogo de esquemas (mais granular que device_categories). */
function schematic_categories(): array
{
    return ['MacBook Pro', 'MacBook Air', 'MacBook (12")', 'iMac', 'Mac mini', 'Mac Pro', 'iPad', 'iPhone', 'Outro'];
}

function status_label(string $status): string
{
    return os_statuses()[$status] ?? ucfirst($status);
}

function status_badge(string $status): string
{
    return '<span class="badge badge-status-' . e($status) . '">' . e(status_label($status)) . '</span>';
}

function priority_badge(string $priority): string
{
    $label = os_priorities()[$priority] ?? ucfirst($priority);
    return '<span class="badge badge-priority-' . e($priority) . '">' . e($label) . '</span>';
}

function payment_badge(string $status): string
{
    $label = payment_statuses()[$status] ?? ucfirst($status);
    return '<span class="badge badge-pay-' . e($status) . '">' . e($label) . '</span>';
}

// ---- Markdown (subconjunto seguro) ----

/** Aplica formatação inline a um trecho JÁ escapado (negrito, itálico, código, links). */
function md_inline(string $text): string
{
    // Código inline `assim` — protegido primeiro para não sofrer outras regras.
    $text = preg_replace_callback('/`([^`]+)`/', static function ($m) {
        return '<code>' . $m[1] . '</code>';
    }, $text);

    // Links [texto](url) — apenas http(s) e mailto por segurança.
    $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+|mailto:[^\s)]+)\)/', static function ($m) {
        return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
    }, $text);

    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!\w)_([^_]+)_(?!\w)/', '<em>$1</em>', $text);

    return $text;
}

/** Renderiza um subconjunto seguro de Markdown para HTML (escapa tudo antes). */
function md(?string $source): string
{
    if ($source === null || trim($source) === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $source);
    $html = [];
    $listType = null;      // 'ul' | 'ol' | null
    $inCode = false;
    $paragraph = [];

    $flushParagraph = static function () use (&$paragraph, &$html) {
        if ($paragraph) {
            $html[] = '<p>' . md_inline(e(implode(' ', $paragraph))) . '</p>';
            $paragraph = [];
        }
    };
    $closeList = static function () use (&$listType, &$html) {
        if ($listType) {
            $html[] = '</' . $listType . '>';
            $listType = null;
        }
    };

    foreach ($lines as $line) {
        // Blocos de código ``` ```
        if (preg_match('/^\s*```/', $line)) {
            if ($inCode) {
                $html[] = '</code></pre>';
                $inCode = false;
            } else {
                $flushParagraph();
                $closeList();
                $html[] = '<pre><code>';
                $inCode = true;
            }
            continue;
        }
        if ($inCode) {
            $html[] = e($line);
            continue;
        }

        $trim = trim($line);

        if ($trim === '') {
            $flushParagraph();
            $closeList();
            continue;
        }

        // Títulos
        if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
            $flushParagraph();
            $closeList();
            $level = strlen($m[1]);
            $html[] = "<h$level>" . md_inline(e(trim($m[2]))) . "</h$level>";
            continue;
        }

        // Linha horizontal
        if (preg_match('/^(-{3,}|\*{3,})$/', $trim)) {
            $flushParagraph();
            $closeList();
            $html[] = '<hr>';
            continue;
        }

        // Citação
        if (preg_match('/^>\s?(.*)$/', $trim, $m)) {
            $flushParagraph();
            $closeList();
            $html[] = '<blockquote>' . md_inline(e($m[1])) . '</blockquote>';
            continue;
        }

        // Lista não ordenada
        if (preg_match('/^[-*+]\s+(.*)$/', $trim, $m)) {
            $flushParagraph();
            if ($listType !== 'ul') {
                $closeList();
                $html[] = '<ul>';
                $listType = 'ul';
            }
            $html[] = '<li>' . md_inline(e($m[1])) . '</li>';
            continue;
        }

        // Lista ordenada
        if (preg_match('/^\d+[.)]\s+(.*)$/', $trim, $m)) {
            $flushParagraph();
            if ($listType !== 'ol') {
                $closeList();
                $html[] = '<ol>';
                $listType = 'ol';
            }
            $html[] = '<li>' . md_inline(e($m[1])) . '</li>';
            continue;
        }

        // Parágrafo
        $closeList();
        $paragraph[] = $trim;
    }

    if ($inCode) {
        $html[] = '</code></pre>';
    }
    $flushParagraph();
    $closeList();

    return implode("\n", $html);
}

/** Cor determinística (0-5) para uma etiqueta, usada nas classes CSS. */
function trello_tag_variant(string $tag): int
{
    return crc32(mb_strtolower($tag)) % 6;
}

// ---- Assets ----

/**
 * URL de asset com versão (cache-busting): o nginx cacheia por 30 dias
 * como immutable, então cada alteração no arquivo muda a query string
 * e força o navegador a baixar a versão nova.
 */
function asset(string $path): string
{
    $file = BASE_PATH . '/public' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return $path . '?v=' . $version;
}

// ---- Views ----
function render(string $view, array $data = []): never
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require APP_PATH . '/views/layout.php';
    exit;
}

function render_bare(string $view, array $data = []): never
{
    extract($data, EXTR_SKIP);
    require APP_PATH . '/views/' . $view . '.php';
    exit;
}

function render_html(string $view, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/views/' . $view . '.php';
    return (string) ob_get_clean();
}
