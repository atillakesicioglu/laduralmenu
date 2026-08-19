<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$err = '';
if (current_admin_id() !== null) {
    redirect('products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $result = login_admin(db(), trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
    if ($result === true) {
        redirect('products.php');
    }
    $err = $result;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin girişi — La Dural</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
  <div class="login-page">
    <main class="card login-card">
      <img src="../assets/img/logo.png?v=2" alt="La Dural">
      <h1>Yönetim paneli</h1>
      <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <label>Kullanıcı adı
          <input name="username" required autocomplete="username">
        </label>
        <label>Şifre
          <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn" type="submit">Giriş yap</button>
      </form>
    </main>
  </div>
</body>
</html>
