<?php
declare(strict_types=1);

define('PROJECT_ROOT', __DIR__ . '/..');
define('DATA_DIR', PROJECT_ROOT . '/data');

// Whitelist: solo estos comandos pueden ejecutarse desde el panel.
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

    // Services (systemctl)
    'ollama'            => ['cmd' => 'systemctl', 'args' => ['start', 'ollama']],
    'litellm'           => ['cmd' => 'systemctl', 'args' => ['start', 'litellm']],
    'jenkins'           => ['cmd' => 'systemctl', 'args' => ['start', 'jenkins']],
    'postgresql'        => ['cmd' => 'systemctl', 'args' => ['start', 'postgresql']],
    'apache2'           => ['cmd' => 'systemctl', 'args' => ['start', 'apache2']],
    'cups'              => ['cmd' => 'systemctl', 'args' => ['start', 'cups']],
]);

// Helper: check if a command key is whitelisted
function is_whitelisted(string $key): bool {
    return isset(COMMAND_WHITELIST[$key]);
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
