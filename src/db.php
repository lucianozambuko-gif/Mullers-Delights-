<?php
declare(strict_types=1);

/**
 * MySQL (Aiven) in production. SQLite is supported only so the site can be run
 * locally with `php -S` and no database server: set DB_DRIVER=sqlite in .env.
 */
function db_driver(): string
{
    return env('DB_DRIVER', 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (db_driver() === 'sqlite') {
        $path = env('DB_SQLITE_PATH', APP_ROOT . '/data/app.sqlite');
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }

    // Aiven gives a single service URI: mysql://user:pass@host:port/dbname?ssl-mode=REQUIRED
    $url = env('DATABASE_URL');
    if ($url !== null) {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL is not a valid mysql:// URL.');
        }
        $host = $parts['host'];
        $port = (string) ($parts['port'] ?? 3306);
        $name = ltrim($parts['path'] ?? '', '/');
        $user = rawurldecode($parts['user'] ?? '');
        $pass = rawurldecode($parts['pass'] ?? '');
    } else {
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $name = env('DB_NAME', '');
        $user = env('DB_USER', '');
        $pass = env('DB_PASSWORD', '');
    }
    if ($name === '' || $user === '') {
        throw new RuntimeException('Database is not configured. Set DATABASE_URL (or DB_HOST, DB_NAME, DB_USER, DB_PASSWORD).');
    }

    // TLS: verified against the Aiven CA when DB_SSL_CA points at it, otherwise
    // encrypted but unverified. DB_SSL=off is for a local MySQL only.
    $ca = env('DB_SSL_CA');
    if ($ca !== null) {
        if (!is_readable($ca)) {
            throw new RuntimeException('DB_SSL_CA is set but the certificate file cannot be read.');
        }
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    } elseif (env('DB_SSL', 'require') !== 'off') {
        $options[PDO::MYSQL_ATTR_SSL_CAPATH] = '/etc/ssl/certs';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, $options);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function scalar(string $sql, array $params = []): mixed
{
    return q($sql, $params)->fetchColumn();
}

/** Timestamps are stored in UTC and converted for display. */
function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

function local_time(?string $utc, string $format = 'j M Y, H:i'): string
{
    if ($utc === null || $utc === '') {
        return '';
    }
    $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    return $date->setTimezone(new DateTimeZone(date_default_timezone_get()))->format($format);
}

/**
 * Sliding-window rate limit. Keys are stored hashed so no raw IP addresses or
 * emails are kept. Returns false when the limit has been reached.
 */
function rate_limit_ok(string $bucket, string $key, int $max, int $windowSeconds): bool
{
    $hash = hash('sha256', $bucket . '|' . strtolower($key));
    $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);
    return (int) scalar('SELECT COUNT(*) FROM rate_hits WHERE bucket = ? AND key_hash = ? AND created_at >= ?', [$bucket, $hash, $since]) < $max;
}

function rate_limit_hit(string $bucket, string $key): void
{
    $hash = hash('sha256', $bucket . '|' . strtolower($key));
    q('INSERT INTO rate_hits (bucket, key_hash, created_at) VALUES (?, ?, ?)', [$bucket, $hash, now_utc()]);
    // Occasional cleanup so the table stays small
    if (random_int(1, 50) === 1) {
        q('DELETE FROM rate_hits WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400 * 2)]);
    }
}
