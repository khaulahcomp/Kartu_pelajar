<?php
require_once __DIR__ . '/includes/auth.php';

$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($q !== '') {
    $where = 'WHERE full_name LIKE ? OR nisn LIKE ? OR nis LIKE ? OR class LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

$stmt = $pdo->prepare("SELECT * FROM students $where ORDER BY full_name ASC");
$stmt->execute($params);
$students = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-title">Data Pelajar</div>

  <form class="search-bar" method="get">
    <input type="text" name="q" placeholder="Cari nama, NISN, NIS, atau kelas..." value="<?= h($q) ?>">
    <button class="btn btn-secondary" type="submit">Cari</button>
    <a class="btn" href="student_form.php">+ Tambah Pelajar</a>
    <a class="btn btn-secondary" href="students_import.php">⬆️ Import Excel</a>
    <a class="btn btn-secondary" href="students_export.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">⬇️ Export Excel</a>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><input type="checkbox" id="checkAll"></th>
          <th>Foto</th><th>Nama Lengkap</th><th>NISN</th><th>NIS</th><th>Kelas</th><th>TTL</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$students): ?>
          <tr><td colspan="8" style="text-align:center; color:#888;">Tidak ada data ditemukan.</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><input type="checkbox" class="row-check" value="<?= (int) $s['id'] ?>"></td>
          <td>
            <?php if ($s['photo']): ?>
              <img class="avatar-sm" src="<?= h(UPLOAD_PHOTO_URL . $s['photo']) ?>" alt="">
            <?php else: ?>
              <div class="avatar-sm" style="background:#e5e7eb;"></div>
            <?php endif; ?>
          </td>
          <td><?= h($s['full_name']) ?></td>
          <td><?= h($s['nisn'] ?: '-') ?></td>
          <td><?= h($s['nis'] ?: '-') ?></td>
          <td><span class="badge"><?= h($s['class'] ?: '-') ?></span></td>
          <td><?= h(ttl($s['pob'], $s['dob'])) ?></td>
          <td style="white-space:nowrap;">
            <a class="btn btn-sm btn-secondary" href="card.php?id=<?= (int) $s['id'] ?>">Kartu</a>
            <a class="btn btn-sm btn-secondary" href="student_form.php?id=<?= (int) $s['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-danger" href="student_delete.php?id=<?= (int) $s['id'] ?>"
               onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($students): ?>
  <div class="btn-row" style="margin-top:16px;">
    <button class="btn" onclick="printSelected()">🖨️ Cetak Kartu Terpilih (Browser)</button>
    <button class="btn btn-secondary" onclick="pdfSelected()">📄 PDF Kartu Terpilih</button>
    <a class="btn btn-secondary" href="card_print_pdf.php?all=1<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">📄 PDF Kartu Kolektif (Semua<?= $q !== '' ? ' Hasil Pencarian' : '' ?>)</a>
  </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('checkAll')?.addEventListener('change', function(){
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});
function getSelectedIds(){
  return Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
}
function printSelected(){
  const ids = getSelectedIds();
  if (ids.length === 0){ alert('Pilih minimal satu pelajar terlebih dahulu.'); return; }
  window.open('card_print_batch.php?ids=' + ids.join(','), '_blank');
}
function pdfSelected(){
  const ids = getSelectedIds();
  if (ids.length === 0){ alert('Pilih minimal satu pelajar terlebih dahulu.'); return; }
  window.open('card_print_pdf.php?ids=' + ids.join(','), '_blank');
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
