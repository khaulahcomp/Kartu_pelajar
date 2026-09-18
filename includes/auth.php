<?php
/**
 * auth.php - session start + proteksi halaman admin
 * Cukup include file ini di paling atas halaman yang butuh login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['admin_id'])) {
    redirect('login.php');
}
