<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$prodCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

admin_header('Özet', 'index.php');
flash();
?>
<div class="card">
  <h1>Özet</h1>
  <p><?= $catCount ?> kategori · <?= $prodCount ?> ürün</p>
  <p class="row">
    <a class="btn" href="product-edit.php">Ürün ekle</a>
    <a class="btn ghost" href="../index.php" target="_blank" rel="noopener">Menüyü aç</a>
  </p>
</div>
<?php admin_footer();