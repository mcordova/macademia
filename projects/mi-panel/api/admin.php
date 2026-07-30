<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// ── CORS (for local dev) ──
if (php_sapi_name() !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

$programs = load_json('programs.json');

// ── GET: list all programs (including disabled) ──
if ($method === 'GET') {
    $result = $programs;

    if (!empty($_GET['enabled_only']) && $_GET['enabled_only'] === '1') {
        $result = array_filter($result, fn($p) => $p['enabled']);
    }

    usort($result, fn($a, $b) => $a['id'] - $b['id']);
    echo json_encode(array_values($result));
    exit;
}

// ── POST: create or scan ──
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';

    if ($action === 'scan') {
        handle_scan($programs);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['name']) || empty($input['program_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, program_type']);
        exit;
    }

    $newProgram = [
        'id'           => next_id($programs),
        'name'         => $input['name'],
        'package'      => $input['package'] ?? null,
        'command_key'  => $input['command_key'] ?? null,
        'category'     => $input['category'] ?? 'Other',
        'program_type' => $input['program_type'],
        'notes'        => $input['notes'] ?? null,
        'enabled'      => $input['enabled'] ?? 1,
        'created_at'   => date('Y-m-d H:i:s'),
    ];

    $programs[] = $newProgram;
    save_json('programs.json', $programs);

    http_response_code(201);
    echo json_encode(['id' => $newProgram['id'], 'message' => 'Program created']);
    exit;
}

// ── PUT: update program by id ──
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($_GET['id'] ?? $input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid "id"']);
        exit;
    }

    $idx = array_search($id, array_column($programs, 'id'));
    if ($idx === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Program not found']);
        exit;
    }

    $updatable = ['name', 'package', 'command_key', 'category', 'program_type', 'notes', 'enabled'];
    foreach ($updatable as $field) {
        if (array_key_exists($field, $input)) {
            $programs[$idx][$field] = $input[$field];
        }
    }

    save_json('programs.json', $programs);
    echo json_encode(['message' => 'Program updated', 'program' => $programs[$idx]]);
    exit;
}

// ── DELETE: remove program by id ──
if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid "id"']);
        exit;
    }

    $idx = array_search($id, array_column($programs, 'id'));
    if ($idx === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Program not found']);
        exit;
    }

    $deleted = $programs[$idx];
    array_splice($programs, $idx, 1);
    save_json('programs.json', $programs);

    echo json_encode(['message' => 'Program deleted', 'program' => $deleted]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

// ── Scan system for installed packages ──
function handle_scan(array $existing): void {
    $known = [];
    foreach ($existing as $p) {
        if ($p['package']) $known[$p['package']] = true;
    }

    // Query dpkg for explicitly installed packages (not auto)
    $output = [];
    exec("dpkg-query -W -f='\${Package}|\${Status}|\${Summary}\n' 2>/dev/null", $output, $code);

    $found = [];
    if ($code === 0) {
        foreach ($output as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 2) continue;
            $pkg = trim($parts[0], "'");
            $status = $parts[1] ?? '';
            $summary = $parts[2] ?? '';

            // Skip if not "install ok installed"
            if (!str_contains($status, 'installed')) continue;
            // Skip already known
            if (isset($known[$pkg])) continue;

            $found[] = [
                'package' => $pkg,
                'notes'   => trim($summary, "'"),
                'source'  => 'dpkg',
            ];
        }
    }

    // Also scan command_keys from COMMAND_WHITELIST that aren't in DB
    $existingKeys = [];
    foreach ($existing as $p) {
        if ($p['command_key']) $existingKeys[$p['command_key']] = true;
    }
    $orphanCommands = [];
    foreach (COMMAND_WHITELIST as $key => $cfg) {
        if (!isset($existingKeys[$key])) {
            $orphanCommands[] = [
                'command_key' => $key,
                'command'     => $cfg['cmd'],
                'source'      => 'whitelist',
            ];
        }
    }

    echo json_encode([
        'new_packages' => $found,
        'orphan_commands' => $orphanCommands,
    ]);
}
