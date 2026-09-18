<?php
require_once __DIR__ . '/includes/auth.php';

$stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
$settings = $stmt->fetch();
if (!$settings) {
    // seharusnya tidak terjadi karena database.sql sudah insert 1 baris default
    $pdo->exec("INSERT INTO settings (school_name) VALUES ('Nama Sekolah')");
    $settings = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1')->fetch();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'school_name' => trim($_POST['school_name'] ?? ''),
        'npsn' => trim($_POST['npsn'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'website' => trim($_POST['website'] ?? ''),
        'headmaster_name' => trim($_POST['headmaster_name'] ?? ''),
        'headmaster_nip' => trim($_POST['headmaster_nip'] ?? ''),
        'academic_year' => trim($_POST['academic_year'] ?? ''),
        'card_validity' => trim($_POST['card_validity'] ?? ''),
        'theme_primary' => $_POST['theme_primary'] ?? '#1a3c6e',
        'theme_secondary' => $_POST['theme_secondary'] ?? '#f4b400',
        'theme_card_opacity' => (int) ($_POST['theme_card_opacity'] ?? 12),
    ];

    // Warna area kartu: kosong/"auto" berarti ikut warna utama otomatis
    $cardBgMode = $_POST['card_bg_mode'] ?? 'auto';
    $data['theme_card_bg'] = ($cardBgMode === 'custom' && !empty($_POST['theme_card_bg']))
        ? $_POST['theme_card_bg']
        : null;

    if ($data['theme_card_opacity'] < 0) {
        $data['theme_card_opacity'] = 0;
    } elseif ($data['theme_card_opacity'] > 40) {
        $data['theme_card_opacity'] = 40;
    }

    if ($data['school_name'] === '') {
        $errors[] = 'Nama sekolah wajib diisi.';
    }
    foreach (['theme_primary', 'theme_secondary'] as $colorField) {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $data[$colorField])) {
            $errors[] = 'Format warna tidak valid.';
        }
    }
    if ($data['theme_card_bg'] !== null && !preg_match('/^#[0-9a-fA-F]{6}$/', $data['theme_card_bg'])) {
        $errors[] = 'Format warna area kartu tidak valid.';
    }

    $newLogo = null;
    try {
        $newLogo = upload_image('logo', UPLOAD_LOGO_DIR, 'logo');
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        $data['theme_text_on_primary'] = contrast_color($data['theme_primary']);
        $data['logo'] = $settings['logo'];

        if ($newLogo) {
            if (!empty($settings['logo'])) {
                delete_upload_file(UPLOAD_LOGO_DIR, $settings['logo']);
            }
            $data['logo'] = $newLogo;
        }

        // Hapus logo jika dicentang
        if (!empty($_POST['remove_logo']) && !$newLogo) {
            delete_upload_file(UPLOAD_LOGO_DIR, $settings['logo']);
            $data['logo'] = null;
        }

        try {
            $stmt = $pdo->prepare('UPDATE settings SET school_name=?, npsn=?, address=?, phone=?, email=?, website=?, headmaster_name=?, headmaster_nip=?, academic_year=?, card_validity=?, theme_primary=?, theme_secondary=?, theme_card_bg=?, theme_card_opacity=?, theme_text_on_primary=?, logo=? WHERE id=?');
            $stmt->execute([
                $data['school_name'], $data['npsn'], $data['address'], $data['phone'], $data['email'],
                $data['website'], $data['headmaster_name'], $data['headmaster_nip'], $data['academic_year'],
                $data['card_validity'], $data['theme_primary'], $data['theme_secondary'],
                $data['theme_card_bg'], $data['theme_card_opacity'],
                $data['theme_text_on_primary'], $data['logo'], $settings['id'],
            ]);
            flash_set('success', 'Pengaturan berhasil disimpan.');
            redirect('settings.php');
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'theme_card') !== false || stripos($e->getMessage(), 'Unknown column') !== false) {
                $errors[] = 'Database belum diperbarui untuk fitur warna kartu ini. Silakan import file migration_v3_warna_kartu.sql lewat phpMyAdmin terlebih dahulu, lalu coba simpan lagi.';
            } else {
                $errors[] = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
            }
        }
    }

    if ($errors) {
        $settings = array_merge($settings, $data);
    }
}

// data contoh untuk preview kartu
$previewStudent = [
    'id' => 0, 'photo' => null, 'full_name' => 'Nama Pelajar Contoh',
    'nisn' => '0012345678', 'nis' => '', 'class' => 'IX-A', 'pob' => 'Bandung',
    'dob' => '2010-05-17', 'address' => 'Jl. Contoh Alamat No. 10, Kota Contoh',
    'card_number' => 'ID-00001',
];

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-title">Pengaturan Sekolah &amp; Tampilan Kartu</div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="settingsForm">
    <div class="form-grid">
      <div>
        <h3 style="margin-top:0;color:var(--primary);font-size:15px;">Identitas Sekolah</h3>
        <div class="field">
          <label>Nama Sekolah *</label>
          <input type="text" name="school_name" value="<?= h($settings['school_name']) ?>" required>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>NPSN</label>
            <input type="text" name="npsn" value="<?= h($settings['npsn']) ?>">
          </div>
          <div class="field">
            <label>Tahun Ajaran</label>
            <input type="text" name="academic_year" value="<?= h($settings['academic_year']) ?>" placeholder="2026/2027">
          </div>
        </div>
        <div class="field">
          <label>Alamat Sekolah</label>
          <textarea name="address"><?= h($settings['address']) ?></textarea>
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Telepon</label>
            <input type="text" name="phone" value="<?= h($settings['phone']) ?>">
          </div>
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" value="<?= h($settings['email']) ?>">
          </div>
        </div>
        <div class="field">
          <label>Website</label>
          <input type="text" name="website" value="<?= h($settings['website']) ?>">
        </div>
        <div class="form-grid">
          <div class="field">
            <label>Nama Kepala Sekolah</label>
            <input type="text" name="headmaster_name" value="<?= h($settings['headmaster_name']) ?>">
          </div>
          <div class="field">
            <label>NIP Kepala Sekolah</label>
            <input type="text" name="headmaster_nip" value="<?= h($settings['headmaster_nip']) ?>">
          </div>
        </div>
        <div class="field">
          <label>Keterangan Masa Berlaku Kartu</label>
          <input type="text" name="card_validity" value="<?= h($settings['card_validity']) ?>" placeholder="Berlaku selama menjadi peserta didik aktif">
        </div>
      </div>

      <div>
        <h3 style="margin-top:0;color:var(--primary);font-size:15px;">Logo Sekolah</h3>
        <div class="field">
          <?php if (!empty($settings['logo'])): ?>
            <img src="<?= h(UPLOAD_LOGO_URL . $settings['logo']) ?>" style="width:80px;height:80px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:6px;margin-bottom:8px;">
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;">
              <input type="checkbox" name="remove_logo" value="1" style="width:auto;"> Hapus logo saat ini
            </label>
          <?php endif; ?>
          <input type="file" name="logo" accept="image/png, image/jpeg, image/webp">
          <small>Gunakan gambar persegi (contoh 300x300px) untuk hasil terbaik, maks 3MB.</small>
        </div>

        <h3 style="color:var(--primary);font-size:15px;">Tema Warna Aplikasi &amp; Kartu</h3>
        <div class="field">
          <label>Warna Utama (Primary)</label>
          <div class="color-swatch-row">
            <input type="color" id="theme_primary" name="theme_primary" value="<?= h($settings['theme_primary']) ?>">
            <span class="color-hex" id="theme_primary_hex"><?= h($settings['theme_primary']) ?></span>
          </div>
          <small>Dipakai untuk header kartu, sidebar, dan aksen utama aplikasi.</small>
        </div>
        <div class="field">
          <label>Warna Aksen (Secondary)</label>
          <div class="color-swatch-row">
            <input type="color" id="theme_secondary" name="theme_secondary" value="<?= h($settings['theme_secondary']) ?>">
            <span class="color-hex" id="theme_secondary_hex"><?= h($settings['theme_secondary']) ?></span>
          </div>
          <small>Dipakai sebagai garis aksen pada kartu pelajar.</small>
        </div>

        <div class="field">
          <label>Warna Area Kartu (Tengah)</label>
          <div style="display:flex; gap:18px; margin-bottom:8px;">
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;">
              <input type="radio" name="card_bg_mode" value="auto" id="card_bg_auto" style="width:auto;" <?= empty($settings['theme_card_bg']) ? 'checked' : '' ?>>
              Otomatis (ikut Warna Utama)
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-weight:400;">
              <input type="radio" name="card_bg_mode" value="custom" id="card_bg_custom" style="width:auto;" <?= !empty($settings['theme_card_bg']) ? 'checked' : '' ?>>
              Warna Kustom
            </label>
          </div>
          <div class="color-swatch-row">
            <input type="color" id="theme_card_bg" name="theme_card_bg" value="<?= h($settings['theme_card_bg'] ?: $settings['theme_primary']) ?>">
            <span class="color-hex" id="theme_card_bg_hex"><?= h($settings['theme_card_bg'] ?: $settings['theme_primary']) ?></span>
          </div>
          <div style="display:flex; gap:8px; flex-wrap:wrap; margin:10px 0;">
            <?php
            $cardBgPresets = [
                ['name' => 'Biru Elegan', 'color' => '#1a3c6e'],
                ['name' => 'Hijau Zamrud', 'color' => '#0f6b52'],
                ['name' => 'Ungu Lembut', 'color' => '#5b3a8e'],
                ['name' => 'Merah Marun', 'color' => '#7a2438'],
                ['name' => 'Abu Elegan', 'color' => '#4a4f57'],
                ['name' => 'Emas Krem', 'color' => '#8a6d1f'],
                ['name' => 'Teal Modern', 'color' => '#0f5c66'],
                ['name' => 'Cokelat Hangat', 'color' => '#6b4226'],
            ];
            foreach ($cardBgPresets as $preset): ?>
              <button type="button" class="preset-swatch" data-color="<?= h($preset['color']) ?>" title="<?= h($preset['name']) ?>"
                style="width:26px;height:26px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px var(--border); background:<?= h($preset['color']) ?>; cursor:pointer; padding:0;"></button>
            <?php endforeach; ?>
          </div>
          <div class="field" style="margin-top:4px;">
            <label>Intensitas Warna (Kepekatan)</label>
            <?php $curOpacity = isset($settings['theme_card_opacity']) ? (int) $settings['theme_card_opacity'] : 12; ?>
            <select name="theme_card_opacity" id="theme_card_opacity">
              <option value="0" <?= $curOpacity === 0 ? 'selected' : '' ?>>Putih Solid (tanpa warna)</option>
              <option value="6" <?= $curOpacity === 6 ? 'selected' : '' ?>>Sangat Lembut</option>
              <option value="12" <?= $curOpacity === 12 ? 'selected' : '' ?>>Lembut (disarankan)</option>
              <option value="20" <?= $curOpacity === 20 ? 'selected' : '' ?>>Sedang</option>
              <option value="30" <?= $curOpacity === 30 ? 'selected' : '' ?>>Lebih Pekat</option>
            </select>
          </div>
          <small>Warna ditampilkan sebagai gradasi lembut/transparan (bukan warna solid penuh) agar teks di kartu tetap mudah dibaca. Klik lingkaran warna di atas untuk pilihan cepat, atau pakai color-picker untuk warna bebas.</small>
        </div>

        <div class="field">
          <label>Pratinjau Kartu Pelajar</label>
          <div class="card-preview-wrap" style="justify-content:flex-start;">
            <div id="cardPreviewMount">
              <?php
              $student = $previewStudent; // dipakai oleh card_template.php
              include __DIR__ . '/includes/card_template.php';
              ?>
            </div>
          </div>
          <small>Pratinjau memakai warna yang sedang dipilih. Simpan untuk menerapkan ke seluruh kartu.</small>
        </div>
      </div>
    </div>

    <div class="btn-row">
      <button class="btn" type="submit">💾 Simpan Pengaturan</button>
    </div>
  </form>
</div>

<script>
// live-update preview warna tanpa reload
function syncColor(inputId, hexId, cssVar){
  const input = document.getElementById(inputId);
  const hexEl = document.getElementById(hexId);
  input.addEventListener('input', () => {
    hexEl.textContent = input.value;
    if (cssVar) document.documentElement.style.setProperty(cssVar, input.value);
    updateCardBgPreview();
  });
}
syncColor('theme_primary', 'theme_primary_hex', '--primary');
syncColor('theme_secondary', 'theme_secondary_hex', '--secondary');
syncColor('theme_card_bg', 'theme_card_bg_hex', null);

function hexToRgb(hex){
  hex = hex.replace('#','');
  return {
    r: parseInt(hex.substring(0,2), 16),
    g: parseInt(hex.substring(2,4), 16),
    b: parseInt(hex.substring(4,6), 16),
  };
}

function updateCardBgPreview(){
  const mode = document.querySelector('input[name=card_bg_mode]:checked').value;
  const baseHex = mode === 'custom'
    ? document.getElementById('theme_card_bg').value
    : document.getElementById('theme_primary').value;
  const secHex = document.getElementById('theme_secondary').value;
  const opacity = parseInt(document.getElementById('theme_card_opacity').value, 10) / 100;
  const opacityEnd = opacity * 0.4;

  const c = hexToRgb(baseHex);
  const s = hexToRgb(secHex);
  const bg = `linear-gradient(165deg, rgba(${c.r},${c.g},${c.b},${opacity}) 0%, rgba(${s.r},${s.g},${s.b},${opacityEnd}) 55%, #ffffff 100%)`;

  const card = document.querySelector('#cardPreviewMount .id-card');
  if (card) card.style.background = bg;
}

// preset swatch -> isi color picker + pindah ke mode "custom"
document.querySelectorAll('.preset-swatch').forEach(btn => {
  btn.addEventListener('click', () => {
    const color = btn.getAttribute('data-color');
    document.getElementById('theme_card_bg').value = color;
    document.getElementById('theme_card_bg_hex').textContent = color;
    document.getElementById('card_bg_custom').checked = true;
    updateCardBgPreview();
  });
});

document.getElementById('card_bg_auto').addEventListener('change', updateCardBgPreview);
document.getElementById('card_bg_custom').addEventListener('change', updateCardBgPreview);
document.getElementById('theme_card_opacity').addEventListener('change', updateCardBgPreview);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
