<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$keys = ['brand', 'city', 'slogan', 'address_short', 'address', 'hours', 'instagram', 'instagram_label', 'notice', 'notice_sub'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($keys as $k) {
        upsert_setting($pdo, $k, trim((string) ($_POST[$k] ?? '')));
    }
    $_SESSION['flash_ok'] = 'Ayarlar kaydedildi.';
    redirect('settings.php');
}

$vals = [];
foreach ($keys as $k) {
    $vals[$k] = setting($pdo, $k);
}

admin_header('Ayarlar', 'settings.php');
flash();
?>
<div class="card">
  <h1>Menü bilgileri</h1>
  <form method="post">
    <?= csrf_field() ?>
    <label>Marka <input name="brand" value="<?= e($vals['brand']) ?>"></label>
    <label>Şehir <input name="city" value="<?= e($vals['city']) ?>"></label>
    <label>Slogan <input name="slogan" value="<?= e($vals['slogan']) ?>"></label>
    <label>Kısa adres (üst bar) <input name="address_short" value="<?= e($vals['address_short']) ?>"></label>
    <label>Adres <textarea name="address"><?= e($vals['address']) ?></textarea></label>
    <label>Saat <input name="hours" value="<?= e($vals['hours']) ?>"></label>
    <label>Instagram URL <input name="instagram" value="<?= e($vals['instagram']) ?>"></label>
    <label>Instagram yazısı <input name="instagram_label" value="<?= e($vals['instagram_label']) ?>"></label>
    <label>Uyarı <input name="notice" value="<?= e($vals['notice']) ?>"></label>
    <label>Uyarı alt metin <input name="notice_sub" value="<?= e($vals['notice_sub']) ?>"></label>
    <button class="btn" type="submit">Kaydet</button>
  </form>
</div>
<?php admin_footer();