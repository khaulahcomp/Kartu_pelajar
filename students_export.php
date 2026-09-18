<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/xlsx_lib.php';

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

$header = [
    'ID', 'Nama Lengkap', 'NISN', 'NIS', 'Kelas', 'Jenis Kelamin (L/P)',
    'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Alamat',
    'Golongan Darah', 'Agama', 'Nama Orang Tua/Wali', 'Nomor Kartu',
];
$rows = [$header];
foreach ($students as $s) {
    $rows[] = [
        $s['id'], $s['full_name'], $s['nisn'], $s['nis'], $s['class'], $s['gender'],
        $s['pob'], $s['dob'], $s['address'], $s['blood_type'], $s['religion'],
        $s['parent_name'], $s['card_number'],
    ];
}

$filename = 'data-pelajar-' . date('Ymd-His');

if (xlsx_supported()) {
    $file = xlsx_write($rows, 'Data Pelajar');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    @unlink($file);
    exit;
}

// Fallback CSV jika ekstensi ZipArchive/SimpleXML tidak tersedia di server
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
echo "\xEF\xBB\xBF"; // BOM agar Excel membaca UTF-8 dengan benar
$out = fopen('php://output', 'w');
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
