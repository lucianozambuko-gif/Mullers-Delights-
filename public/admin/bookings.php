<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

$admin = require_admin();

$status = is_string($_GET['status'] ?? null) && isset(booking_statuses()[$_GET['status']]) ? $_GET['status'] : '';
$search = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    // Escape LIKE wildcards so a search for "%" doesn't match everything.
    // "!" is the escape character because backslash is quoted differently by MySQL and SQLite.
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    $where[] = "(name LIKE ? ESCAPE '!' OR phone LIKE ? ESCAPE '!' OR reference LIKE ? ESCAPE '!')";
    array_push($params, $like, $like, $like);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total = (int) scalar('SELECT COUNT(*) FROM bookings' . $whereSql, $params);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
// LIMIT and OFFSET are integers computed above, not user text
$bookings = all('SELECT * FROM bookings' . $whereSql . " ORDER BY id DESC LIMIT $perPage OFFSET $offset", $params);

$link = fn (array $change) => '/admin/bookings.php?' . http_build_query(array_filter(array_merge(['status' => $status, 'q' => $search], $change), fn ($value) => $value !== '' && $value !== null));

admin_start('Bookings', 'bookings', $admin);
?>
        <div class="admin-card mb-4">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a class="btn btn-sm <?= $status === '' ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($link(['status' => ''])) ?>">All</a>
<?php foreach (booking_statuses() as $key => $label): ?>
                <a class="btn btn-sm <?= $status === $key ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($link(['status' => $key])) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
            </div>
            <form method="get" action="/admin/bookings.php" class="row g-2">
<?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
<?php endif; ?>
                <div class="col">
                    <label class="visually-hidden" for="q">Search bookings</label>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($search) ?>" maxlength="60" placeholder="Search name, phone or reference">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>

        <p class="text-muted"><?= $total ?> booking<?= $total === 1 ? '' : 's' ?><?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?></p>

<?php if (!$bookings): ?>
        <div class="admin-card"><p class="mb-0">No bookings to show.</p></div>
<?php endif; ?>
<?php foreach ($bookings as $booking): ?>
        <a class="admin-card booking-card mb-3" href="/admin/booking.php?id=<?= (int) $booking['id'] ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <strong class="booking-card-name"><?= e($booking['name']) ?></strong>
                    <small class="text-muted ms-1"><?= e($booking['reference']) ?></small>
                </div>
                <div class="text-end text-nowrap">
                    <?= status_badge($booking['status']) ?>
                    <?= payment_badge($booking['payment_status']) ?>
                </div>
            </div>
            <div class="row g-2 mt-1 booking-card-meta">
                <div class="col-sm-4"><i class="fa fa-calendar-alt me-2"></i><?= e(date('D j M Y', strtotime($booking['event_date']))) ?></div>
                <div class="col-sm-4"><i class="fa fa-glass-cheers me-2"></i><?= e($booking['event_type']) ?>, <?= (int) $booking['guests'] ?> guests</div>
                <div class="col-sm-4"><i class="fa fa-phone-alt me-2"></i><?= e($booking['phone']) ?></div>
            </div>
            <small class="text-muted">Received <?= e(local_time($booking['created_at'])) ?></small>
        </a>
<?php endforeach; ?>

<?php if ($pages > 1): ?>
        <nav class="d-flex justify-content-between align-items-center mt-4" aria-label="Booking pages">
            <a class="btn btn-outline-dark<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= e($link(['page' => $page - 1])) ?>">Previous</a>
            <span>Page <?= $page ?> of <?= $pages ?></span>
            <a class="btn btn-outline-dark<?= $page >= $pages ? ' disabled' : '' ?>" href="<?= e($link(['page' => $page + 1])) ?>">Next</a>
        </nav>
<?php endif; ?>
<?php
admin_end();
