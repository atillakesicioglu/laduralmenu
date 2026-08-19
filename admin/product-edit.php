<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$product = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $product = $st->fetch() ?: null;
    if (!$product) {
        $_SESSION['flash_err'] = 'Ürün bulunamadı.';
        redirect('products.php');
    }
}

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'save';
    try {
        if ($action === 'delete' && $product) {
            delete_product_image($product['image_path'] ?? null);
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            $_SESSION['flash_ok'] = 'Ürün silindi.';
            redirect('products.php');
        }

        $name = trim((string) $_POST['name']);
        $categoryId = (int) $_POST['category_id'];
        $description = trim((string) $_POST['description']);
        $note = trim((string) $_POST['note']);
        $price = (float) str_replace(',', '.', (string) $_POST['price']);
        $sort = (int) $_POST['sort_order'];
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || $categoryId < 1) {
            throw new RuntimeException('Ad ve kategori gerekli.');
        }

        $image = $product['image_path'] ?? null;
        if (!empty($_POST['remove_image']) && $image) {
            delete_product_image($image);
            $image = null;
        }
        $image = save_product_image($_FILES['image'] ?? null, $image);

        if ($product) {
            $pdo->prepare(
                'UPDATE products SET category_id=?, name=?, description=?, note=?, price=?, image_path=?, is_featured=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([$categoryId, $name, $description, $note, $price, $image, $featured, $sort, $active, $id]);
            $_SESSION['flash_ok'] = 'Ürün güncellendi.';
            redirect('product-edit.php?id=' . $id);
        }

        if ($sort === 0) {
            $sort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM products')->fetchColumn();
        }
        $pdo->prepare(
            'INSERT INTO products (category_id, name, description, note, price, image_path, is_featured, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$categoryId, $name, $description, $note, $price, $image, $featured, $sort, $active]);
        $_SESSION['flash_ok'] = 'Ürün eklendi.';
        redirect('products.php');
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
    }
}

admin_header($product ? 'Ürün düzenle' : 'Ürün ekle', 'products.php');
flash();
?>
<div class="card">
  <h1><?= $product ? 'Ürün düzenle' : 'Ürün ekle' ?></h1>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($product): ?><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><?php endif; ?>
    <label>Ad
      <input name="name" required value="<?= e($product['name'] ?? '') ?>">
    </label>
    <label>Kategori
      <select name="category_id" required>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Açıklama
      <textarea name="description"><?= e($product['description'] ?? '') ?></textarea>
    </label>
    <label>Not (ör. İki kişilik)
      <input name="note" value="<?= e($product['note'] ?? '') ?>">
    </label>
    <label>Fiyat (TL)
      <input name="price" type="number" step="0.01" min="0" required value="<?= e((string) ($product['price'] ?? '')) ?>">
    </label>
    <label>Sıra
      <input name="sort_order" type="number" value="<?= e((string) ($product['sort_order'] ?? '0')) ?>">
    </label>
    <label>Fotoğraf
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
    </label>
    <?php if (!empty($product['image_path'])): ?>
      <p><img class="thumb-sm" src="../<?= e($product['image_path']) ?>" alt=""></p>
      <label class="check"><input type="checkbox" name="remove_image" value="1"> Fotoğrafı kaldır</label>
    <?php endif; ?>
    <label class="check"><input type="checkbox" name="is_featured" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> Öne çıkan</label>
    <label class="check"><input type="checkbox" name="is_active" <?= !isset($product['is_active']) || (int) $product['is_active'] ? 'checked' : '' ?>> Menüde göster</label>
    <div class="row">
      <button class="btn" type="submit" name="action" value="save">Kaydet</button>
      <a class="btn ghost" href="products.php">Liste</a>
    </div>
  </form>
  <?php if ($product): ?>
  <form method="post" style="margin-top:18px" onsubmit="return confirm('Ürün silinsin mi?')">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
    <button class="btn danger" type="submit" name="action" value="delete">Ürünü sil</button>
  </form>
  <?php endif; ?>
</div>
<?php admin_footer();