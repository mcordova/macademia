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

if (!preg_match('/^[a-zA-Z0-9._-]+$/', $service)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid service name']);
    exit;
}

$port = null;
$urlPath = null;
$pid = null;
$memoryUsage = null;
$isActive = false;
$isEnabled = false;
$statusText = 'unknown';

if (is_service($service)) {
    // systemd service
    exec("systemctl is-active " . escapeshellarg($service), $activeOutput, $activeCode);
    $isActive = $activeCode === 0;

    exec("systemctl is-enabled " . escapeshellarg($service), $enabledOutput, $enabledCode);
    $isEnabled = $enabledCode === 0;

    $statusText = trim(implode("\n", $activeOutput));

    exec("systemctl show " . escapeshellarg($service) . " --property=MainPID --value", $pidOutput, $pidCode);
    $mainPid = ($pidCode === 0) ? trim(implode("\n", $pidOutput)) : null;
    $pid = ($mainPid !== '0' && $mainPid !== '') ? $mainPid : null;

    if ($isActive && $pid) {
        exec("systemctl show " . escapeshellarg($service) . " --property=MemoryCurrent --value", $memOutput, $memCode);
        if ($memCode === 0) {
            $bytes = (int) trim(implode("\n", $memOutput));
            $memoryUsage = format_bytes($bytes);
        }
    }

    $port = SERVICES[$service]['port'] ?? null;
    $urlPath = SERVICES[$service]['url_path'] ?? '/';

} elseif (is_docker($service)) {
    // Docker container
    $container = DOCKER[$service]['container'];

    exec("docker inspect --format='{{.State.Status}}' " . escapeshellarg($container) . " 2>/dev/null", $statusOut, $statusCode);
    $containerStatus = $statusCode === 0 ? trim(implode("\n", $statusOut)) : 'not-found';
    $isActive = $containerStatus === 'running';
    $isEnabled = $containerStatus !== 'not-found';
    $statusText = $containerStatus;

    if ($isActive) {
        exec("docker inspect --format='{{.State.Pid}}' " . escapeshellarg($container) . " 2>/dev/null", $pidOut, $pidCode);
        $mainPid = $pidCode === 0 ? trim(implode("\n", $pidOut)) : null;
        $pid = ($mainPid !== '0' && $mainPid !== '') ? $mainPid : null;

        exec("docker stats --no-stream --format '{{.MemUsage}}' " . escapeshellarg($container) . " 2>/dev/null", $memOut, $memCode);
        if ($memCode === 0) {
            $memoryUsage = trim(implode("\n", $memOut));
        }
    }

    $port = DOCKER[$service]['port'] ?? null;
    $urlPath = DOCKER[$service]['url_path'] ?? null;
}

echo json_encode([
    'service'  => $service,
    'active'   => $isActive,
    'enabled'  => $isEnabled,
    'status'   => $statusText,
    'pid'      => $pid,
    'memory'   => $memoryUsage,
    'port'     => $port,
    'url_path' => $urlPath,
]);

function format_bytes(int $bytes): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes, 1024));
    $i = min($i, count($units) - 1);
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}
