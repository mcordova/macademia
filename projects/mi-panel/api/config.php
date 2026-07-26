<?php
declare(strict_types=1);

define('PROJECT_ROOT', __DIR__ . '/..');
define('DATA_DIR', PROJECT_ROOT . '/data');

// Whitelist: solo estos comandos pueden ejecutarse desde el panel (solo apps, NO servicios).
// 'cmd' = comando del sistema, 'args' = argumentos permitidos (null = sin restricción)
define('COMMAND_WHITELIST', [
    // Editors
    'code'              => ['cmd' => 'code',              'args' => null],
    'cursor'            => ['cmd' => 'cursor',            'args' => null],
    'sublime_text'      => ['cmd' => 'subl',              'args' => null],
    'vim'               => ['cmd' => 'vim',               'args' => null],

    // Browsers
    'brave_browser'     => ['cmd' => 'brave-browser',     'args' => null],

    // Terminal tools
    'htop'              => ['cmd' => 'htop',              'args' => null],
    'btop'              => ['cmd' => 'btop',              'args' => null],
    'lnav'              => ['cmd' => 'lnav',              'args' => null],
    'gparted'           => ['cmd' => 'gparted',           'args' => null],

    // Docker
    'docker'            => ['cmd' => 'docker',            'args' => null],
    'docker_compose'    => ['cmd' => 'docker',            'args' => ['compose']],

    // Dev tools
    'node'              => ['cmd' => 'node',              'args' => null],
    'npm'               => ['cmd' => 'npm',               'args' => null],
    'git'               => ['cmd' => 'git',               'args' => null],
    'php'               => ['cmd' => 'php',               'args' => null],
    'python3'           => ['cmd' => 'python3',           'args' => null],

    // Multimedia
    'obs_studio'        => ['cmd' => 'obs',               'args' => null],
    'guvcview'          => ['cmd' => 'guvcview',          'args' => null],

    // System
    'screen'            => ['cmd' => 'screen',            'args' => null],

    // GIS
    'qgis'              => ['cmd' => 'qgis',              'args' => null],
]);

// Service definitions: maps command_key => systemd unit name.
// Used by service-control.php and logs.php.
define('SERVICES', [
    'ollama'     => ['unit' => 'ollama',      'log_unit' => 'ollama'],
    'litellm'    => ['unit' => 'litellm',     'log_unit' => 'litellm'],
    'jenkins'    => ['unit' => 'jenkins',     'log_unit' => 'jenkins'],
    'postgresql' => ['unit' => 'postgresql',  'log_unit' => 'postgresql'],
    'apache2'    => ['unit' => 'apache2',     'log_unit' => 'apache2'],
    'cups'       => ['unit' => 'cups',        'log_unit' => 'cups'],
]);

// Helper: check if a command key is whitelisted
function is_whitelisted(string $key): bool {
    return isset(COMMAND_WHITELIST[$key]);
}

// Helper: check if a key is a known service
function is_service(string $key): bool {
    return isset(SERVICES[$key]);
}

// CORS: allow local dev (only in web context)
if (php_sapi_name() !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
