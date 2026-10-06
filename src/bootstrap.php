<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Load .env for local development. Real environment variables (Render) always win.
(function (): void {
    $file = APP_ROOT . '/.env';
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
})();

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function is_production(): bool
{
    return env('APP_ENV', 'local') === 'production';
}

date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Harare'));
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', is_production() ? '0' : '1');
ini_set('log_errors', '1');

// Never show a stack trace to visitors; log it and show a plain message instead.
set_exception_handler(function (Throwable $e): void {
    error_log(sprintf('[app] %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (wants_json()) {
        echo json_encode(['ok' => false, 'message' => 'Something went wrong on our side. Please try again or contact us on WhatsApp.']);
        return;
    }
    $detail = is_production() ? '' : '<pre style="white-space:pre-wrap">' . e($e->getMessage()) . '</pre>';
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Something went wrong</title><body style="font-family:sans-serif;max-width:560px;margin:15vh auto;padding:0 20px">'
        . '<h1>Something went wrong</h1><p>Sorry, we could not complete that request. Please go back and try again.</p>' . $detail . '</body>';
});

/** Numbers are accepted too: SQLite returns numeric columns as int/float where MySQL returns strings. */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return env('TRUST_PROXY') === '1' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

/** Public base URL with no trailing slash. Paynow needs absolute callback URLs. */
function app_url(): string
{
    $configured = env('APP_URL') ?? env('RENDER_EXTERNAL_URL');
    if ($configured !== null) {
        return rtrim($configured, '/');
    }
    return (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

function client_ip(): string
{
    if (env('TRUST_PROXY') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Trimmed, whitespace-collapsed single-line POST value. */
function post(string $key, int $maxLength = 255): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $maxLength);
}

/** Trimmed multi-line POST value. */
function post_text(string $key, int $maxLength = 2000): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
    return mb_substr($value, 0, $maxLength);
}

function send_security_headers(bool $noIndex = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
        . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; "
        . "connect-src 'self'; frame-ancestors 'none'; base-uri 'self'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    if ($noIndex) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

require APP_ROOT . '/src/db.php';
require APP_ROOT . '/src/content.php';
