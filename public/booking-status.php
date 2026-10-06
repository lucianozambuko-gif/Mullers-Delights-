<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/layout.php';
require APP_ROOT . '/src/paynow.php';

// The link is the customer's key to their booking: a long random token, never the booking id
$token = $_GET['t'] ?? '';
$booking = (is_string($token) && preg_match('/^[a-f0-9]{48}$/', $token)) ? one('SELECT * FROM bookings WHERE token = ?', [$token]) : null;

if ($booking === null) {
    http_response_code(404);
    simple_page_start('Booking not found');
    echo '<div class="mx-auto simple-card text-center"><h1 class="mb-3">Booking not found</h1><p>This booking link is not valid. Please check the link, or make a new booking.</p>'
        . '<a class="btn btn-primary py-3 px-5 mt-2" href="/#booking">Make a booking</a></div>';
    simple_page_end();
    exit;
}

header('Cache-Control: no-store');

$notice = match ($_GET['pay'] ?? '') {
    'error' => ['danger', 'The payment could not be started. Please try again in a moment, or contact us on WhatsApp.'],
    'unavailable' => ['warning', 'Online deposits are not available right now. Please contact us on WhatsApp to arrange payment.'],
    default => null,
};

$payments = all('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC', [$booking['id']]);
$paid = $booking['payment_status'] === 'paid';
$paidPayment = array_values(array_filter($payments, fn ($payment) => $payment['status'] === 'paid'))[0] ?? null;
$pending = !$paid && $payments && $payments[0]['status'] === 'pending' && $payments[0]['poll_url'] !== '';
$canPay = !$paid && $booking['status'] !== 'cancelled' && deposits_available();
$whatsapp = setting('whatsapp_number');

simple_page_start('Booking ' . $booking['reference']);
?>
            <div class="mx-auto simple-card">
                <div class="text-center mb-4">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">Thank You</h5>
                    <h1 class="mb-2">Booking Request Received</h1>
                    <p class="mb-0">Your reference is <strong class="booking-ref"><?= e($booking['reference']) ?></strong></p>
                </div>

<?php if ($notice): ?>
                <div class="alert alert-<?= e($notice[0]) ?>" role="alert"><?= e($notice[1]) ?></div>
<?php endif; ?>

<?php if ($booking['status'] === 'cancelled'): ?>
                <div class="alert alert-secondary" role="status">This booking has been cancelled. Contact us if you think this is a mistake.</div>
<?php elseif ($booking['status'] === 'confirmed'): ?>
                <div class="alert alert-success" role="status"><?= e(setting('contact_name')) ?> has confirmed this booking.</div>
<?php endif; ?>

<?php if ($whatsapp !== '' && $booking['status'] !== 'cancelled'): ?>
                <div class="status-step">
                    <h4>1. Send it to <?= e(setting('contact_name')) ?> on WhatsApp</h4>
                    <p>Your details are saved. Sending them on WhatsApp starts the conversation so the menu, date and price can be confirmed with you.</p>
                    <a class="btn btn-whatsapp w-100 py-3" href="<?= e(wa_link($whatsapp, booking_whatsapp_message($booking))) ?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp me-2"></i>Send Booking on WhatsApp</a>
                </div>
<?php endif; ?>

<?php if ($paid): ?>
                <div class="status-step">
                    <h4>Deposit received</h4>
                    <div class="alert alert-success mb-0" role="status"><i class="fa fa-check-circle me-2"></i>Thank you. Your deposit<?= $paidPayment ? ' of ' . e(money($paidPayment['amount'])) : '' ?> has been paid.</div>
                </div>
<?php elseif ($canPay): ?>
                <div class="status-step">
                    <h4>2. Pay your deposit (optional)</h4>
<?php if ($pending): ?>
                    <div class="alert alert-info" role="status">We are waiting for Paynow to confirm your payment. This page updates when it comes through. If you did not finish paying, you can start again below.</div>
<?php elseif ($booking['payment_status'] === 'cancelled' || $booking['payment_status'] === 'failed'): ?>
                    <div class="alert alert-warning" role="status">Your last payment did not go through. No money was taken. You can try again below.</div>
<?php endif; ?>
                    <p>Secure your date with a deposit of <strong><?= e(money(setting('deposit_amount'))) ?></strong>, paid securely through Paynow.</p>
                    <form method="post" action="/pay.php">
                        <input type="hidden" name="t" value="<?= e($booking['token']) ?>">
                        <button class="btn btn-primary w-100 py-3" type="submit"><i class="fa fa-lock me-2"></i>Pay <?= e(money(setting('deposit_amount'))) ?> Deposit</button>
                    </form>
                </div>
<?php endif; ?>

                <div class="status-step">
                    <h4>Your details</h4>
                    <dl class="row mb-0 booking-summary">
                        <dt class="col-5 col-sm-4">Name</dt><dd class="col-7 col-sm-8"><?= e($booking['name']) ?></dd>
                        <dt class="col-5 col-sm-4">Phone</dt><dd class="col-7 col-sm-8"><?= e($booking['phone']) ?></dd>
                        <dt class="col-5 col-sm-4">Event</dt><dd class="col-7 col-sm-8"><?= e($booking['event_type']) ?></dd>
                        <dt class="col-5 col-sm-4">Date</dt><dd class="col-7 col-sm-8"><?= e(date('l, j F Y', strtotime($booking['event_date']))) ?></dd>
                        <dt class="col-5 col-sm-4">Guests</dt><dd class="col-7 col-sm-8"><?= (int) $booking['guests'] ?></dd>
<?php if ($booking['package_label'] !== ''): ?>
                        <dt class="col-5 col-sm-4">Package</dt><dd class="col-7 col-sm-8"><?= e($booking['package_label']) ?><?= $booking['package_price'] !== null ? ' (' . e(money($booking['package_price'])) . ')' : '' ?></dd>
<?php endif; ?>
<?php if ($booking['venue'] !== ''): ?>
                        <dt class="col-5 col-sm-4">Venue</dt><dd class="col-7 col-sm-8"><?= e($booking['venue']) ?></dd>
<?php endif; ?>
<?php if ((string) $booking['details'] !== ''): ?>
                        <dt class="col-5 col-sm-4">Details</dt><dd class="col-7 col-sm-8"><?= nl2br(e($booking['details'])) ?></dd>
<?php endif; ?>
                    </dl>
                </div>

                <p class="text-center mt-4 mb-0"><small>Keep this page's link to come back to your booking. A booking is only final once <?= e(setting('contact_name')) ?> confirms it with you.</small></p>
            </div>
<?php if ($pending): ?>
            <script src="/js/status-refresh.js"></script>
<?php endif; ?>
<?php
simple_page_end();
