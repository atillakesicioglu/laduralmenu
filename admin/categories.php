<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $name = trim((string) $_POST['name']);
            $heading = trim((string) ($_POST['heading'] ?? '')) ?: $name;
            if ($name === '') {
                throw new RuntimeException('Kategori adı gerekli.');
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM categories')->fetchColumn();
            $pdo->prepare('INSERT INTO categories (slug, name, heading, sort_order) VALUES (?, ?, ?, ?)')
                ->execute([unique_slug($pdo, $name), $name, $heading, $max + 1]);
            $_SESSION['flash_ok'] = 'Kategori eklendi.';
        } elseif ($action === 'update') {
            $id = (int) $_POST['id'];
            $name = trim((string) $_POST['name']);
            $heading = trim((string) ($_POST['heading'] ?? '')) ?: $name;
            $sort = (int) $_POST['sort_order'];
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($name === '') {
                throw new RuntimeException('Kategori adı gerekli.');
            }
            $pdo->prepare('UPDATE categories SET slug=?, name=?, heading=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([unique_slug($pdo, $name, $id), $name, $heading, $sort, $active, $id]);
            $_SESSION['flash_ok'] = 'Kategori güncellendi.';
        } elseif ($action === 'delete') {
            $id = (int) $_POST['id'];
            $cnt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $cnt->execute([$id]);
            if ((int) $cnt->fetchColumn() > 0) {
                throw new RuntimeException('Önce bu kategorideki ürünleri silin veya taşıyın.');
            }
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            $_SESSION['flash_ok'] = 'Kategori silindi.';
        }
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
    }
    redirect('categories.php');
}

$rows = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY sort_order, id'
)->fetchAll();

admin_header('Kategoriler', 'categories.php');
flash();
?>
<div class="card" style="margin-bottom:16px">
  <h1>Yeni kategori</h1>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Chip adı (üst menü)
      <input name="name" required>
    </label>
    <label>Başlık (sayfa içi, örn. Gözleme ve Atıştırmalıklar)
      <input name="heading">
    </label>
    <button class="btn" type="submit">Ekle</button>
  </form>
</div>

<?php foreach ($rows as $c): ?>
<div class="card" style="margin-bottom:12px">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <label>Chip adı
      <input name="name" value="<?= e($c['name']) ?>" required>
    </label>
    <label>Başlık
      <input name="heading" value="<?= e($c['heading']) ?>">
    </label>
    <label>Sıra
      <input type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>">
    </label>
    <label class="check"><input type="checkbox" name="is_active" <?= (int) $c['is_active'] ? 'checked' : '' ?>> Menüde göster</label>
    <p class="muted"><?= (int) $c['product_count'] ?> ürün · slug: <?= e($c['slug']) ?></p>
    <div class="row">
      <button class="btn" type="submit">Kaydet</button>
    </div>
  </form>
  <form method="post" onsubmit="return confirm('Kategori silinsin mi?')">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <button class="btn danger" type="submit" <?= (int) $c['product_count'] > 0 ? 'disabled' : '' ?>>Sil</button>
  </form>
</div>
<?php endforeach; ?>
<?php admin_footer();