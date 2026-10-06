<?php
declare(strict_types=1);

require_once APP_ROOT . '/src/layout.php';

function admin_start(string $title, string $active, array $admin): void
{
    $newCount = (int) scalar("SELECT COUNT(*) FROM bookings WHERE status = 'new'");
    $links = [
        'dashboard' => ['/admin/', 'Dashboard', 'fa-tachometer-alt'],
        'bookings' => ['/admin/bookings.php', 'Bookings', 'fa-calendar-check'],
        'packages' => ['/admin/packages.php', 'Packages', 'fa-utensils'],
        'events' => ['/admin/events.php', 'Events', 'fa-glass-cheers'],
        'content' => ['/admin/content.php', 'Content', 'fa-pen'],
        'settings' => ['/admin/settings.php', 'Settings', 'fa-cog'],
    ];
    html_head($title . ' - Admin', '', ['/css/admin.css']);
    ?>

<body class="admin">
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav px-3 px-lg-4">
        <a class="navbar-brand p-0" href="/admin/">
            <img src="/img/logo-horizontal.png" alt="<?= e(setting('site_name')) ?>" width="640" height="182">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="fa fa-bars"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
<?php foreach ($links as $key => [$href, $label, $icon]): ?>
                <li class="nav-item">
                    <a class="nav-link<?= $key === $active ? ' active' : '' ?>" href="<?= e($href) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>>
                        <i class="fa <?= e($icon) ?> me-2"></i><?= e($label) ?>
<?php if ($key === 'bookings' && $newCount > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $newCount ?></span>
<?php endif; ?>
                    </a>
                </li>
<?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center flex-wrap gap-2 py-2 py-lg-0">
                <span class="admin-user me-2"><i class="fa fa-user me-2"></i><?= e($admin['name']) ?></span>
                <a class="btn btn-outline-light btn-sm" href="/" target="_blank" rel="noopener">View site</a>
                <form method="post" action="/admin/logout.php" class="m-0">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary btn-sm" type="submit">Log out</button>
                </form>
            </div>
        </div>
    </nav>
    <main class="container-xl py-4">
        <h1 class="admin-title mb-4"><?= e($title) ?></h1>
<?php foreach (take_flashes() as [$type, $message]): ?>
        <div class="alert alert-<?= e($type) ?>" role="<?= $type === 'danger' ? 'alert' : 'status' ?>"><?= e($message) ?></div>
<?php endforeach; ?>
<?php
}

function admin_end(): void
{
    ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/admin.js"></script>
</body>

</html>
<?php
}

/** Login and setup pages: centred card, no navigation. */
function auth_page_start(string $title): void
{
    send_security_headers(true);
    header('Cache-Control: no-store');
    html_head($title . ' - Admin', '', ['/css/admin.css']);
    ?>

<body class="admin admin-auth">
    <main class="auth-card">
        <img class="auth-logo" src="/img/logo-emblem.png" alt="<?= e(setting('site_name')) ?>" width="520" height="520">
        <h1 class="h3 text-center mb-4"><?= e($title) ?></h1>
<?php
}

function auth_page_end(): void
{
    ?>
    </main>
</body>

</html>
<?php
}

function icon_select(string $name, string $selected, string $id = ''): string
{
    $html = '<select class="form-select" name="' . e($name) . '"' . ($id !== '' ? ' id="' . e($id) . '"' : '') . ' aria-label="Icon">';
    foreach (icon_choices() as $value => $label) {
        $html .= '<option value="' . e($value) . '"' . ($value === $selected ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
}

function status_badge(string $status): string
{
    $classes = ['new' => 'bg-warning text-dark', 'confirmed' => 'bg-success', 'completed' => 'bg-secondary', 'cancelled' => 'bg-danger'];
    return '<span class="badge ' . ($classes[$status] ?? 'bg-secondary') . '">' . e(booking_statuses()[$status] ?? $status) . '</span>';
}

function payment_badge(string $status): string
{
    if ($status === 'none') {
        return '';
    }
    $classes = ['pending' => 'bg-info text-dark', 'paid' => 'bg-success', 'cancelled' => 'bg-secondary', 'failed' => 'bg-danger'];
    return '<span class="badge ' . ($classes[$status] ?? 'bg-secondary') . '">' . e(payment_statuses()[$status] ?? $status) . '</span>';
}
