<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$service = $_GET['service'] ?? '';
if ($service === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "service" parameter']);
    exit;
}

// Sanitize: only alphanumeric, hyphens, underscores
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $service)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid service name']);
    exit;
}

// Check active status
exec("systemctl is-active " . escapeshellarg($service), $activeOutput, $activeCode);
$isActive = $activeCode === 0;

// Check enabled status
exec("systemctl is-enabled " . escapeshellarg($service), $enabledOutput, $enabledCode);
$isEnabled = $enabledCode === 0;

// Get main PID
exec("systemctl show " . escapeshellarg($service) . " --property=MainPID --value", $pidOutput, $pidCode);
$mainPid = ($pidCode === 0) ? trim(implode("\n", $pidOutput)) : null;

// Get memory usage if running
$memoryUsage = null;
if ($isActive && $mainPid && $mainPid !== '0') {
    exec("systemctl show " . escapeshellarg($service) . " --property=MemoryCurrent --value", $memOutput, $memCode);
    if ($memCode === 0) {
        $bytes = (int) trim(implode("\n", $memOutput));
        $memoryUsage = format_bytes($bytes);
    }
}

echo json_encode([
    'service'  => $service,
    'active'   => $isActive,
    'enabled'  => $isEnabled,
    'status'   => trim(implode("\n", $activeOutput)),
    'pid'      => ($mainPid !== '0' && $mainPid !== '') ? $mainPid : null,
    'memory'   => $memoryUsage,
]);

function format_bytes(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes, 1024));
    $i = min($i, count($units) - 1);
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}
