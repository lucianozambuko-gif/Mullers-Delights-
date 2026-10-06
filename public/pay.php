<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/paynow.php';

send_security_headers(true);

$token = $_POST['t'] ?? '';
if (!is_post() || !is_string($token) || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    redirect('/');
}
$booking = one('SELECT * FROM bookings WHERE token = ?', [$token]);
if ($booking === null) {
    redirect('/');
}

$back = '/booking-status.php?t=' . $token;
if ($booking['payment_status'] === 'paid' || $booking['status'] === 'cancelled') {
    redirect($back);
}
if (!deposits_available()) {
    redirect($back . '&pay=unavailable');
}
// Stops a double-tap or a script from opening a pile of Paynow transactions
if (!rate_limit_ok('pay', $token, 5, 3600)) {
    redirect($back . '&pay=error');
}
rate_limit_hit('pay', $token);

try {
    $browserUrl = paynow_start_deposit($booking);
} catch (RuntimeException $e) {
    redirect($back . '&pay=error');
}

header('Location: ' . $browserUrl, true, 303);
exit;
