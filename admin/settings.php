<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();
$keys = ['brand', 'city', 'slogan', 'address_short', 'address', 'hours', 'instagram', 'instagram_label', 'notice', 'notice_sub'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $form = $_POST['form'] ?? 'cafe';
    if ($form === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        $new2 = (string) ($_POST['new2'] ?? '');
        $st = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $st->execute([current_admin_id()]);
        $hash = (string) $st->fetchColumn();
        if (!password_verify($current, $hash)) {
            $_SESSION['flash_err'] = 'Mevcut şifre yanlış.';
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_err'] = 'Yeni şifre en az 8 karakter olmalı.';
        } elseif ($new !== $new2) {
            $_SESSION['flash_err'] = 'Yeni şifreler eşleşmiyor.';
        } else {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), current_admin_id()]);
            $_SESSION['flash_ok'] = 'Şifre değişti.';
        }
    } else {
        foreach ($keys as $k) {
            upsert_setting($pdo, $k, trim((string) ($_POST[$k] ?? '')));
        }
        $_SESSION['flash_ok'] = 'Kafe ayarları kaydedildi.';
    }
    redirect('settings.php');
}

$vals = [];
foreach ($keys as $k) {
    $vals[$k] = setting($pdo, $k);
}

admin_header('Kafe ayarları', 'settings.php');
flash();
?>
<div class="card" style="margin-bottom:16px">
  <h1>Kafe ayarları</h1>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="cafe">
    <div class="grid-2">
      <label>Marka <input name="brand" value="<?= e($vals['brand']) ?>"></label>
      <label>Şehir <input name="city" value="<?= e($vals['city']) ?>"></label>
    </div>
    <label>Slogan <input name="slogan" value="<?= e($vals['slogan']) ?>"></label>
    <label>Kısa adres (üst bar) <input name="address_short" value="<?= e($vals['address_short']) ?>"></label>
    <label>Adres <textarea name="address"><?= e($vals['address']) ?></textarea></label>
    <div class="grid-2">
      <label>Saat <input name="hours" value="<?= e($vals['hours']) ?>"></label>
      <label>Instagram yazısı <input name="instagram_label" value="<?= e($vals['instagram_label']) ?>"></label>
    </div>
    <label>Instagram URL <input name="instagram" value="<?= e($vals['instagram']) ?>"></label>
    <label>Uyarı <input name="notice" value="<?= e($vals['notice']) ?>"></label>
    <label>Uyarı alt metin <input name="notice_sub" value="<?= e($vals['notice_sub']) ?>"></label>
    <div class="form-actions">
      <div class="push">
        <button class="btn" type="submit">Kaydet</button>
      </div>
    </div>
  </form>
</div>
<div class="card">
  <h1>Şifre değiştir</h1>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="password">
    <label>Mevcut şifre <input type="password" name="current" required></label>
    <label>Yeni şifre <input type="password" name="new" required minlength="8"></label>
    <label>Yeni şifre tekrar <input type="password" name="new2" required minlength="8"></label>
    <div class="form-actions">
      <div class="push">
        <button class="btn" type="submit">Şifreyi güncelle</button>
      </div>
    </div>
  </form>
</div>
<?php admin_footer();