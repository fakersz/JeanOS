<?php
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: 'home';
$currentResource = explode('/', $currentPath)[0] ?: 'home';
$activeRoute = $currentResource === 'dashboard' ? 'home' : $currentResource;

$icons = [
    'home'        => '<path d="M3 10.8 12 3l9 7.8"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/>',
    'brain'       => '<path d="M9 3a4 4 0 0 0-4 4v1a4 4 0 0 0 0 8v1a4 4 0 0 0 7 2.65A4 4 0 0 0 19 17v-1a4 4 0 0 0 0-8V7a4 4 0 0 0-7-2.65A3.98 3.98 0 0 0 9 3Z"/><path d="M12 4v16M8 8h2M14 8h2M8 14h2M14 14h2"/>',
    'agenda'      => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    'tasks'       => '<path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    'projects'    => '<path d="M3 7h7l2 3h9v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M3 7V5a2 2 0 0 1 2-2h4l2 4"/>',
    'knowledge'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>',
    'ideas'       => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M8.5 14.5a6 6 0 1 1 7 0c-.8.6-1.5 1.5-1.5 2.5h-4c0-1-.7-1.9-1.5-2.5Z"/>',
    'diary'       => '<path d="M4 3h12a3 3 0 0 1 3 3v15H7a3 3 0 0 1-3-3Z"/><path d="M8 7h7M8 11h7M8 15h4"/>',
    'documents'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
    'financial'   => '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'development' => '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/><path d="m14 4-4 16"/>',
    'automoveis'  => '<path d="M5 17h14l-1.4-6.3A3 3 0 0 0 14.7 8H9.3a3 3 0 0 0-2.9 2.7Z"/><circle cx="7.5" cy="17.5" r="2"/><circle cx="16.5" cy="17.5" r="2"/><path d="M5 13h14"/>',
    'clients'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'devices'     => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
    'orders'      => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
    'inventory'   => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/>',
    'schematics'  => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
    'settings'    => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1 .6l-.08.08a2 2 0 1 1-3.84 0L10 20a1.8 1.8 0 0 0-1-.6 1.8 1.8 0 0 0-1.98.36l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.8 1.8 0 0 0 4.6 15a1.8 1.8 0 0 0-.6-1l-.08-.08a2 2 0 1 1 0-3.84L4 10a1.8 1.8 0 0 0 .6-1 1.8 1.8 0 0 0-.36-1.98l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.8 1.8 0 0 0 9 4.6a1.8 1.8 0 0 0 1-.6l.08-.08a2 2 0 1 1 3.84 0L14 4a1.8 1.8 0 0 0 1 .6 1.8 1.8 0 0 0 1.98-.36l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.8 1.8 0 0 0 19.4 9c.2.35.4.68.6 1l.08.08a2 2 0 1 1 0 3.84L20 14c-.2.32-.4.65-.6 1Z"/>',
];

$menuGroups = [
    'LifeOS' => [
        ['home', 'Home'],
        ['brain', 'Brain'],
        ['agenda', 'Agenda'],
        ['tasks', 'Tasks'],
        ['projects', 'Projetos'],
        ['knowledge', 'Knowledge'],
        ['ideas', 'Ideias'],
        ['diary', 'Diário'],
        ['documents', 'Documentos'],
    ],
    'Operação' => [
        ['financial', 'Financeiro'],
        ['development', 'Desenvolvimento'],
        ['automoveis', 'Automóveis'],
    ],
    'Assistência Técnica' => [
        ['clients', 'Clientes'],
        ['devices', 'Equipamentos'],
        ['orders', 'Ordens de Serviço'],
        ['inventory', 'Estoque'],
        ['schematics', 'Esquemas Elétricos'],
    ],
    'Sistema' => [
        ['settings', 'Configurações'],
    ],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= e(asset('/assets/img/logo.png')) ?>" alt="Peixoto OS" class="brand-logo">
        <div>
            <div class="brand-name">Peixoto<span>OS</span></div>
            <div class="brand-sub">Digital Brain</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($menuGroups as $group => $items): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= e($group) ?></div>
                <?php foreach ($items as [$route, $label]): ?>
                    <a href="/<?= $route ?>" class="nav-item<?= $activeRoute === $route ? ' active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icons[$route] ?></svg>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/logout" class="nav-item nav-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span>Sair</span>
        </a>
    </div>
</aside>
