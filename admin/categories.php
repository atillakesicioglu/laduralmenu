<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$editId = (int) ($_GET['id'] ?? 0);
$isNew = isset($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $name = trim((string) $_POST['name']);
            $heading = trim((string) ($_POST['heading'] ?? '')) ?: $name;
            if ($name === '') {
                throw new RuntimeException('Menüde görünen ad gerekli.');
            }
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM categories')->fetchColumn();
            $pdo->prepare('INSERT INTO categories (slug, name, heading, sort_order) VALUES (?, ?, ?, ?)')
                ->execute([unique_slug($pdo, $name), $name, $heading, $max + 1]);
            $_SESSION['flash_ok'] = 'Kategori eklendi.';
            redirect('categories.php');
        } elseif ($action === 'update') {
            $id = (int) $_POST['id'];
            $name = trim((string) $_POST['name']);
            $heading = trim((string) ($_POST['heading'] ?? '')) ?: $name;
            $sort = (int) $_POST['sort_order'];
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($name === '') {
                throw new RuntimeException('Menüde görünen ad gerekli.');
            }
            $pdo->prepare('UPDATE categories SET slug=?, name=?, heading=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([unique_slug($pdo, $name, $id), $name, $heading, $sort, $active, $id]);
            $_SESSION['flash_ok'] = 'Kategori güncellendi.';
            redirect('categories.php');
        } elseif ($action === 'delete') {
            $id = (int) $_POST['id'];
            $cnt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $cnt->execute([$id]);
            if ((int) $cnt->fetchColumn() > 0) {
                throw new RuntimeException('Önce bu kategorideki ürünleri silin veya taşıyın.');
            }
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            $_SESSION['flash_ok'] = 'Kategori silindi.';
            redirect('categories.php');
        }
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
        redirect('categories.php');
    }
}

$rows = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY sort_order, id'
)->fetchAll();
$edit = null;
foreach ($rows as $c) {
    if ((int) $c['id'] === $editId) {
        $edit = $c;
        break;
    }
}

admin_header('Kategoriler', 'categories.php');
flash();
?>
<div class="page-head">
  <h1>Kategoriler</h1>
  <a class="btn" href="categories.php?new=1">Yeni kategori</a>
</div>

<?php if ($isNew || $edit): ?>
<div class="card" style="margin-bottom:16px">
  <h1><?= $edit ? 'Kategori düzenle' : 'Yeni kategori' ?></h1>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
    <label>Menüde görünen ad
      <span class="hint">Üstteki kaydırmalı kategorilerde çıkan kısa ad. Örn. Gözleme</span>
      <input name="name" required value="<?= e($edit['name'] ?? '') ?>">
    </label>
    <label>Kategori adı
      <span class="hint">Sayfa içinde bölüm başlığı. Örn. Gözleme ve Atıştırmalıklar</span>
      <input name="heading" value="<?= e($edit['heading'] ?? '') ?>">
    </label>
    <?php if ($edit): ?>
    <label>Sıra
      <input type="number" name="sort_order" value="<?= (int) $edit['sort_order'] ?>">
    </label>
    <label class="check"><input type="checkbox" name="is_active" <?= (int) $edit['is_active'] ? 'checked' : '' ?>> Menüde göster</label>
    <?php endif; ?>
    <div class="form-actions">
      <?php if ($edit): ?>
        <button class="btn danger" type="submit" name="action" value="delete" <?= (int) $edit['product_count'] > 0 ? 'disabled' : '' ?> onclick="return confirm('Kategori silinsin mi?')">Sil</button>
      <?php endif; ?>
      <div class="push">
        <a class="btn ghost" href="categories.php">Vazgeç</a>
        <button class="btn" type="submit">Kaydet</button>
      </div>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="cat-list">
<?php foreach ($rows as $c): ?>
  <article class="cat-row">
    <div>
      <div class="p-name"><?= e($c['name']) ?></div>
      <div class="p-meta"><?= e($c['heading']) ?> · <?= (int) $c['product_count'] ?> ürün<?= !(int) $c['is_active'] ? ' · gizli' : '' ?></div>
    </div>
    <a class="btn ghost" href="categories.php?id=<?= (int) $c['id'] ?>">Düzenle</a>
  </article>
<?php endforeach; ?>
</div>
<?php admin_footer();