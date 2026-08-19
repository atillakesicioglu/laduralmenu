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
        $price = parse_price((string) $_POST['price']);
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
            $sort = (int) ($_POST['sort_order'] ?? $product['sort_order']);
            $pdo->prepare(
                'UPDATE products SET category_id=?, name=?, description=?, note=?, price=?, image_path=?, is_featured=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([$categoryId, $name, $description, $note, $price, $image, $featured, $sort, $active, $id]);
            $_SESSION['flash_ok'] = 'Ürün güncellendi.';
            redirect('product-edit.php?id=' . $id);
        }

        $st = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM products WHERE category_id = ?');
        $st->execute([$categoryId]);
        $sort = (int) $st->fetchColumn();

        $pdo->prepare(
            'INSERT INTO products (category_id, name, description, note, price, image_path, is_featured, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$categoryId, $name, $description, $note, $price, $image, $featured, $sort, $active]);
        $_SESSION['flash_ok'] = 'Ürün eklendi.';
        redirect('products.php?cat=' . $categoryId);
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
    }
}

admin_header($product ? 'Ürün düzenle' : 'Yeni ürün', 'products.php');
flash();
$priceValue = isset($product['price']) ? format_price_input($product['price']) : '';
?>
<div class="card">
  <h1><?= $product ? 'Ürün düzenle' : 'Yeni ürün' ?></h1>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($product): ?><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><?php endif; ?>
    <label>Ürün adı
      <input name="name" required value="<?= e($product['name'] ?? '') ?>">
    </label>
    <div class="grid-2">
      <label>Kategori
        <select name="category_id" required>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Fiyat (TL)
        <input class="js-price" name="price" inputmode="decimal" required placeholder="80,00" value="<?= e($priceValue) ?>">
        <span class="hint">80 yazınca 80,00 olur.</span>
      </label>
    </div>
    <label>Açıklama
      <textarea name="description"><?= e($product['description'] ?? '') ?></textarea>
    </label>
    <label>Not
      <input name="note" value="<?= e($product['note'] ?? '') ?>" placeholder="Örn. İki kişilik">
    </label>
    <label>Fotoğraf
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
    </label>
    <?php if (!empty($product['image_path'])): ?>
      <p><img class="edit-preview" src="../<?= e($product['image_path']) ?>" alt=""></p>
      <label class="check"><input type="checkbox" name="remove_image" value="1"> Fotoğrafı kaldır</label>
    <?php endif; ?>
    <label class="check"><input type="checkbox" name="is_featured" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> Öne çıkanlarda göster</label>
    <label class="check"><input type="checkbox" name="is_active" <?= !isset($product['is_active']) || (int) $product['is_active'] ? 'checked' : '' ?>> Menüde görünsün</label>
    <?php if ($product): ?>
      <label>Sıra
        <input name="sort_order" type="number" value="<?= e((string) $product['sort_order']) ?>">
      </label>
    <?php endif; ?>
    <div class="form-actions">
      <?php if ($product): ?>
        <button class="btn danger" type="submit" name="action" value="delete" onclick="return confirm('Ürün silinsin mi?')">Sil</button>
      <?php endif; ?>
      <div class="push">
        <a class="btn ghost" href="products.php">Vazgeç</a>
        <button class="btn" type="submit" name="action" value="save">Kaydet</button>
      </div>
    </div>
  </form>
</div>
<?php admin_footer(price_js());