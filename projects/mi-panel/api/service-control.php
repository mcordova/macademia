<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$serviceKey = $input['service'] ?? null;
$action = $input['action'] ?? null;

if (!$serviceKey || !$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "service" or "action"']);
    exit;
}

if (!in_array($action, ['start', 'stop', 'restart'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action. Must be: start, stop, restart']);
    exit;
}

if (!is_service($serviceKey)) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown service: ' . $serviceKey]);
    exit;
}

$unitName = SERVICES[$serviceKey]['unit'];

// Build and execute systemctl command
$command = sprintf('systemctl %s %s 2>&1', escapeshellarg($action), escapeshellarg($unitName));

$startTime = microtime(true);
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);
$durationMs = (int) ((microtime(true) - $startTime) * 1000);

$outputText = implode("\n", $output);

// Check new status
exec("systemctl is-active " . escapeshellarg($unitName), $statusOutput, $statusActive);

// Log to history
$programs = load_json('programs.json');
$programId = null;
foreach ($programs as $p) {
    if ($p['command_key'] === $serviceKey && $p['program_type'] === 'service') {
        $programId = $p['id'];
        break;
    }
}

if ($programId) {
    $executions = load_json('executions.json');
    $newExec = [
        'id'           => next_id($executions),
        'program_id'   => (int) $programId,
        'executed_at'  => date('Y-m-d H:i:s'),
        'command_run'  => sprintf('systemctl %s %s', $action, $unitName),
        'exit_code'    => $exitCode,
        'output'       => $outputText ?: null,
        'duration_ms'  => $durationMs,
    ];
    $executions[] = $newExec;
    save_json('executions.json', $executions);
}

echo json_encode([
    'success'      => $exitCode === 0,
    'exit_code'    => $exitCode,
    'action'       => $action,
    'service'      => $serviceKey,
    'unit'         => $unitName,
    'command'      => sprintf('systemctl %s %s', $action, $unitName),
    'output'       => $outputText,
    'duration_ms'  => $durationMs,
    'new_status'   => ($statusActive === 0) ? 'active' : 'inactive',
]);
