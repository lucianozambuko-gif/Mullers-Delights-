<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';
require APP_ROOT . '/src/paynow.php';

$admin = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$booking = one('SELECT * FROM bookings WHERE id = ?', [$id]);
if ($booking === null) {
    flash('danger', 'That booking does not exist. It may have been deleted.');
    redirect('/admin/bookings.php');
}
$self = '/admin/booking.php?id=' . $id;

if (is_post()) {
    $action = post('action', 30);

    if ($action === 'update') {
        $status = post('status', 16);
        if (!isset(booking_statuses()[$status])) {
            flash('danger', 'Please choose a valid status.');
        } else {
            q('UPDATE bookings SET status = ?, admin_notes = ?, updated_at = ? WHERE id = ?', [$status, post_text('admin_notes', 2000), now_utc(), $id]);
            flash('success', 'Booking updated.');
        }
        redirect($self);
    }

    if ($action === 'check_payment') {
        $payment = one('SELECT * FROM payments WHERE id = ? AND booking_id = ?', [(int) post('payment_id', 10), $id]);
        if ($payment === null) {
            flash('danger', 'That payment does not exist.');
        } elseif (!paynow_configured()) {
            flash('danger', 'Paynow is not configured on the server, so the payment cannot be checked.');
        } elseif (paynow_poll($payment)) {
            flash('success', 'Payment status refreshed from Paynow.');
        } else {
            flash('warning', 'Paynow did not return a status for this payment. Try again shortly.');
        }
        redirect($self);
    }

    if ($action === 'delete') {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            q('DELETE FROM payments WHERE booking_id = ?', [$id]);
            q('DELETE FROM bookings WHERE id = ?', [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', 'Booking ' . $booking['reference'] . ' was deleted.');
        redirect('/admin/bookings.php');
    }

    redirect($self);
}

$payments = all('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC', [$id]);
$replyText = 'Hello ' . $booking['name'] . ', this is ' . setting('contact_name') . ' from ' . setting('site_name') . ' about your booking ' . $booking['reference'] . '.';

admin_start('Booking ' . $booking['reference'], 'bookings', $admin);
?>
        <p><a href="/admin/bookings.php"><i class="fa fa-arrow-left me-2"></i>All bookings</a></p>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="admin-card mb-4">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <h2 class="h4 mb-0"><?= e($booking['name']) ?></h2>
                        <div class="text-end"><?= status_badge($booking['status']) ?> <?= payment_badge($booking['payment_status']) ?></div>
                    </div>
                    <dl class="row mb-0 booking-summary">
                        <dt class="col-5 col-sm-4">Phone</dt><dd class="col-7 col-sm-8"><a href="<?= e(tel_link($booking['phone'])) ?>"><?= e($booking['phone']) ?></a></dd>
                        <dt class="col-5 col-sm-4">Event</dt><dd class="col-7 col-sm-8"><?= e($booking['event_type']) ?></dd>
                        <dt class="col-5 col-sm-4">Date</dt><dd class="col-7 col-sm-8"><?= e(date('l, j F Y', strtotime($booking['event_date']))) ?></dd>
                        <dt class="col-5 col-sm-4">Guests</dt><dd class="col-7 col-sm-8"><?= (int) $booking['guests'] ?></dd>
                        <dt class="col-5 col-sm-4">Package</dt><dd class="col-7 col-sm-8"><?= $booking['package_label'] !== '' ? e($booking['package_label']) . ($booking['package_price'] !== null ? ' (' . e(money($booking['package_price'])) . ')' : '') : '<span class="text-muted">Not chosen</span>' ?></dd>
                        <dt class="col-5 col-sm-4">Venue</dt><dd class="col-7 col-sm-8"><?= $booking['venue'] !== '' ? e($booking['venue']) : '<span class="text-muted">Not given</span>' ?></dd>
                        <dt class="col-5 col-sm-4">Details</dt><dd class="col-7 col-sm-8"><?= (string) $booking['details'] !== '' ? nl2br(e($booking['details'])) : '<span class="text-muted">None</span>' ?></dd>
                        <dt class="col-5 col-sm-4">Received</dt><dd class="col-7 col-sm-8"><?= e(local_time($booking['created_at'])) ?></dd>
                    </dl>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a class="btn btn-whatsapp" href="<?= e(wa_link($booking['phone'], $replyText)) ?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp me-2"></i>WhatsApp customer</a>
                        <a class="btn btn-outline-dark" href="<?= e(tel_link($booking['phone'])) ?>"><i class="fa fa-phone-alt me-2"></i>Call</a>
                        <a class="btn btn-outline-dark" href="/booking-status.php?t=<?= e($booking['token']) ?>" target="_blank" rel="noopener">Customer's page</a>
                    </div>
                </div>

                <div class="admin-card">
                    <h2 class="h4 mb-3">Deposit payments</h2>
<?php if (!$payments): ?>
                    <p class="text-muted mb-0">No online payment has been started for this booking.</p>
<?php endif; ?>
<?php foreach ($payments as $payment): ?>
                    <div class="list-row">
                        <span>
                            <strong><?= e(money($payment['amount'])) ?></strong> <?= payment_badge($payment['status']) ?><br>
                            <small class="text-muted"><?= e($payment['reference']) ?><?= $payment['paynow_reference'] !== '' ? ', Paynow ref ' . e($payment['paynow_reference']) : '' ?><?= $payment['gateway_status'] !== '' ? ', ' . e($payment['gateway_status']) : '' ?><br>Started <?= e(local_time($payment['created_at'])) ?></small>
                        </span>
<?php if ($payment['status'] === 'pending' && $payment['poll_url'] !== ''): ?>
                        <form method="post" action="<?= e($self) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="check_payment">
                            <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                            <button class="btn btn-outline-dark btn-sm" type="submit">Check with Paynow</button>
                        </form>
<?php endif; ?>
                    </div>
<?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="admin-card mb-4">
                    <h2 class="h4 mb-3">Manage</h2>
                    <form method="post" action="<?= e($self) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <div class="mb-3">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
<?php foreach (booking_statuses() as $key => $label): ?>
                                <option value="<?= e($key) ?>"<?= $booking['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="admin_notes">Private notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="5" maxlength="2000" placeholder="Agreed price, menu, anything to remember. Customers never see this."><?= e($booking['admin_notes']) ?></textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Save</button>
                    </form>
                </div>

                <div class="admin-card">
                    <h2 class="h4 mb-2">Delete booking</h2>
                    <p class="text-muted">Permanently removes this booking, the customer's details and its payment records. Use this when a customer asks for their details to be deleted.</p>
                    <form method="post" action="<?= e($self) ?>" data-confirm="Delete booking <?= e($booking['reference']) ?> permanently? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-outline-danger w-100" type="submit">Delete permanently</button>
                    </form>
                </div>
            </div>
        </div>
<?php
admin_end();
