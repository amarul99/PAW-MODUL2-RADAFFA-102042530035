CREATE DATABASE IF NOT EXISTS db_lms_paw;
USE db_lms_paw;

-- 1. Tabel Mata Kuliah
CREATE TABLE IF NOT EXISTS mata_kuliah (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    dosen VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

-- 2. Tabel Materi (Relasi FK ON DELETE RESTRICT)
CREATE TABLE IF NOT EXISTS materi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    tautan VARCHAR(255) NULL,
    FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. Tabel Tugas (Relasi FK ON DELETE RESTRICT)
CREATE TABLE IF NOT EXISTS tugas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    judul VARCHAR(150) NOT NULL,
    instruksi TEXT NOT NULL,
    tenggat DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    FOREIGN KEY (mata_kuliah_id) REFERENCES mata_kuliah(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Data Awal
INSERT INTO mata_kuliah (kode, nama, dosen, semester) VALUES 
('PAW-101', 'Pengembangan Aplikasi Website', 'Dosen PAW', 3),
('ALP-101', 'Algoritma dan Pemrograman', 'Dosen ALP', 1);

INSERT INTO materi (mata_kuliah_id, judul, isi, tautan) VALUES 
(1, 'Dasar PHP', 'Penjelasan variabel, fungsi, dan sintaks PHP dasar.', 'https://www.php.net/manual/en/');

INSERT INTO tugas (mata_kuliah_id, judul, instruksi, tenggat, status) VALUES 
(1, 'Latihan CRUD', 'Buatlah fungsi CRUD sederhana menggunakan PHP dan MySQL.', '2026-11-01', 'Aktif');