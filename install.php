<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function schema_sql(PDO $pdo): array
{
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        return [
            'CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                failed_logins INTEGER NOT NULL DEFAULT 0,
                locked_until TEXT NULL
            )',
            'CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                heading TEXT NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1
            )',
            'CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                description TEXT NULL,
                note TEXT NOT NULL DEFAULT \'\',
                price REAL NOT NULL DEFAULT 0,
                image_path TEXT NULL,
                is_featured INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (category_id) REFERENCES categories(id)
            )',
            'CREATE TABLE IF NOT EXISTS settings (
                k TEXT PRIMARY KEY,
                v TEXT NOT NULL
            )',
        ];
    }

    return [
        'CREATE TABLE IF NOT EXISTS admins (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(64) NOT NULL UNIQUE,
          password_hash VARCHAR(255) NOT NULL,
          failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
          locked_until DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS categories (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          slug VARCHAR(64) NOT NULL UNIQUE,
          name VARCHAR(120) NOT NULL,
          heading VARCHAR(160) NOT NULL,
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS products (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          category_id INT UNSIGNED NOT NULL,
          name VARCHAR(180) NOT NULL,
          description TEXT NULL,
          note VARCHAR(255) NOT NULL DEFAULT \'\',
          price DECIMAL(10,2) NOT NULL DEFAULT 0,
          image_path VARCHAR(255) NULL,
          is_featured TINYINT(1) NOT NULL DEFAULT 0,
          sort_order INT NOT NULL DEFAULT 0,
          is_active TINYINT(1) NOT NULL DEFAULT 1,
          CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS settings (
          k VARCHAR(64) PRIMARY KEY,
          v TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];
}

$error = '';
$ok = false;

try {
    $pdo = db();
    if (db_installed($pdo)) {
        $ok = true;
        $error = 'Kurulum zaten tamamlanmış. install.php dosyasını sunucudan silin.';
    }
} catch (Throwable $e) {
    $error = 'Veritabanına bağlanılamadı. includes/config.php içindeki bilgileri kontrol edin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    csrf_verify();
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if ($user === '' || strlen($user) < 3) {
        $error = 'Kullanıcı adı en az 3 karakter olmalı.';
    } elseif (strlen($pass) < 8) {
        $error = 'Şifre en az 8 karakter olmalı.';
    } elseif ($pass !== $pass2) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        try {
            $pdo = db();
            foreach (schema_sql($pdo) as $sql) {
                $pdo->exec($sql);
            }

            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM products');
            $pdo->exec('DELETE FROM categories');
            $pdo->exec('DELETE FROM admins');
            $pdo->exec('DELETE FROM settings');

            $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
                ->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);

            $seed = require __DIR__ . '/includes/seed_data.php';
            $catIds = [];
            $insCat = $pdo->prepare('INSERT INTO categories (slug, name, heading, sort_order) VALUES (?, ?, ?, ?)');
            foreach ($seed['categories'] as $c) {
                $insCat->execute([$c['slug'], $c['name'], $c['heading'], $c['sort_order']]);
                $catIds[$c['slug']] = (int) $pdo->lastInsertId();
            }

            $insP = $pdo->prepare(
                'INSERT INTO products (category_id, name, description, note, price, is_featured, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($seed['products'] as $p) {
                $insP->execute([
                    $catIds[$p['category_slug']],
                    $p['name'],
                    $p['description'],
                    $p['note'],
                    $p['price'],
                    $p['is_featured'] ? 1 : 0,
                    $p['sort_order'],
                ]);
            }

            $set = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?)');
            foreach ([
                'installed' => '1',
                'brand' => 'La Dural',
                'city' => 'Kdz. Ereğli',
                'slogan' => 'sıcak · hızlı · lezzetli',
                'address_short' => 'Orhanlar Mah.',
                'address' => "Orhanlar Mahallesi Atatürk Bulvarı No:29\nKdz. Ereğli",
                'hours' => '07:30 – 01:00',
                'instagram' => 'https://instagram.com/laduralcafe',
                'instagram_label' => '@laduralcafe',
                'notice' => 'Alerji yapabilecek ürünleri lütfen garsona bildiriniz.',
                'notice_sub' => 'Görseller değişkenlik gösterebilir.',
            ] as $k => $v) {
                $set->execute([$k, $v]);
            }

            $pdo->commit();
            $ok = true;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Kurulum başarısız: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kurulum — La Dural</title>
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">
  <main class="card login-card">
    <h1>La Dural menü kurulumu</h1>
    <?php if ($ok): ?>
      <p class="ok">Kurulum tamam. <strong>install.php dosyasını hemen silin.</strong></p>
      <p><a class="btn" href="admin/login.php">Admin girişi</a> <a class="btn ghost" href="index.php">Menüyü gör</a></p>
    <?php else: ?>
      <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
      <p class="muted">Önce MySQL veritabanını oluşturup <code>includes/config.php</code> dosyasını doldurun. 2026 PDF menüsü otomatik yüklenecek.</p>
      <form method="post">
        <?= csrf_field() ?>
        <label>Admin kullanıcı adı
          <input name="username" required minlength="3" autocomplete="username">
        </label>
        <label>Şifre
          <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Şifre tekrar
          <input type="password" name="password2" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn" type="submit">Kur</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
