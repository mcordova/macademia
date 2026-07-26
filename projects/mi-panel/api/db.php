<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function data_path(string $file): string {
    return DATA_DIR . '/' . $file;
}

function ensure_data_dir(): void {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
}

function load_json(string $file): array {
    ensure_data_dir();
    $path = data_path($file);
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function save_json(string $file, array $data): void {
    ensure_data_dir();
    $path = data_path($file);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    // Atomic write: write to temp file then rename
    $tmp = $path . '.tmp.' . getmypid();
    file_put_contents($tmp, $json);
    rename($tmp, $path);
}

function next_id(array $items): int {
    if (empty($items)) return 1;
    return max(array_column($items, 'id')) + 1;
}
