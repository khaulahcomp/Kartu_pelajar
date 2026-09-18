<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$error = null;

// Cek apakah sudah ada admin. Jika belum, arahkan ke install.php
$hasAdmin = (int) $pdo->query('SELECT COUNT(*) c FROM admin_users')->fetch()['c'] > 0;
if (!$hasAdmin) {
    redirect('install.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
        redirect('index.php');
    } else {
        $error = 'Username atau password salah.';
    }
}

$settings = get_settings($pdo);
$primary = $settings['theme_primary'] ?: '#1a3c6e';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?= h($settings['school_name']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>:root{ --primary: <?= h($primary) ?>; }</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <?php if (!empty($settings['logo'])): ?>
      <img src="<?= h(UPLOAD_LOGO_URL . $settings['logo']) ?>" class="login-logo" alt="Logo">
    <?php endif; ?>
    <h1><?= h($settings['school_name']) ?></h1>
    <p class="sub">Aplikasi Kartu Pelajar</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn" style="width:100%; margin-top:8px;" type="submit">Masuk</button>
    </form>
  </div>
</div>
</body>
</html>
