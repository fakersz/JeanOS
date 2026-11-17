<?php
/** Controller: configurações pessoais */

require APP_PATH . '/models/User.php';
require APP_PATH . '/models/Life.php';

function settings_index(): void
{
    render('settings/index', [
        'title' => 'Configurações',
        'user' => current_user(),
        'nodeTypes' => life_node_types(),
        'entityTypes' => life_entity_types(),
    ]);
}
