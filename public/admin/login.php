<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/admin-layout.php';

if (current_admin() !== null) {
    redirect('/admin/');
}
$noAdmins = (int) scalar('SELECT COUNT(*) FROM admins') === 0;

$error = null;
$email = '';
if (is_post()) {
    verify_csrf();
    $email = strtolower(post('email', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $error = attempt_login($email, $password);
    if ($error === null) {
        redirect('/admin/');
    }
}

auth_page_start('Admin Login');
?>
<?php if ($noAdmins): ?>
        <div class="alert alert-info" role="status">No admin account exists yet. Open the setup link from the deployment notes to create the first one.</div>
<?php endif; ?>
<?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>
        <form method="post" action="/admin/login.php">
            <?= csrf_field() ?>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
                <label for="email">Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
                <label for="password">Password</label>
            </div>
            <button class="btn btn-primary w-100 py-3" type="submit">Log In</button>
        </form>
        <p class="text-center mt-4 mb-0"><a href="/">Back to the website</a></p>
<?php
auth_page_end();
