<?php
declare(strict_types=1);

// One-time creation of the first admin account. Refuses to run once any admin exists.
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

if ((int) scalar('SELECT COUNT(*) FROM admins') > 0) {
    redirect('/admin/login.php');
}

// In production the page only opens with the secret from ADMIN_SETUP_TOKEN, so a
// stranger who finds a fresh deploy first can't claim the admin account.
$expected = env('ADMIN_SETUP_TOKEN');
$given = $_GET['token'] ?? ($_POST['token'] ?? '');
$given = is_string($given) ? $given : '';
if ($expected === null && is_production()) {
    http_response_code(403);
    auth_page_start('Setup Locked');
    echo '<div class="alert alert-danger mb-0" role="alert">Set the ADMIN_SETUP_TOKEN environment variable, then open this page as /admin/setup.php?token=YOUR_TOKEN.</div>';
    auth_page_end();
    exit;
}
if ($expected !== null && !hash_equals($expected, $given)) {
    http_response_code(403);
    auth_page_start('Setup Locked');
    echo '<div class="alert alert-danger mb-0" role="alert">This page needs the setup token. Open it as /admin/setup.php?token=YOUR_TOKEN using the ADMIN_SETUP_TOKEN value from the hosting dashboard.</div>';
    auth_page_end();
    exit;
}

$errors = [];
$name = '';
$email = '';
if (is_post()) {
    verify_csrf();
    $name = post('name', 80);
    $email = strtolower(post('email', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    if (mb_strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (($problem = password_problem($password)) !== null) {
        $errors[] = $problem;
    } elseif ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        q('INSERT INTO admins (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)',
            [$name, $email, password_hash($password, PASSWORD_DEFAULT), now_utc()]);
        flash('success', 'Admin account created. You are now logged in.');
        attempt_login($email, $password);
        redirect('/admin/');
    }
}

auth_page_start('Create Admin Account');
?>
<?php foreach ($errors as $error): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>
        <form method="post" action="/admin/setup.php">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($given) ?>">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" placeholder="Your name" value="<?= e($name) ?>" maxlength="80" autocomplete="name" required autofocus>
                <label for="name">Your name</label>
            </div>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?= e($email) ?>" maxlength="190" autocomplete="username" required>
                <label for="email">Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="10" autocomplete="new-password" required>
                <label for="password">Password (10 characters or more)</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repeat password" minlength="10" autocomplete="new-password" required>
                <label for="password_confirm">Repeat password</label>
            </div>
            <button class="btn btn-primary w-100 py-3" type="submit">Create Account</button>
        </form>
<?php
auth_page_end();
