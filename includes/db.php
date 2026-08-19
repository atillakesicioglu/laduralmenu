<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configPath = __DIR__ . '/config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('config.php eksik. includes/config.sample.php dosyasını kopyalayın.');
    }

    $cfg = require $configPath;
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (($cfg['db_driver'] ?? 'mysql') === 'sqlite') {
        $file = $cfg['sqlite_path'] ?? (dirname(__DIR__) . '/data/menu.sqlite');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . $file, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $cfg['db_host'],
        $cfg['db_name'],
        $cfg['db_charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], $opts);

    return $pdo;
}

function db_installed(PDO $pdo): bool
{
    try {
        $v = $pdo->query("SELECT v FROM settings WHERE k = 'installed'")->fetchColumn();
        return $v === '1';
    } catch (PDOException) {
        return false;
    }
}
