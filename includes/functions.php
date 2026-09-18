<?php
/**
 * functions.php - fungsi bantu yang dipakai di seluruh aplikasi
 */

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function get_settings(PDO $pdo)
{
    static $settings = null;
    if ($settings === null) {
        $stmt = $pdo->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
        $settings = $stmt->fetch();
        if (!$settings) {
            // fallback jika tabel settings kosong
            $settings = [
                'school_name' => 'Nama Sekolah',
                'address' => '',
                'logo' => null,
                'theme_primary' => '#1a3c6e',
                'theme_secondary' => '#f4b400',
                'theme_text_on_primary' => '#ffffff',
                'academic_year' => '',
                'card_validity' => '',
            ];
        }
    }
    return $settings;
}

function format_tanggal($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $ts = strtotime($date);
    if (!$ts) {
        return h($date);
    }
    return (int) date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function ttl($pob, $dob)
{
    $pob = trim((string) $pob);
    $dobText = format_tanggal($dob);
    if ($pob === '' && $dobText === '-') {
        return '-';
    }
    if ($pob === '') {
        return $dobText;
    }
    return $pob . ', ' . $dobText;
}

/**
 * Meng-upload file gambar dengan validasi dasar.
 * Mengembalikan nama file baru jika sukses, atau null jika tidak ada file diupload.
 * Melempar Exception jika file tidak valid.
 */
function upload_image($fieldName, $targetDir, $prefix = 'img')
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Terjadi kesalahan saat upload file.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        throw new Exception('Format file harus JPG, PNG, atau WEBP.');
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new Exception('Ukuran file maksimal 3MB.');
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $newName = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $destination = rtrim($targetDir, '/\\') . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Gagal menyimpan file yang diupload.');
    }

    return $newName;
}

function delete_upload_file($dir, $filename)
{
    if (!empty($filename)) {
        $path = rtrim($dir, '/\\') . '/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Menghitung warna teks (hitam/putih) yang kontras terhadap warna latar hex.
 */
function hex_to_rgb($hex)
{
    $hex = ltrim((string) $hex, '#');
    if (strlen($hex) !== 6) {
        return [26, 60, 110]; // fallback biru default
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function contrast_color($hex)
{
    $hex = ltrim((string) $hex, '#');
    if (strlen($hex) !== 6) {
        return '#ffffff';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.6 ? '#1a1a1a' : '#ffffff';
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get()
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
