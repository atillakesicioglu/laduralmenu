<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$err = '';
if (current_admin_id() !== null) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $result = login_admin(db(), trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
    if ($result === true) {
        redirect('index.php');
    }
    $err = $result;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin girişi</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
  <main class="card login-card">
    <h1>La Dural admin</h1>
    <?php if ($err): ?><p class="err"><?= e($err) ?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Kullanıcı adı
        <input name="username" required autocomplete="username">
      </label>
      <label>Şifre
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="btn" type="submit">Giriş</button>
    </form>
  </main>
</body>
</html>
