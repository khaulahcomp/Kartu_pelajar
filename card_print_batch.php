<?php
require_once __DIR__ . '/includes/auth.php';

$idsParam = $_GET['ids'] ?? '';
$ids = array_filter(array_map('intval', explode(',', $idsParam)));

if (!$ids) {
    flash_set('danger', 'Tidak ada pelajar yang dipilih.');
    redirect('students.php');
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM students WHERE id IN ($placeholders) ORDER BY full_name ASC");
$stmt->execute($ids);
$studentsList = $stmt->fetchAll();

$settings = get_settings($pdo);
$primary = $settings['theme_primary'] ?: '#1a3c6e';
$secondary = $settings['theme_secondary'] ?: '#f4b400';
$textOnPrimary = $settings['theme_text_on_primary'] ?: contrast_color($primary);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Kartu Pelajar</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  :root{ --primary: <?= h($primary) ?>; --secondary: <?= h($secondary) ?>; --on-primary: <?= h($textOnPrimary) ?>; }
  body{ background:#e9edf2; padding:20px; }
  .batch-toolbar{ margin-bottom:16px; display:flex; gap:10px; }
  .batch-grid{ display:flex; flex-wrap:wrap; gap:6mm; }
  @media print{ .batch-toolbar{ display:none; } body{ background:#fff; padding:0; } }
</style>
</head>
<body>
  <div class="batch-toolbar">
    <button class="btn" onclick="window.print()">🖨️ Cetak Semua (<?= count($studentsList) ?> kartu)</button>
    <a class="btn btn-secondary" href="students.php">← Kembali</a>
  </div>
  <div class="print-area batch-grid">
    <?php foreach ($studentsList as $student): ?>
      <?php include __DIR__ . '/includes/card_template.php'; ?>
    <?php endforeach; ?>
  </div>
</body>
</html>
