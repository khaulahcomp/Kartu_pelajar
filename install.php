<?php
/**
 * install.php
 * Dijalankan SEKALI setelah import database.sql, untuk membuat akun admin pertama.
 * Setelah selesai, HAPUS file ini dari server untuk keamanan.
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$hasAdmin = (int) $pdo->query('SELECT COUNT(*) c FROM admin_users')->fetch()['c'] > 0;
if ($hasAdmin) {
    redirect('login.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password, full_name) VALUES (?, ?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName]);
        flash_set('success', 'Akun admin berhasil dibuat. Silakan login. Jangan lupa hapus install.php!');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instalasi - Aplikasi Kartu Pelajar</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box" style="max-width:420px;">
    <h1>Instalasi Awal</h1>
    <p class="sub">Buat akun administrator pertama untuk aplikasi Kartu Pelajar</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="field">
        <label>Nama Lengkap</label>
        <input type="text" name="full_name" required>
      </div>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
        <small>Minimal 6 karakter</small>
      </div>
      <div class="field">
        <label>Konfirmasi Password</label>
        <input type="password" name="password_confirm" required>
      </div>
      <button class="btn" style="width:100%; margin-top:8px;" type="submit">Buat Akun & Selesai</button>
    </form>
  </div>
</div>
</body>
</html>
