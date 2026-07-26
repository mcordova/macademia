<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$executions = load_json('executions.json');
$programs = load_json('programs.json');
$programMap = [];
foreach ($programs as $p) {
    $programMap[$p['id']] = $p['name'];
}

$programId = isset($_GET['program_id']) ? (int) $_GET['program_id'] : null;
$limit = min((int) ($_GET['limit'] ?? 50), 200);

// Filter
if ($programId) {
    $executions = array_filter($executions, fn($e) => $e['program_id'] == $programId);
}

// Sort by date descending
usort($executions, fn($a, $b) => strcmp($b['executed_at'], $a['executed_at']));

// Limit
$executions = array_slice($executions, 0, $limit);

// Add program name
$result = array_map(function ($e) use ($programMap) {
    $e['program_name'] = $programMap[$e['program_id']] ?? 'Unknown';
    return $e;
}, $executions);

echo json_encode(array_values($result));
