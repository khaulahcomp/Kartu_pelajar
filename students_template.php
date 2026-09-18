<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/xlsx_lib.php';

$header = [
    'ID (kosongkan jika data baru)', 'Nama Lengkap', 'NISN', 'NIS', 'Kelas', 'Jenis Kelamin (L/P)',
    'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Alamat',
    'Golongan Darah', 'Agama', 'Nama Orang Tua/Wali', 'Nomor Kartu',
];
$example = [
    '', 'Contoh: Budi Santoso', '0012345678', '', 'IX-A', 'L',
    'Bandung', '2010-05-17', 'Jl. Contoh No. 10, Kota Contoh',
    'O', 'Islam', 'Ahmad Santoso', '',
];
$rows = [$header, $example];

$filename = 'template-import-data-pelajar';

if (xlsx_supported()) {
    $file = xlsx_write($rows, 'Template');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    @unlink($file);
    exit;
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
