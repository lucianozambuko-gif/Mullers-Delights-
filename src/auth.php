<?php
declare(strict_types=1);

const ADMIN_IDLE_SECONDS = 7200;

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('md_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function current_admin(): ?array
{
    start_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    if (time() - (int) ($_SESSION['last_seen'] ?? 0) > ADMIN_IDLE_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $admin = one('SELECT id, name, email FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
    if ($admin === null) {
        $_SESSION = [];
        return null;
    }
    $_SESSION['last_seen'] = time();
    return $admin;
}

/** Gate for every admin page. Also sends no-index and no-cache headers. */
function require_admin(): array
{
    send_security_headers(true);
    header('Cache-Control: no-store');
    $admin = current_admin();
    if ($admin === null) {
        redirect('/admin/login.php');
    }
    if (is_post()) {
        verify_csrf();
    }
    return $admin;
}

function attempt_login(string $email, string $password): ?string
{
    $ip = client_ip();
    // Throttle per address and per account, so rotating one doesn't bypass the other
    if (!rate_limit_ok('login_ip', $ip, 10, 900) || !rate_limit_ok('login_email', $email, 5, 900)) {
        return 'Too many login attempts. Please wait 15 minutes and try again.';
    }

    $admin = one('SELECT id, password_hash FROM admins WHERE email = ?', [$email]);
    if ($admin === null) {
        // Spend the same effort as a real check, so timing doesn't reveal which emails exist
        password_hash($password, PASSWORD_DEFAULT);
    }
    if ($admin === null || !password_verify($password, $admin['password_hash'])) {
        rate_limit_hit('login_ip', $ip);
        rate_limit_hit('login_email', $email);
        return 'Incorrect email or password.';
    }

    start_session();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['last_seen'] = time();
    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
    }
    return null;
}

function logout(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'], 'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

function password_problem(string $password): ?string
{
    if (strlen($password) < 10) {
        return 'Password must be at least 10 characters.';
    }
    if (strlen($password) > 200) {
        return 'Password is too long.';
    }
    return null;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_session();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        // 403 rather than the non-standard 419, which Apache turns into a 500
        http_response_code(403);
        exit('This form has expired. Please go back, refresh the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'][] = [$type, $message];
}

function take_flashes(): array
{
    start_session();
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}
