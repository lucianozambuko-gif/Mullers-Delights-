<?php
declare(strict_types=1);

// Health check for Render: 200 only when the app can reach its database.
require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: text/plain');
header('Cache-Control: no-store');

try {
    scalar('SELECT 1');
    echo 'ok';
} catch (Throwable $e) {
    error_log('[health] database check failed: ' . $e->getMessage());
    http_response_code(503);
    echo 'database unavailable';
}
