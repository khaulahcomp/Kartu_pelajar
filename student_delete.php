<?php
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT photo FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        delete_upload_file(UPLOAD_PHOTO_DIR, $row['photo']);
        $del = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $del->execute([$id]);
        flash_set('success', 'Data pelajar berhasil dihapus.');
    } else {
        flash_set('danger', 'Data pelajar tidak ditemukan.');
    }
}
redirect('students.php');
