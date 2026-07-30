<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$serviceKey = $_GET['service'] ?? '';
$lines = min(max((int) ($_GET['lines'] ?? 100), 10), 1000);
$colorize = ($_GET['colorize'] ?? '') === '1';

if ($serviceKey === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "service" parameter']);
    exit;
}

if (!is_service($serviceKey) && !is_docker($serviceKey)) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown service or container: ' . $serviceKey]);
    exit;
}

$output = [];
$exitCode = 0;
$source = 'journalctl';
$logFile = null;
$logUnit = $serviceKey;
$viewCommand = '';

if (is_service($serviceKey)) {
    $logUnit = SERVICES[$serviceKey]['log_unit'];

    $command = sprintf('journalctl -u %s --no-pager -n %d --output=short-iso', escapeshellarg($logUnit), $lines);
    exec($command . ' 2>&1', $output, $exitCode);

    $logText = implode("\n", $output);

    // Fallback to log files
    if (empty(trim($logText)) || $exitCode !== 0) {
        $candidates = [
            "/var/log/{$logUnit}/{$logUnit}.log",
            "/var/log/{$logUnit}.log",
            "/var/log/{$logUnit}",
        ];
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                $logFile = $path;
                $logCommand = sprintf('tail -n %d %s', $lines, escapeshellarg($path));
                exec($logCommand . ' 2>&1', $output, $exitCode);
                $logText = implode("\n", $output);
                $source = 'file';
                break;
            }
        }
    }

    if ($logFile) {
        $viewCommand = sprintf('tail -n %d %s', $lines, $logFile);
    } else {
        $viewCommand = sprintf('journalctl -u %s -n %d -f', $logUnit, min($lines, 50));
    }

} else {
    // Docker container logs
    $container = DOCKER[$serviceKey]['container'];
    $command = sprintf('docker logs --tail %d %s 2>&1', $lines, escapeshellarg($container));
    exec($command, $output, $exitCode);

    $logText = implode("\n", $output);
    $source = 'docker';
    $viewCommand = sprintf('docker logs --tail %d -f %s', $lines, $container);
}

// Colorize with ccze if requested
$contentType = 'text';
if ($colorize && $logText) {
    $descriptorspec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open('ccze -h', $descriptorspec, $pipes);
    if (is_resource($process)) {
        fwrite($pipes[0], $logText);
        fclose($pipes[0]);
        $colorized = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($process);
        if ($colorized !== false && $colorized !== '') {
            $logText = $colorized;
            $contentType = 'html';
        }
    }
}

echo json_encode([
    'service'       => $serviceKey,
    'log_unit'      => $logUnit,
    'lines'         => $lines,
    'log'           => $logText ?: '(no log entries found)',
    'view_command'  => $viewCommand,
    'source'        => $source,
    'log_file'      => $logFile,
    'content_type'  => $contentType,
]);
