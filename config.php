<?php
/**
 * config.php
 * -----------------------------------------------------------
 * ATUR DATA KONEKSI DATABASE DI BAWAH INI SEBELUM UPLOAD/DIPAKAI.
 * Info ini bisa didapat dari menu "MySQL Databases" di cPanel.
 * -----------------------------------------------------------
 */

define('DB_HOST', 'localhost');           // biasanya 'localhost' di cPanel
define('DB_NAME', 'cpaneluser_kartu');     // nama database, contoh: cpaneluser_kartu
define('DB_USER', 'cpaneluser_dbuser');    // username database
define('DB_PASS', 'PASSWORD_DATABASE_ANDA'); // password database

// Zona waktu
date_default_timezone_set('Asia/Jakarta');

// Path dasar aplikasi (biarkan otomatis, tidak perlu diubah)
define('BASE_PATH', dirname(__FILE__));
define('BASE_URL', rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// Folder upload
define('UPLOAD_PHOTO_DIR', BASE_PATH . '/uploads/photos/');
define('UPLOAD_LOGO_DIR', BASE_PATH . '/uploads/logo/');
define('UPLOAD_PHOTO_URL', BASE_URL . '/uploads/photos/');
define('UPLOAD_LOGO_URL', BASE_URL . '/uploads/logo/');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal. Periksa kembali pengaturan di config.php. Detail: ' . htmlspecialchars($e->getMessage()));
}
