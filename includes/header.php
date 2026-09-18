<?php
$settings = get_settings($pdo);
$primary = $settings['theme_primary'] ?: '#1a3c6e';
$secondary = $settings['theme_secondary'] ?: '#f4b400';
$textOnPrimary = $settings['theme_text_on_primary'] ?: contrast_color($primary);
$flash = flash_get();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($settings['school_name']) ?> - Aplikasi Kartu Pelajar</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  :root{
    --primary: <?= h($primary) ?>;
    --secondary: <?= h($secondary) ?>;
    --on-primary: <?= h($textOnPrimary) ?>;
  }
</style>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <?php if (!empty($settings['logo'])): ?>
        <img src="<?= h(UPLOAD_LOGO_URL . $settings['logo']) ?>" alt="Logo" class="sidebar-logo">
      <?php endif; ?>
      <div class="sidebar-brand-text"><?= h($settings['school_name']) ?></div>
    </div>
    <nav class="sidebar-nav">
      <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">📊 Dashboard</a>
      <a href="students.php" class="<?= in_array($currentPage, ['students.php','student_form.php','students_import.php','students_export.php','students_template.php']) ? 'active' : '' ?>">🎓 Data Pelajar</a>
      <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">⚙️ Pengaturan</a>
      <a href="logout.php">🚪 Keluar</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="topbar">
      <button class="burger" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
      <div class="topbar-title"><?= h($settings['school_name']) ?></div>
      <div class="topbar-user">👤 <?= h($_SESSION['admin_name'] ?? 'Admin') ?></div>
    </header>
    <div class="content-area">
      <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
      <?php endif; ?>
