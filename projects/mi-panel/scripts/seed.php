<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/db.php';

$programsFile = data_path('programs.json');
$executionsFile = data_path('executions.json');

if (file_exists($programsFile)) {
    $existing = load_json('programs.json');
    $count = count($existing);
    if ($count > 0) {
        echo "Database already has {$count} programs. Skipping seed.\n";
        echo "Run with --force to re-seed.\n";
        if (!in_array('--force', $argv ?? [])) {
            exit(0);
        }
    }
}

$programs = [
    // ── Shells & Terminal ──
    ['name' => 'Alacritty',    'package' => 'alacritty',    'command_key' => 'alacritty',   'category' => 'Shells & Terminal', 'program_type' => 'gui',     'notes' => 'GPU-accelerated terminal emulator'],
    ['name' => 'Zsh',          'package' => 'zsh',           'command_key' => null,           'category' => 'Shells & Terminal', 'program_type' => 'terminal', 'notes' => 'Primary shell (launch via terminal)'],
    ['name' => 'Bat',          'package' => 'bat',           'command_key' => 'bat',          'category' => 'Shells & Terminal', 'program_type' => 'terminal', 'notes' => 'cat clone with syntax highlighting'],
    ['name' => 'Eza',          'package' => 'eza',           'command_key' => 'eza',          'category' => 'Shells & Terminal', 'program_type' => 'terminal', 'notes' => 'Modern ls replacement'],
    ['name' => 'Ripgrep',      'package' => 'ripgrep',       'command_key' => 'rg',           'category' => 'Shells & Terminal', 'program_type' => 'terminal', 'notes' => 'Fast grep replacement'],
    ['name' => 'Screen',       'package' => 'screen',        'command_key' => 'screen',       'category' => 'Shells & Terminal', 'program_type' => 'terminal', 'notes' => 'Terminal multiplexer'],

    // ── Editors & IDEs ──
    ['name' => 'Vim',          'package' => 'vim',           'command_key' => 'vim',           'category' => 'Editors & IDEs',   'program_type' => 'terminal', 'notes' => 'Terminal text editor'],
    ['name' => 'Sublime Text', 'package' => 'sublime-text',  'command_key' => 'sublime_text',  'category' => 'Editors & IDEs',   'program_type' => 'gui',      'notes' => 'GUI text editor'],
    ['name' => 'VS Code',      'package' => 'code',          'command_key' => 'code',          'category' => 'Editors & IDEs',   'program_type' => 'gui',      'notes' => 'Visual Studio Code'],
    ['name' => 'Cursor',       'package' => 'cursor',        'command_key' => 'cursor',        'category' => 'Editors & IDEs',   'program_type' => 'gui',      'notes' => 'AI-powered code editor'],

    // ── Development Tools ──
    ['name' => 'Git',          'package' => 'git',           'command_key' => 'git',           'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Version control'],
    ['name' => 'Make',         'package' => 'make',          'command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Build automation'],
    ['name' => 'Cargo',        'package' => 'cargo',         'command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Rust package manager'],
    ['name' => 'Rustc',        'package' => 'rustc',         'command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Rust compiler'],
    ['name' => 'Go',           'package' => 'golang',        'command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Go compiler'],
    ['name' => 'Node.js',      'package' => 'nodejs',        'command_key' => 'node',          'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'JavaScript runtime'],
    ['name' => 'npm',          'package' => 'npm',           'command_key' => 'npm',           'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Node.js package manager'],
    ['name' => 'OpenJDK 21',   'package' => 'openjdk-21-jre','command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Java runtime'],
    ['name' => 'Symfony CLI',  'package' => 'symfony-cli',   'command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'Symfony PHP framework CLI'],
    ['name' => 'OpenCode',     'package' => 'opencode',      'command_key' => null,            'category' => 'Dev Tools',        'program_type' => 'terminal', 'notes' => 'AI coding assistant'],

    // ── PHP ──
    ['name' => 'PHP 8.3',      'package' => 'php',           'command_key' => 'php',           'category' => 'PHP',              'program_type' => 'terminal', 'notes' => 'PHP runtime / CLI'],

    // ── Python ──
    ['name' => 'Python 3.12',  'package' => 'python3',       'command_key' => 'python3',       'category' => 'Python',           'program_type' => 'terminal', 'notes' => 'Python interpreter'],
    ['name' => 'pip',          'package' => 'python3-pip',   'command_key' => null,            'category' => 'Python',           'program_type' => 'terminal', 'notes' => 'Python package installer'],

    // ── Containers & Virtualization ──
    ['name' => 'Docker',       'package' => 'docker.io',     'command_key' => 'docker',        'category' => 'Containers',       'program_type' => 'terminal', 'notes' => 'Docker Engine'],
    ['name' => 'Docker Compose','package' => 'docker-compose-v2','command_key' => null,         'category' => 'Containers',       'program_type' => 'terminal', 'notes' => 'Docker Compose v2'],
    ['name' => 'VirtualBox',   'package' => 'virtualbox-7.2','command_key' => null,             'category' => 'Containers',       'program_type' => 'gui',      'notes' => 'Virtualization platform'],

    // ── Databases ──
    ['name' => 'PostgreSQL 16','package' => 'postgresql',    'command_key' => 'postgresql',    'category' => 'Databases',        'program_type' => 'service',  'notes' => 'PostgreSQL database server'],
    ['name' => 'LiteLLM DB',   'package' => null,            'command_key' => null,            'category' => 'Databases',        'program_type' => 'service',  'notes' => 'PostgreSQL for LiteLLM (Docker)'],

    // ── Web Browsers ──
    ['name' => 'Brave Browser','package' => 'brave-browser', 'command_key' => 'brave_browser', 'category' => 'Web Browsers',    'program_type' => 'gui',      'notes' => 'Privacy-focused Chromium browser'],

    // ── Multimedia & Graphics ──
    ['name' => 'OBS Studio',   'package' => 'obs-studio',    'command_key' => 'obs_studio',    'category' => 'Multimedia',       'program_type' => 'gui',      'notes' => 'Screen recording / streaming'],
    ['name' => 'guvcview',     'package' => 'guvcview',      'command_key' => 'guvcview',      'category' => 'Multimedia',       'program_type' => 'gui',      'notes' => 'Webcam viewer / capture'],

    // ── Office & Documents ──
    ['name' => 'PDF Chain',    'package' => 'pdfchain',      'command_key' => null,            'category' => 'Office & Docs',    'program_type' => 'gui',      'notes' => 'PDF editing tool'],

    // ── System & Hardware Monitoring ──
    ['name' => 'htop',         'package' => 'htop',          'command_key' => 'htop',          'category' => 'Monitoring',       'program_type' => 'terminal', 'notes' => 'Interactive process viewer'],
    ['name' => 'btop',         'package' => 'btop',          'command_key' => 'btop',          'category' => 'Monitoring',       'program_type' => 'terminal', 'notes' => 'Resource monitor (fancy htop)'],
    ['name' => 'Psensor',      'package' => 'psensor',       'command_key' => null,            'category' => 'Monitoring',       'program_type' => 'gui',      'notes' => 'Hardware temperature monitor'],
    ['name' => 'GParted',      'package' => 'gparted',       'command_key' => 'gparted',       'category' => 'Monitoring',       'program_type' => 'gui',      'notes' => 'Partition editor'],

    // ── System Utilities ──
    ['name' => 'jq',           'package' => 'jq',            'command_key' => 'jq',            'category' => 'System Utilities', 'program_type' => 'terminal', 'notes' => 'JSON processor'],
    ['name' => 'lnav',         'package' => 'lnav',          'command_key' => 'lnav',          'category' => 'System Utilities', 'program_type' => 'terminal', 'notes' => 'Log file navigator'],
    ['name' => 'ClipIt',       'package' => 'clipit',        'command_key' => null,            'category' => 'System Utilities', 'program_type' => 'gui',      'notes' => 'Lightweight clipboard manager'],

    // ── Geospatial ──
    ['name' => 'QGIS',         'package' => 'qgis',          'command_key' => 'qgis',          'category' => 'Geospatial',       'program_type' => 'gui',      'notes' => 'GIS desktop application'],

    // ── AI / LLM Services ──
    ['name' => 'Ollama',       'package' => null,            'command_key' => 'ollama',        'category' => 'AI / LLM',         'program_type' => 'service',  'notes' => 'Local LLM inference server'],
    ['name' => 'LiteLLM',      'package' => null,            'command_key' => 'litellm',       'category' => 'AI / LLM',         'program_type' => 'service',  'notes' => 'LLM proxy (routes to Ollama)'],

    // ── Web Server ──
    ['name' => 'Apache2',      'package' => null,            'command_key' => 'apache2',       'category' => 'Web Server',       'program_type' => 'service',  'notes' => 'HTTP/HTTPS server'],

    // ── CI/CD ──
    ['name' => 'Jenkins',      'package' => 'jenkins',       'command_key' => 'jenkins',       'category' => 'CI/CD',            'program_type' => 'service',  'notes' => 'CI/CD server on port 8080'],

    // ── System Services ──
    ['name' => 'CUPS',         'package' => null,            'command_key' => 'cups',          'category' => 'System Services',  'program_type' => 'service',  'notes' => 'Print scheduler'],
];

$id = 1;
foreach ($programs as &$p) {
    $p['id'] = $id++;
    $p['enabled'] = 1;
    $p['created_at'] = date('Y-m-d H:i:s');
}
unset($p);

save_json('programs.json', $programs);
save_json('executions.json', []);

echo "Seeded " . count($programs) . " programs into data/programs.json\n";
