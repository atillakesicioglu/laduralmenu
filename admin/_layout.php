<?php

declare(strict_types=1);

function admin_header(string $title, string $active = ''): void
{
    $user = e((string) ($_SESSION['admin_username'] ?? ''));
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' — Admin</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap">';
    echo '<link rel="stylesheet" href="../assets/css/admin.css"></head><body class="admin-body">';
    echo '<nav class="topbar">';
    $links = [
        'index.php' => 'Özet',
        'products.php' => 'Ürünler',
        'categories.php' => 'Kategoriler',
        'settings.php' => 'Ayarlar',
        'password.php' => 'Şifre',
    ];
    foreach ($links as $href => $label) {
        $cls = $active === $href ? ' active' : '';
        echo '<a class="' . $cls . '" href="' . $href . '">' . $label . '</a>';
    }
    echo '<span class="spacer"></span><span>' . $user . '</span><a href="logout.php">Çıkış</a></nav><div class="wrap">';
}

function admin_footer(): void
{
    echo '</div></body></html>';
}

function flash(): void
{
    if (!empty($_SESSION['flash_ok'])) {
        echo '<p class="ok">' . e((string) $_SESSION['flash_ok']) . '</p>';
        unset($_SESSION['flash_ok']);
    }
    if (!empty($_SESSION['flash_err'])) {
        echo '<p class="err">' . e((string) $_SESSION['flash_err']) . '</p>';
        unset($_SESSION['flash_err']);
    }
}
