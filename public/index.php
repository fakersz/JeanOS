<?php
/**
 * Peixoto LifeOS — front controller.
 * Rotas no formato /recurso/acao (ex: /clients/create, /orders/view?id=1).
 * Rotas públicas: /login.
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segments = array_values(array_filter(explode('/', $path)));

$resource = $segments[0] ?? 'home';
$action   = $segments[1] ?? 'index';

// Toda requisição POST exige token CSRF válido.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

$routes = [
    'login'      => 'auth',
    'logout'     => 'auth',
    'home'       => 'home',
    'dashboard'  => 'home',
    'brain'      => 'life',
    'agenda'     => 'life',
    'tasks'      => 'life',
    'projects'   => 'life',
    'knowledge'  => 'life',
    'ideas'      => 'life',
    'diary'      => 'life',
    'documents'  => 'life',
    'development'=> 'life',
    'automoveis' => 'life',
    'timeline'   => 'life',
    'settings'   => 'settings',
    'clients'    => 'clients',
    'devices'    => 'devices',
    'orders'     => 'orders',
    'inventory'  => 'inventory',
    'financial'  => 'financial',
    'schematics' => 'schematics',
    'trello'     => 'trello',
];

// Rotas acessíveis sem login.
$publicResources = ['login', 'logout'];

if (!isset($routes[$resource])) {
    http_response_code(404);
    require_login();
    render('errors/404', ['title' => 'Página não encontrada']);
}

if ($resource === 'login') {
    $action = 'login';
} elseif ($resource === 'logout') {
    $action = 'logout';
} elseif (!in_array($resource, $publicResources, true)) {
    require_login();
}

require APP_PATH . '/controllers/' . $routes[$resource] . '.php';

$handler = $routes[$resource] . '_' . str_replace('-', '_', $action);
if (!function_exists($handler)) {
    http_response_code(404);
    render('errors/404', ['title' => 'Página não encontrada']);
}

try {
    $handler();
} catch (PDOException $exception) {
    error_log('[iFixAdmin] ' . $exception->getMessage());
    http_response_code(500);
    render('errors/500', ['title' => 'Erro interno']);
}
