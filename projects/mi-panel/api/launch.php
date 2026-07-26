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
$programId = $input['program_id'] ?? null;

if (!$programId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing program_id']);
    exit;
}

$programs = load_json('programs.json');
$program = null;
foreach ($programs as $p) {
    if ($p['id'] == $programId && $p['enabled']) {
        $program = $p;
        break;
    }
}

if (!$program) {
    http_response_code(404);
    echo json_encode(['error' => 'Program not found or disabled']);
    exit;
}

// Services must use service-control.php instead
if ($program['program_type'] === 'service') {
    http_response_code(400);
    echo json_encode(['error' => 'Services must use service-control.php']);
    exit;
}

$cmdKey = $program['command_key'];
if (!$cmdKey || !is_whitelisted($cmdKey)) {
    http_response_code(403);
    echo json_encode([
        'error'   => 'Command not whitelisted',
        'command' => $cmdKey,
    ]);
    exit;
}

$whitelistEntry = COMMAND_WHITELIST[$cmdKey];

// Build the command
if ($whitelistEntry['args'] !== null) {
    $command = $whitelistEntry['cmd'] . ' ' . implode(' ', array_map('escapeshellarg', $whitelistEntry['args']));
} else {
    $command = $whitelistEntry['cmd'];
}

// Execute
$startTime = microtime(true);
$output = [];
$exitCode = 0;
exec($command . ' 2>&1', $output, $exitCode);
$durationMs = (int) ((microtime(true) - $startTime) * 1000);

$outputText = implode("\n", $output);
if (strlen($outputText) > 2048) {
    $outputText = substr($outputText, 0, 2048) . "\n... (truncated)";
}

// Save to history
$executions = load_json('executions.json');
$newExec = [
    'id'           => next_id($executions),
    'program_id'   => (int) $programId,
    'executed_at'  => date('Y-m-d H:i:s'),
    'command_run'  => $command,
    'exit_code'    => $exitCode,
    'output'       => $outputText,
    'duration_ms'  => $durationMs,
];
$executions[] = $newExec;
save_json('executions.json', $executions);

echo json_encode([
    'success'      => $exitCode === 0,
    'exit_code'    => $exitCode,
    'command'      => $command,
    'output'       => $outputText,
    'duration_ms'  => $durationMs,
    'execution_id' => $newExec['id'],
]);
