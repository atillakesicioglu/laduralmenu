<?php

declare(strict_types=1);

function uploads_dir(): string
{
    $dir = dirname(__DIR__) . '/uploads/products';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function save_product_image(?array $file, ?string $oldPath = null): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $oldPath;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Fotoğraf yüklenemedi.');
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Fotoğraf en fazla 2 MB olabilir.');
    }

    $tmp = $file['tmp_name'];
    $info = getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('Geçersiz görsel.');
    }

    $mime = $info['mime'] ?? '';
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extMap[$mime])) {
        throw new RuntimeException('Sadece JPG, PNG veya WEBP yükleyin.');
    }

    $name = bin2hex(random_bytes(12)) . '.' . $extMap[$mime];
    $dest = uploads_dir() . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Fotoğraf kaydedilemedi.');
    }

    delete_product_image($oldPath);

    return 'uploads/products/' . $name;
}

function delete_product_image(?string $path): void
{
    if (!$path) {
        return;
    }
    if (!preg_match('#^uploads/products/[a-zA-Z0-9._-]+$#', $path)) {
        return;
    }
    $full = dirname(__DIR__) . '/' . $path;
    if (is_file($full)) {
        unlink($full);
    }
}
