<?php
require_once __DIR__ . '/includes/auth.php';

$totalStudents = (int) $pdo->query('SELECT COUNT(*) c FROM students')->fetch()['c'];
$totalMale = (int) $pdo->query("SELECT COUNT(*) c FROM students WHERE gender='L'")->fetch()['c'];
$totalFemale = (int) $pdo->query("SELECT COUNT(*) c FROM students WHERE gender='P'")->fetch()['c'];
$totalClasses = (int) $pdo->query("SELECT COUNT(DISTINCT class) c FROM students WHERE class IS NOT NULL AND class <> ''")->fetch()['c'];

$recent = $pdo->query('SELECT * FROM students ORDER BY id DESC LIMIT 6')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="num"><?= $totalStudents ?></div>
    <div class="label">Total Pelajar</div>
  </div>
  <div class="stat-card">
    <div class="num"><?= $totalMale ?></div>
    <div class="label">Laki-laki</div>
  </div>
  <div class="stat-card">
    <div class="num"><?= $totalFemale ?></div>
    <div class="label">Perempuan</div>
  </div>
  <div class="stat-card">
    <div class="num"><?= $totalClasses ?></div>
    <div class="label">Jumlah Kelas</div>
  </div>
</div>

<div class="panel">
  <div class="panel-title">Pelajar Terbaru</div>
  <div class="btn-row" style="margin-bottom:16px;">
    <a href="student_form.php" class="btn">+ Tambah Pelajar</a>
    <a href="students.php" class="btn btn-secondary">Lihat Semua Data</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Foto</th><th>Nama Lengkap</th><th>NISN/NIS</th><th>Kelas</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="5" style="text-align:center; color:#888;">Belum ada data pelajar.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $s): ?>
        <tr>
          <td>
            <?php if ($s['photo']): ?>
              <img class="avatar-sm" src="<?= h(UPLOAD_PHOTO_URL . $s['photo']) ?>" alt="">
            <?php else: ?>
              <div class="avatar-sm" style="background:#e5e7eb;"></div>
            <?php endif; ?>
          </td>
          <td><?= h($s['full_name']) ?></td>
          <td><?= h($s['nisn'] ?: $s['nis'] ?: '-') ?></td>
          <td><span class="badge"><?= h($s['class'] ?: '-') ?></span></td>
          <td>
            <a class="btn btn-sm btn-secondary" href="card.php?id=<?= (int) $s['id'] ?>">Kartu</a>
            <a class="btn btn-sm btn-secondary" href="student_form.php?id=<?= (int) $s['id'] ?>">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
