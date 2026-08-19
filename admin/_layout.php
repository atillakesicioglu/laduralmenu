<?php

declare(strict_types=1);

function admin_header(string $title, string $active = ''): void
{
    $user = e((string) ($_SESSION['admin_username'] ?? ''));
    $links = [
        'products.php' => 'Ürünler',
        'categories.php' => 'Kategoriler',
        'settings.php' => 'Kafe ayarları',
    ];
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' — La Dural</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap">';
    echo '<link rel="stylesheet" href="../assets/css/admin.css"></head><body class="admin-body">';
    echo '<aside class="sidebar">';
    echo '<a class="sidebar-brand" href="products.php"><img src="../assets/img/logo.png" alt="La Dural"></a>';
    echo '<nav class="sidebar-nav">';
    foreach ($links as $href => $label) {
        $cls = $active === $href ? ' active' : '';
        echo '<a class="side-link' . $cls . '" href="' . $href . '">' . $label . '</a>';
    }
    echo '</nav>';
    echo '<div class="sidebar-foot">';
    echo '<div class="sidebar-user">' . $user . '</div>';
    echo '<a class="side-link logout" href="logout.php">Çıkış yap</a>';
    echo '</div></aside><div class="admin-main">';
}

function admin_footer(string $extraJs = ''): void
{
    if ($extraJs !== '') {
        echo '<script>' . $extraJs . '</script>';
    }
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

function price_js(): string
{
    return <<<'JS'
document.querySelectorAll("input.js-price").forEach((el) => {
  const fmt = () => {
    const raw = String(el.value || "").replace(/\s/g, "").replace(/\./g, "").replace(",", ".");
    if (raw === "") return;
    const n = Number(raw);
    if (Number.isNaN(n)) return;
    el.value = n.toFixed(2).replace(".", ",");
  };
  el.addEventListener("blur", fmt);
  fmt();
});
JS;
}
