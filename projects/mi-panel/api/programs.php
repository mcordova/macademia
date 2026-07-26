<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $programs = load_json('programs.json');

    $result = array_filter($programs, function ($p) {
        if (!$p['enabled']) return false;

        if (!empty($_GET['category']) && $p['category'] !== $_GET['category']) return false;
        if (!empty($_GET['type']) && $p['program_type'] !== $_GET['type']) return false;

        if (!empty($_GET['search'])) {
            $q = strtolower($_GET['search']);
            $haystack = strtolower($p['name'] . ' ' . ($p['package'] ?? '') . ' ' . ($p['notes'] ?? '') . ' ' . $p['category']);
            if (strpos($haystack, $q) === false) return false;
        }

        return true;
    });

    // Sort by category then name
    usort($result, function ($a, $b) {
        $cmp = strcmp($a['category'], $b['category']);
        return $cmp !== 0 ? $cmp : strcmp($a['name'], $b['name']);
    });

    echo json_encode(array_values($result));
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['name']) || empty($input['program_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, program_type']);
        exit;
    }

    $programs = load_json('programs.json');
    $newProgram = [
        'id'           => next_id($programs),
        'name'         => $input['name'],
        'package'      => $input['package'] ?? null,
        'command_key'  => $input['command_key'] ?? null,
        'category'     => $input['category'] ?? 'Other',
        'program_type' => $input['program_type'],
        'notes'        => $input['notes'] ?? null,
        'enabled'      => 1,
        'created_at'   => date('Y-m-d H:i:s'),
    ];

    $programs[] = $newProgram;
    save_json('programs.json', $programs);

    http_response_code(201);
    echo json_encode(['id' => $newProgram['id'], 'message' => 'Program created']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
