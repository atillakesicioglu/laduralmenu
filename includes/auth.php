<?php

declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('ladural_admin');
    session_start();
}

function current_admin_id(): ?int
{
    $id = $_SESSION['admin_id'] ?? null;
    return is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
}

function require_admin(): void
{
    if (current_admin_id() === null) {
        redirect('login.php');
    }
}

function login_admin(PDO $pdo, string $username, string $password)
{
    $st = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $st->execute([$username]);
    $admin = $st->fetch();
    if (!$admin) {
        return 'Kullanıcı adı veya şifre hatalı.';
    }

    if (!empty($admin['locked_until']) && strtotime((string) $admin['locked_until']) > time()) {
        return 'Çok fazla hatalı deneme. Bir süre sonra tekrar deneyin.';
    }

    if (!password_verify($password, $admin['password_hash'])) {
        $fails = (int) $admin['failed_logins'] + 1;
        $locked = $fails >= 5 ? date('Y-m-d H:i:s', time() + 15 * 60) : null;
        $pdo->prepare('UPDATE admins SET failed_logins = ?, locked_until = ? WHERE id = ?')
            ->execute([$fails, $locked, $admin['id']]);
        if ($locked) {
            return '5 hatalı giriş. Panel 15 dakika kilitlendi.';
        }
        return 'Kullanıcı adı veya şifre hatalı.';
    }

    $pdo->prepare('UPDATE admins SET failed_logins = 0, locked_until = NULL WHERE id = ?')
        ->execute([$admin['id']]);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];

    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}
