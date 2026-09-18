<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/xlsx_lib.php';

$result = null; // ringkasan hasil import
$errors = [];

/**
 * Mendeteksi field berdasarkan teks header kolom (fleksibel,
 * tidak harus persis sama seperti template, cukup mengandung kata kunci).
 */
function str_has($haystack, $needle)
{
    return $needle === '' || strpos($haystack, $needle) !== false;
}

function str_starts($haystack, $needle)
{
    return $needle === '' || substr($haystack, 0, strlen($needle)) === $needle;
}

function detect_field_map(array $headerRow)
{
    $map = []; // field => column index
    foreach ($headerRow as $i => $label) {
        $norm = mb_strtolower(trim((string) $label));
        if ($norm === '') {
            continue;
        }
        if (str_starts($norm, 'id') && !isset($map['id'])) {
            $map['id'] = $i;
        } elseif (str_has($norm, 'nama lengkap') || $norm === 'nama') {
            $map['full_name'] = $i;
        } elseif (str_starts($norm, 'nisn')) {
            $map['nisn'] = $i;
        } elseif (str_starts($norm, 'nis')) {
            $map['nis'] = $i;
        } elseif (str_has($norm, 'kelas')) {
            $map['class'] = $i;
        } elseif (str_has($norm, 'kelamin')) {
            $map['gender'] = $i;
        } elseif (str_has($norm, 'tempat lahir')) {
            $map['pob'] = $i;
        } elseif (str_has($norm, 'tanggal lahir')) {
            $map['dob'] = $i;
        } elseif (str_has($norm, 'alamat')) {
            $map['address'] = $i;
        } elseif (str_has($norm, 'golongan darah')) {
            $map['blood_type'] = $i;
        } elseif (str_has($norm, 'agama')) {
            $map['religion'] = $i;
        } elseif (str_has($norm, 'orang tua') || str_has($norm, 'wali')) {
            $map['parent_name'] = $i;
        } elseif (str_has($norm, 'nomor kartu') || str_has($norm, 'no kartu')) {
            $map['card_number'] = $i;
        }
    }
    return $map;
}

function cell($row, $map, $field)
{
    if (!isset($map[$field]) || !isset($row[$map[$field]])) {
        return '';
    }
    return trim((string) $row[$map[$field]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal mengupload file. Coba lagi.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $rows = [];

        try {
            if ($ext === 'xlsx') {
                if (!xlsx_supported()) {
                    throw new Exception('Server tidak mendukung file .xlsx (ekstensi ZipArchive/SimpleXML tidak aktif). Silakan simpan file sebagai .csv lalu upload ulang.');
                }
                $rows = xlsx_read($file['tmp_name']);
            } elseif ($ext === 'csv') {
                $handle = fopen($file['tmp_name'], 'r');
                // buang BOM UTF-8 jika ada
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            } else {
                throw new Exception('Format file harus .xlsx atau .csv');
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (!$errors) {
            if (count($rows) < 2) {
                $errors[] = 'File tidak berisi data (hanya header atau kosong).';
            } else {
                $headerRow = array_shift($rows);
                $map = detect_field_map($headerRow);

                if (!isset($map['full_name'])) {
                    $errors[] = 'Kolom "Nama Lengkap" tidak ditemukan di file. Gunakan template resmi agar header terbaca dengan benar.';
                }
            }
        }

        if (!$errors) {
            $inserted = 0;
            $updated = 0;
            $skipped = [];

            $findById = $pdo->prepare('SELECT id, photo FROM students WHERE id = ?');
            $findByNisn = $pdo->prepare('SELECT id, photo FROM students WHERE nisn = ? AND nisn <> "" LIMIT 1');
            $findByNis = $pdo->prepare('SELECT id, photo FROM students WHERE nis = ? AND nis <> "" LIMIT 1');
            $insertStmt = $pdo->prepare('INSERT INTO students (full_name, nisn, nis, class, gender, pob, dob, address, blood_type, religion, parent_name, card_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $updateStmt = $pdo->prepare('UPDATE students SET full_name=?, nisn=?, nis=?, class=?, gender=?, pob=?, dob=?, address=?, blood_type=?, religion=?, parent_name=?, card_number=? WHERE id=?');

            foreach ($rows as $rowIndex => $row) {
                $excelRowNumber = $rowIndex + 2; // +1 header, +1 basis 1
                $fullName = cell($row, $map, 'full_name');
                if ($fullName === '') {
                    continue; // baris kosong dilewati tanpa dianggap error
                }

                $nisn = cell($row, $map, 'nisn');
                $nis = cell($row, $map, 'nis');
                if ($nisn === '' && $nis === '') {
                    $skipped[] = "Baris $excelRowNumber ($fullName): NISN atau NIS wajib diisi salah satu.";
                    continue;
                }

                $genderRaw = mb_strtoupper(cell($row, $map, 'gender'));
                $gender = in_array($genderRaw, ['L', 'P']) ? $genderRaw : (str_starts($genderRaw, 'LAKI') ? 'L' : (str_starts($genderRaw, 'PER') ? 'P' : 'L'));

                $dobRaw = cell($row, $map, 'dob');
                $dob = $dobRaw !== '' ? xlsx_maybe_date($dobRaw) : null;
                if ($dob !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                    $dob = null; // format tak dikenali, kosongkan agar tidak gagal insert
                }

                $values = [
                    $fullName,
                    $nisn,
                    $nis,
                    cell($row, $map, 'class'),
                    $gender,
                    cell($row, $map, 'pob'),
                    $dob,
                    cell($row, $map, 'address'),
                    cell($row, $map, 'blood_type'),
                    cell($row, $map, 'religion'),
                    cell($row, $map, 'parent_name'),
                    cell($row, $map, 'card_number'),
                ];

                // Tentukan target: berdasarkan ID eksplisit, lalu NISN, lalu NIS
                $targetId = null;
                $idCell = cell($row, $map, 'id');
                if ($idCell !== '' && ctype_digit($idCell)) {
                    $findById->execute([(int) $idCell]);
                    $found = $findById->fetch();
                    if ($found) {
                        $targetId = $found['id'];
                    }
                }
                if (!$targetId && $nisn !== '') {
                    $findByNisn->execute([$nisn]);
                    $found = $findByNisn->fetch();
                    if ($found) {
                        $targetId = $found['id'];
                    }
                }
                if (!$targetId && $nis !== '') {
                    $findByNis->execute([$nis]);
                    $found = $findByNis->fetch();
                    if ($found) {
                        $targetId = $found['id'];
                    }
                }

                if ($targetId) {
                    $updateStmt->execute(array_merge($values, [$targetId]));
                    $updated++;
                } else {
                    $insertStmt->execute($values);
                    $inserted++;
                }
            }

            $result = ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-title">Import Data Pelajar dari Excel</div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($result): ?>
    <div class="alert alert-success">
      Import selesai: <strong><?= $result['inserted'] ?></strong> data baru ditambahkan,
      <strong><?= $result['updated'] ?></strong> data diperbarui.
      <?php if ($result['skipped']): ?>
        <?= count($result['skipped']) ?> baris dilewati.
      <?php endif; ?>
    </div>
    <?php if ($result['skipped']): ?>
      <div class="alert alert-danger">
        <strong>Baris yang dilewati:</strong>
        <?php foreach ($result['skipped'] as $s): ?><div><?= h($s) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="btn-row" style="margin-bottom:20px;">
      <a class="btn" href="students.php">Lihat Data Pelajar</a>
    </div>
  <?php endif; ?>

  <div style="background:#f8fafc; border:1px solid var(--border); border-radius:10px; padding:16px; margin-bottom:20px; font-size:13.5px; color:#475569;">
    <strong>Cara pakai:</strong>
    <ol style="margin:8px 0 0; padding-left:20px; line-height:1.8;">
      <li>Unduh <a href="students_template.php">Template Excel</a> lalu isi data pelajar (satu baris = satu pelajar).</li>
      <li>Kolom <strong>NISN</strong> atau <strong>NIS</strong> wajib diisi salah satu, dipakai sebagai kunci pencocokan data.</li>
      <li>Jika NISN/NIS pada file sudah ada di database, data pelajar tersebut akan <strong>diperbarui</strong> (bukan dobel). Jika belum ada, akan dibuat sebagai data baru.</li>
      <li>Format Tanggal Lahir: <code>YYYY-MM-DD</code> (contoh: 2010-05-17). Jika kolom diformat sebagai tanggal di Excel, aplikasi akan otomatis mengonversinya.</li>
      <li>Foto pelajar <strong>tidak bisa</strong> diimpor lewat Excel — tambahkan foto satu per satu lewat menu edit data pelajar.</li>
      <li>Upload file (.xlsx atau .csv) hasil isian Anda di bawah ini.</li>
    </ol>
  </div>

  <form method="post" enctype="multipart/form-data">
    <div class="field">
      <label>Pilih File Excel (.xlsx) atau CSV</label>
      <input type="file" name="import_file" accept=".xlsx,.csv" required>
    </div>
    <div class="btn-row">
      <button class="btn" type="submit">⬆️ Import Data</button>
      <a class="btn btn-secondary" href="students_template.php">⬇️ Unduh Template</a>
      <a class="btn btn-secondary" href="students.php">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
