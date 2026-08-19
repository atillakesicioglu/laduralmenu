<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_price(float|string $price): string
{
    $n = (float) $price;
    if (abs($n - round($n)) < 0.001) {
        return (string) (int) round($n) . ' TL';
    }

    return number_format($n, 2, ',', '.') . ' TL';
}

function slugify(string $text): string
{
    $map = [
        'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'I' => 'i',
        'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u',
        'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
    ];
    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'kat';
}

function setting(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $rows = $pdo->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        $cache = $rows;
    }

    return (string) ($cache[$key] ?? $default);
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function upsert_setting(PDO $pdo, string $k, string $v): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v')
            ->execute([$k, $v]);
        return;
    }
    $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)')
        ->execute([$k, $v]);
}

function unique_slug(PDO $pdo, string $base, ?int $ignoreId = null): string
{
    $slug = slugify($base);
    $i = 2;
    $try = $slug;
    while (true) {
        $sql = 'SELECT id FROM categories WHERE slug = ?';
        $params = [$try];
        if ($ignoreId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if (!$st->fetch()) {
            return $try;
        }
        $try = $slug . '-' . $i;
        $i++;
    }
}
