<?php
declare(strict_types=1);

// Creates missing tables and seeds starting content. Run on every deploy:
//   php bin/migrate.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/schema.php';

try {
    foreach (migrate() as $line) {
        echo $line, PHP_EOL;
    }
    echo 'Migration complete (' . db_driver() . ').', PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
