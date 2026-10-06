<?php
declare(strict_types=1);

// Server-to-server callback from Paynow. Trust comes from the signature, not the caller.
require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/paynow.php';

header('Content-Type: text/plain');

if (!is_post() || !paynow_configured()) {
    http_response_code(400);
    exit('Bad request');
}

$fields = paynow_parse((string) file_get_contents('php://input'));
if (!paynow_apply_status($fields)) {
    http_response_code(400);
    exit('Rejected');
}

echo 'OK';
