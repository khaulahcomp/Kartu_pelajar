<?php
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    flash_set('danger', 'Data pelajar tidak ditemukan.');
    redirect('students.php');
}

$settings = get_settings($pdo);

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-title">Kartu Pelajar - <?= h($student['full_name']) ?></div>

  <div class="btn-row" style="margin-bottom:20px;">
    <button class="btn" onclick="window.print()">🖨️ Cetak Kartu (Browser)</button>
    <a class="btn btn-secondary" href="card_print_pdf.php?ids=<?= (int) $student['id'] ?>" target="_blank">📄 Unduh PDF</a>
    <a class="btn btn-secondary" href="student_form.php?id=<?= (int) $student['id'] ?>">✏️ Edit Data</a>
    <a class="btn btn-secondary" href="students.php">← Kembali</a>
  </div>

  <div class="print-area">
    <div class="card-preview-wrap">
      <?php include __DIR__ . '/includes/card_template.php'; ?>
    </div>
    <div class="card-caption">Kartu Tanda Pengenal Pelajar</div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
