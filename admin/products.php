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
<div class="card" style="margin-bottom:16px">
  <div class="row">
    <h1 style="margin:0;flex:1">Ürünler</h1>
    <a class="btn" href="product-edit.php">Ürün ekle</a>
  </div>
  <form method="get" style="margin-top:12px">
    <label>Kategori
      <select name="cat" onchange="this.form.submit()">
        <option value="0">Tümü</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $filter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
</div>

<div class="card" style="overflow:auto">
  <table>
    <thead>
      <tr>
        <th></th>
        <th>Ürün</th>
        <th>Kategori</th>
        <th>Fiyat</th>
        <th class="hide-sm">Durum</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td>
          <?php if ($p['image_path']): ?>
            <img class="thumb-sm" src="../<?= e($p['image_path']) ?>" alt="">
          <?php endif; ?>
        </td>
        <td>
          <strong><?= e($p['name']) ?></strong>
          <?php if ((int) $p['is_featured']): ?><div class="muted">Öne çıkan</div><?php endif; ?>
        </td>
        <td><?= e($p['category_name']) ?></td>
        <td><?= e(format_price($p['price'])) ?></td>
        <td class="hide-sm"><?= (int) $p['is_active'] ? 'Görünür' : 'Gizli' ?></td>
        <td><a href="product-edit.php?id=<?= (int) $p['id'] ?>">Düzenle</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_footer();