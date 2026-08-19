<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/upload.php';

start_session();
try {
    $pdoBoot = db();
    if (db_installed($pdoBoot)) {
        apply_default_product_photos($pdoBoot);
    }
} catch (Throwable $e) {
}
