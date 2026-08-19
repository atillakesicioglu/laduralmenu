<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
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
    redirect('password.php');
}

admin_header('Şifre', 'password.php');
flash();
?>
<div class="card">
  <h1>Şifre değiştir</h1>
  <form method="post">
    <?= csrf_field() ?>
    <label>Mevcut şifre <input type="password" name="current" required></label>
    <label>Yeni şifre <input type="password" name="new" required minlength="8"></label>
    <label>Yeni şifre tekrar <input type="password" name="new2" required minlength="8"></label>
    <button class="btn" type="submit">Güncelle</button>
  </form>
</div>
<?php admin_footer();