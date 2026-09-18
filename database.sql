-- ========================================================
-- Database: Aplikasi Kartu Pelajar
-- Import file ini lewat phpMyAdmin di cPanel sebelum
-- menjalankan install.php
-- ========================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- --------------------------------------------------------
-- Tabel: admin_users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabel: settings  (identitas sekolah, logo, tema warna)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `school_name` VARCHAR(255) DEFAULT 'Nama Sekolah',
  `npsn` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(150) DEFAULT NULL,
  `headmaster_name` VARCHAR(150) DEFAULT NULL,
  `headmaster_nip` VARCHAR(50) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `card_validity` VARCHAR(100) DEFAULT NULL,
  `theme_primary` VARCHAR(20) NOT NULL DEFAULT '#1a3c6e',
  `theme_secondary` VARCHAR(20) NOT NULL DEFAULT '#f4b400',
  `theme_card_bg` VARCHAR(20) DEFAULT NULL COMMENT 'warna dasar area tengah kartu; NULL = ikut theme_primary otomatis',
  `theme_card_opacity` TINYINT UNSIGNED NOT NULL DEFAULT 12 COMMENT 'kepekatan warna area tengah kartu (persen, 0-40)',
  `theme_text_on_primary` VARCHAR(20) NOT NULL DEFAULT '#ffffff',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings`
  (`school_name`, `address`, `academic_year`, `card_validity`, `theme_primary`, `theme_secondary`, `theme_text_on_primary`)
VALUES
  ('Nama Sekolah Anda', 'Alamat sekolah belum diatur', '2026/2027', 'Berlaku selama menjadi peserta didik aktif', '#1a3c6e', '#f4b400', '#ffffff');

-- --------------------------------------------------------
-- Tabel: students  (data pelajar)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `photo` VARCHAR(255) DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `nisn` VARCHAR(30) DEFAULT NULL,
  `nis` VARCHAR(30) DEFAULT NULL,
  `class` VARCHAR(50) DEFAULT NULL,
  `gender` ENUM('L','P') DEFAULT NULL,
  `pob` VARCHAR(100) DEFAULT NULL COMMENT 'tempat lahir',
  `dob` DATE DEFAULT NULL COMMENT 'tanggal lahir',
  `address` TEXT DEFAULT NULL,
  `blood_type` VARCHAR(5) DEFAULT NULL,
  `religion` VARCHAR(30) DEFAULT NULL,
  `parent_name` VARCHAR(255) DEFAULT NULL,
  `card_number` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `full_name` (`full_name`),
  KEY `nisn` (`nisn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
