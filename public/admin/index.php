<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

$admin = require_admin();

$counts = ['new' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
foreach (all('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$depositsPaid = (float) scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'");
$today = date('Y-m-d');
$upcoming = all("SELECT * FROM bookings WHERE status IN ('new', 'confirmed') AND event_date >= ? ORDER BY event_date, id LIMIT 8", [$today]);
$recent = all('SELECT * FROM bookings ORDER BY id DESC LIMIT 6');

admin_start('Dashboard', 'dashboard', $admin);
?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <a class="stat-card" href="/admin/bookings.php?status=new">
                    <span class="stat-value"><?= $counts['new'] ?></span>
                    <span class="stat-label">New requests</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="stat-card" href="/admin/bookings.php?status=confirmed">
                    <span class="stat-value"><?= $counts['confirmed'] ?></span>
                    <span class="stat-label">Confirmed</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="stat-card" href="/admin/bookings.php?status=completed">
                    <span class="stat-value"><?= $counts['completed'] ?></span>
                    <span class="stat-label">Completed</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-value"><?= e(money($depositsPaid)) ?></span>
                    <span class="stat-label">Deposits paid</span>
                </div>
            </div>
        </div>

<?php if (setting('deposits_enabled') === '1' && !paynow_configured()): ?>
        <div class="alert alert-warning" role="status">Online deposits are switched on, but the Paynow keys are not set on the server, so customers are not being offered the option to pay.</div>
<?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="admin-card h-100">
                    <h2 class="h4 mb-3">Upcoming events</h2>
<?php if (!$upcoming): ?>
                    <p class="text-muted mb-0">No upcoming events yet.</p>
<?php endif; ?>
<?php foreach ($upcoming as $booking): ?>
                    <a class="list-row" href="/admin/booking.php?id=<?= (int) $booking['id'] ?>">
                        <span>
                            <strong><?= e(date('D j M Y', strtotime($booking['event_date']))) ?></strong><br>
                            <?= e($booking['event_type']) ?> for <?= (int) $booking['guests'] ?>, <?= e($booking['name']) ?>
                        </span>
                        <?= status_badge($booking['status']) ?>
                    </a>
<?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="admin-card h-100">
                    <h2 class="h4 mb-3">Latest requests</h2>
<?php if (!$recent): ?>
                    <p class="text-muted mb-0">No bookings yet. They will appear here as soon as someone uses the form on the website.</p>
<?php endif; ?>
<?php foreach ($recent as $booking): ?>
                    <a class="list-row" href="/admin/booking.php?id=<?= (int) $booking['id'] ?>">
                        <span>
                            <strong><?= e($booking['name']) ?></strong> <small class="text-muted"><?= e($booking['reference']) ?></small><br>
                            <?= e($booking['event_type']) ?>, received <?= e(local_time($booking['created_at'], 'j M, H:i')) ?>
                        </span>
                        <?= status_badge($booking['status']) ?>
                    </a>
<?php endforeach; ?>
<?php if ($recent): ?>
                    <a class="btn btn-outline-dark btn-sm mt-3" href="/admin/bookings.php">All bookings</a>
<?php endif; ?>
                </div>
            </div>
        </div>
<?php
admin_end();
