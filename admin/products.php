<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$filter = (int) ($_GET['cat'] ?? 0);

$sql = 'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id';
$params = [];
if ($filter > 0) {
    $sql .= ' WHERE p.category_id = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY c.sort_order, p.sort_order, p.id';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order')->fetchAll();

admin_header('Ürünler', 'products.php');
flash();
?>
<div class="page-head">
  <h1>Ürünler</h1>
  <a class="btn" href="product-edit.php">Yeni ürün</a>
</div>
<div class="card" style="margin-bottom:14px">
  <form method="get">
    <label>Kategoriye göre filtrele
      <select name="cat" onchange="this.form.submit()">
        <option value="0">Tüm kategoriler</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $filter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
</div>
<div class="product-list">
<?php foreach ($rows as $p): ?>
  <article class="product-row">
    <?php if ($p['image_path']): ?>
      <img class="thumb" src="../<?= e($p['image_path']) ?>" alt="">
    <?php else: ?>
      <div class="thumb-empty">Fotoğraf yok</div>
    <?php endif; ?>
    <div>
      <div class="p-name">
        <?= e($p['name']) ?>
        <?php if ((int) $p['is_featured']): ?><span class="badge">Öne çıkan</span><?php endif; ?>
        <?php if (!(int) $p['is_active']): ?><span class="badge off">Gizli</span><?php endif; ?>
      </div>
      <div class="p-meta"><?= e($p['category_name']) ?></div>
    </div>
    <div class="price-col"><?= e(format_price($p['price'])) ?></div>
    <a class="btn ghost" href="product-edit.php?id=<?= (int) $p['id'] ?>">Düzenle</a>
  </article>
<?php endforeach; ?>
<?php if (!$rows): ?><p class="muted">Bu filtrede ürün yok.</p><?php endif; ?>
</div>
<?php admin_footer();