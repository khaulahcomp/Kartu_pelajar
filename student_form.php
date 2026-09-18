<?php
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$student = [
    'id' => 0, 'photo' => null, 'full_name' => '', 'nisn' => '', 'nis' => '',
    'class' => '', 'gender' => 'L', 'pob' => '', 'dob' => '', 'address' => '',
    'blood_type' => '', 'religion' => '', 'parent_name' => '', 'card_number' => '',
];
$errors = [];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Data pelajar tidak ditemukan.');
        redirect('students.php');
    }
    $student = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student['full_name'] = trim($_POST['full_name'] ?? '');
    $student['nisn'] = trim($_POST['nisn'] ?? '');
    $student['nis'] = trim($_POST['nis'] ?? '');
    $student['class'] = trim($_POST['class'] ?? '');
    $student['gender'] = $_POST['gender'] ?? 'L';
    $student['pob'] = trim($_POST['pob'] ?? '');
    $student['dob'] = $_POST['dob'] ?? '';
    $student['address'] = trim($_POST['address'] ?? '');
    $student['blood_type'] = trim($_POST['blood_type'] ?? '');
    $student['religion'] = trim($_POST['religion'] ?? '');
    $student['parent_name'] = trim($_POST['parent_name'] ?? '');
    $student['card_number'] = trim($_POST['card_number'] ?? '');

    if ($student['full_name'] === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if ($student['nisn'] === '' && $student['nis'] === '') {
        $errors[] = 'NISN atau NIS wajib diisi salah satu.';
    }

    $newPhoto = null;
    try {
        $newPhoto = upload_image('photo', UPLOAD_PHOTO_DIR, 'siswa');
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        if ($newPhoto) {
            if (!empty($student['photo'])) {
                delete_upload_file(UPLOAD_PHOTO_DIR, $student['photo']);
            }
            $student['photo'] = $newPhoto;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE students SET photo=?, full_name=?, nisn=?, nis=?, class=?, gender=?, pob=?, dob=?, address=?, blood_type=?, religion=?, parent_name=?, card_number=? WHERE id=?');
            $stmt->execute([
                $student['photo'], $student['full_name'], $student['nisn'], $student['nis'],
                $student['class'], $student['gender'], $student['pob'], $student['dob'] ?: null,
                $student['address'], $student['blood_type'], $student['religion'],
                $student['parent_name'], $student['card_number'], $id,
            ]);
            flash_set('success', 'Data pelajar berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO students (photo, full_name, nisn, nis, class, gender, pob, dob, address, blood_type, religion, parent_name, card_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $student['photo'], $student['full_name'], $student['nisn'], $student['nis'],
                $student['class'], $student['gender'], $student['pob'], $student['dob'] ?: null,
                $student['address'], $student['blood_type'], $student['religion'],
                $student['parent_name'], $student['card_number'],
            ]);
            $id = (int) $pdo->lastInsertId();
            flash_set('success', 'Data pelajar berhasil ditambahkan.');
        }
        redirect('students.php');
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-title"><?= $id ? 'Edit Data Pelajar' : 'Tambah Data Pelajar' ?></div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="form-grid">
      <div>
        <div class="field">
          <label>Nama Lengkap *</label>
          <input type="text" name="full_name" value="<?= h($student['full_name']) ?>" required>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>NISN</label>
            <input type="text" name="nisn" value="<?= h($student['nisn']) ?>">
          </div>
          <div class="field">
            <label>NIS</label>
            <input type="text" name="nis" value="<?= h($student['nis']) ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Kelas</label>
            <input type="text" name="class" value="<?= h($student['class']) ?>" placeholder="Contoh: IX-A">
          </div>
          <div class="field">
            <label>Jenis Kelamin</label>
            <select name="gender">
              <option value="L" <?= $student['gender'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
              <option value="P" <?= $student['gender'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Tempat Lahir</label>
            <input type="text" name="pob" value="<?= h($student['pob']) ?>">
          </div>
          <div class="field">
            <label>Tanggal Lahir</label>
            <input type="date" name="dob" value="<?= h($student['dob']) ?>">
          </div>
        </div>
        <div class="field">
          <label>Alamat *</label>
          <textarea name="address" required><?= h($student['address']) ?></textarea>
        </div>
      </div>

      <div>
        <div class="field">
          <label>Foto Pelajar</label>
          <?php if (!empty($student['photo'])): ?>
            <img src="<?= h(UPLOAD_PHOTO_URL . $student['photo']) ?>" style="width:100px;height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border);margin-bottom:8px;">
          <?php endif; ?>
          <input type="file" name="photo" accept="image/png, image/jpeg, image/webp">
          <small>Disarankan foto rasio 3:4 (portrait), maks 3MB.</small>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Golongan Darah</label>
            <input type="text" name="blood_type" value="<?= h($student['blood_type']) ?>" placeholder="A/B/AB/O">
          </div>
          <div class="field">
            <label>Agama</label>
            <input type="text" name="religion" value="<?= h($student['religion']) ?>">
          </div>
        </div>
        <div class="field">
          <label>Nama Orang Tua/Wali</label>
          <input type="text" name="parent_name" value="<?= h($student['parent_name']) ?>">
        </div>
        <div class="field">
          <label>Nomor Kartu (opsional)</label>
          <input type="text" name="card_number" value="<?= h($student['card_number']) ?>" placeholder="Kosongkan jika otomatis">
        </div>
      </div>
    </div>

    <div class="btn-row">
      <button class="btn" type="submit">💾 Simpan</button>
      <a class="btn btn-secondary" href="students.php">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
