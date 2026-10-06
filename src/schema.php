<?php
declare(strict_types=1);

/**
 * Creates any missing tables and seeds the starting content once.
 * Safe to run on every deploy: it only ever creates, never drops or alters.
 */
function migrate(): array
{
    $mysql = db_driver() === 'mysql';
    $pk = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fk = $mysql ? 'INT UNSIGNED' : 'INTEGER';
    $suffix = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $tables = [
        'settings' => "name VARCHAR(64) NOT NULL PRIMARY KEY, value TEXT NOT NULL",
        'admins' => "id $pk, name VARCHAR(80) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL",
        'rate_hits' => "id $pk, bucket VARCHAR(32) NOT NULL, key_hash CHAR(64) NOT NULL, created_at DATETIME NOT NULL",
        'package_groups' => "id $pk, name VARCHAR(120) NOT NULL, note VARCHAR(255) NOT NULL DEFAULT '', sort_order INT NOT NULL DEFAULT 0, is_active TINYINT NOT NULL DEFAULT 1",
        'packages' => "id $pk, group_id $fk NOT NULL, name VARCHAR(120) NOT NULL, price DECIMAL(10,2) NOT NULL, icon VARCHAR(40) NOT NULL DEFAULT 'fa-utensils', sort_order INT NOT NULL DEFAULT 0, is_active TINYINT NOT NULL DEFAULT 1",
        'event_types' => "id $pk, name VARCHAR(120) NOT NULL, description VARCHAR(255) NOT NULL DEFAULT '', icon VARCHAR(40) NOT NULL DEFAULT 'fa-utensils', sort_order INT NOT NULL DEFAULT 0, is_active TINYINT NOT NULL DEFAULT 1",
        'bookings' => "id $pk, reference VARCHAR(20) NOT NULL UNIQUE, token CHAR(48) NOT NULL UNIQUE, name VARCHAR(80) NOT NULL, phone VARCHAR(20) NOT NULL, "
            . "event_type VARCHAR(120) NOT NULL, package_label VARCHAR(255) NOT NULL DEFAULT '', package_price DECIMAL(10,2) NULL, event_date DATE NOT NULL, guests INT NOT NULL, "
            . "venue VARCHAR(120) NOT NULL DEFAULT '', details TEXT NULL, status VARCHAR(16) NOT NULL DEFAULT 'new', payment_status VARCHAR(16) NOT NULL DEFAULT 'none', "
            . "admin_notes TEXT NULL, consent_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL",
        'payments' => "id $pk, booking_id $fk NOT NULL, reference VARCHAR(40) NOT NULL UNIQUE, amount DECIMAL(10,2) NOT NULL, currency VARCHAR(3) NOT NULL DEFAULT 'USD', "
            . "status VARCHAR(16) NOT NULL DEFAULT 'pending', gateway_status VARCHAR(40) NOT NULL DEFAULT '', paynow_reference VARCHAR(60) NOT NULL DEFAULT '', "
            . "poll_url VARCHAR(500) NOT NULL DEFAULT '', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL",
    ];

    $log = [];
    foreach ($tables as $name => $columns) {
        db()->exec("CREATE TABLE IF NOT EXISTS $name ($columns)$suffix");
        $log[] = "table ok: $name";
    }

    $indexes = [
        'idx_rate_hits_lookup' => 'rate_hits (bucket, key_hash, created_at)',
        'idx_packages_group' => 'packages (group_id)',
        'idx_bookings_status' => 'bookings (status, event_date)',
        'idx_payments_booking' => 'payments (booking_id)',
    ];
    foreach ($indexes as $name => $definition) {
        if ($mysql) {
            $table = strtok($definition, ' ');
            $exists = scalar('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?', [$table, $name]);
            if (!$exists) {
                db()->exec("CREATE INDEX $name ON $definition");
            }
        } else {
            db()->exec("CREATE INDEX IF NOT EXISTS $name ON $definition");
        }
    }

    // Seed once. The marker stops deleted packages or events coming back on the next deploy.
    if (!scalar("SELECT COUNT(*) FROM settings WHERE name = 'seeded_at'")) {
        seed_defaults();
        $log[] = 'seeded starting content';
    }

    return $log;
}

function seed_defaults(): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        q("INSERT INTO package_groups (name, note, sort_order) VALUES ('Standard Main Course Meals', '', 10)");
        $standard = (int) $pdo->lastInsertId();
        q("INSERT INTO package_groups (name, note, sort_order) VALUES ('Exclusive Main Course Meals', 'Extra guests attract an extra fee.', 20)");
        $exclusive = (int) $pdo->lastInsertId();

        $packages = [
            [$standard, 'Breakfast', 20, 'fa-coffee', 10],
            [$standard, 'Lunch', 20, 'fa-concierge-bell', 20],
            [$standard, 'Supper', 20, 'fa-mug-hot', 30],
            [$exclusive, 'Lunch', 30, 'fa-concierge-bell', 10],
            [$exclusive, 'Dinner', 30, 'fa-utensils', 20],
        ];
        foreach ($packages as $package) {
            q('INSERT INTO packages (group_id, name, price, icon, sort_order) VALUES (?, ?, ?, ?, ?)', $package);
        }

        $events = [
            ['Marooro', 'Roora and lobola gatherings, catered with respect for the occasion.', 'fa-handshake', 10],
            ['Graduation Party', 'Celebrate the achievement with a meal the whole family remembers.', 'fa-graduation-cap', 20],
            ['Birthday Party', 'From small family birthdays to big milestone celebrations.', 'fa-birthday-cake', 30],
            ['Weddings', 'A wedding meal to match the day, served to all your guests.', 'fa-ring', 40],
            ['Kitchen Party', 'Good food for the bride-to-be and everyone celebrating with her.', 'fa-blender', 50],
            ['Business Events', 'Meetings, launches and staff functions, served on time.', 'fa-briefcase', 60],
        ];
        foreach ($events as $event) {
            q('INSERT INTO event_types (name, description, icon, sort_order) VALUES (?, ?, ?, ?)', $event);
        }

        q("INSERT INTO settings (name, value) VALUES ('seeded_at', ?)", [now_utc()]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
