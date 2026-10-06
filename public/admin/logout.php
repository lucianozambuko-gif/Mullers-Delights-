<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
require APP_ROOT . '/src/auth.php';

// POST only, with a CSRF token, so another site can't log the admin out with a link
if (is_post()) {
    verify_csrf();
    logout();
}
redirect('/admin/login.php');
