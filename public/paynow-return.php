<?php
declare(strict_types=1);

// Where Paynow sends the customer after checkout. The return itself proves nothing,
// so ask Paynow for the real outcome before showing the booking page.
require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/paynow.php';

send_security_headers(true);

$token = $_GET['t'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    redirect('/');
}
$booking = one('SELECT id FROM bookings WHERE token = ?', [$token]);
if ($booking === null) {
    redirect('/');
}

$payment = one("SELECT * FROM payments WHERE booking_id = ? AND status = 'pending' ORDER BY id DESC", [$booking['id']]);
if ($payment !== null && paynow_configured()) {
    paynow_poll($payment);
}

redirect('/booking-status.php?t=' . $token);
