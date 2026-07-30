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

$isSvc = is_service($serviceKey);
$isDkr = is_docker($serviceKey);

if (!$isSvc && !$isDkr) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown service or container: ' . $serviceKey]);
    exit;
}

if ($isSvc) {
    $unitName = SERVICES[$serviceKey]['unit'];
    $command = sprintf('systemctl %s %s 2>&1', escapeshellarg($action), escapeshellarg($unitName));
    $displayCommand = sprintf('systemctl %s %s', $action, $unitName);
} else {
    $containerName = DOCKER[$serviceKey]['container'];
    $command = sprintf('docker %s %s 2>&1', escapeshellarg($action), escapeshellarg($containerName));
    $displayCommand = sprintf('docker %s %s', $action, $containerName);
}

$startTime = microtime(true);
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);
$durationMs = (int) ((microtime(true) - $startTime) * 1000);

$outputText = implode("\n", $output);

// Check new status
if ($isSvc) {
    exec("systemctl is-active " . escapeshellarg($unitName), $statusOutput, $statusActive);
    $newStatus = ($statusActive === 0) ? 'active' : 'inactive';
} else {
    exec("docker inspect --format='{{.State.Status}}' " . escapeshellarg($containerName) . " 2>/dev/null", $statusOutput, $statusActive);
    $newStatus = ($statusActive === 0) ? trim(implode("\n", $statusOutput)) : 'inactive';
}

// Log to history
$programs = load_json('programs.json');
$programId = null;
foreach ($programs as $p) {
    if ($p['command_key'] === $serviceKey && ($p['program_type'] === 'service' || $p['program_type'] === 'docker')) {
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
        'command_run'  => $displayCommand,
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
    'unit'         => $isSvc ? $unitName : $containerName,
    'command'      => $displayCommand,
    'output'       => $outputText,
    'duration_ms'  => $durationMs,
    'new_status'   => $newStatus,
]);
