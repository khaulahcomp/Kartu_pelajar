<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/pdf_card_writer.php';

$idsParam = $_GET['ids'] ?? '';
$all = isset($_GET['all']) && $_GET['all'] == '1';
$q = trim($_GET['q'] ?? '');

if ($all) {
    $params = [];
    $where = '';
    if ($q !== '') {
        $where = 'WHERE full_name LIKE ? OR nisn LIKE ? OR nis LIKE ? OR class LIKE ?';
        $like = '%' . $q . '%';
        $params = [$like, $like, $like, $like];
    }
    $stmt = $pdo->prepare("SELECT * FROM students $where ORDER BY full_name ASC");
    $stmt->execute($params);
    $studentsList = $stmt->fetchAll();
} else {
    $ids = array_filter(array_map('intval', explode(',', $idsParam)));
    if (!$ids) {
        flash_set('danger', 'Tidak ada pelajar yang dipilih untuk dicetak PDF.');
        redirect('students.php');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id IN ($placeholders) ORDER BY full_name ASC");
    $stmt->execute($ids);
    $studentsList = $stmt->fetchAll();
}

if (!$studentsList) {
    flash_set('danger', 'Data pelajar tidak ditemukan.');
    redirect('students.php');
}

$settings = get_settings($pdo);

try {
    $pdfFile = generate_cards_pdf($studentsList, $settings);
} catch (Exception $e) {
    flash_set('danger', 'Gagal membuat PDF: ' . $e->getMessage());
    redirect('students.php');
}

$filename = 'kartu-pelajar-' . date('Ymd-His') . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($pdfFile));
readfile($pdfFile);
@unlink($pdfFile);
exit;
