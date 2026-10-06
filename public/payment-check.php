<?php
declare(strict_types=1);

// Polled by the booking status page while a deposit is pending.
require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/paynow.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$token = $_GET['t'] ?? '';
$booking = (is_string($token) && preg_match('/^[a-f0-9]{48}$/', $token)) ? one('SELECT id, payment_status FROM bookings WHERE token = ?', [$token]) : null;
if ($booking === null) {
    http_response_code(404);
    exit(json_encode(['status' => 'unknown']));
}

if ($booking['payment_status'] === 'pending' && paynow_configured() && rate_limit_ok('paycheck', $token, 40, 3600)) {
    rate_limit_hit('paycheck', $token);
    $payment = one("SELECT * FROM payments WHERE booking_id = ? AND status = 'pending' ORDER BY id DESC", [$booking['id']]);
    if ($payment !== null) {
        paynow_poll($payment);
        $booking = one('SELECT id, payment_status FROM bookings WHERE id = ?', [$booking['id']]);
    }
}

echo json_encode(['status' => $booking['payment_status']]);
