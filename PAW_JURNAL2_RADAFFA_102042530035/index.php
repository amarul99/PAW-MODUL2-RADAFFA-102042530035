<?php
require 'koneksi.php';

$jml_mk = $conn->query("SELECT COUNT(*) AS total FROM mata_kuliah")->fetch_assoc()['total'];
$jml_materi = $conn->query("SELECT COUNT(*) AS total FROM materi")->fetch_assoc()['total'];
$jml_tugas = $conn->query("SELECT COUNT(*) AS total FROM tugas")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beranda LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topbar">
        <strong>LMS EAD</strong>
        <a href="index.php">Beranda</a>
        <a href="mata_kuliah.php">Mata Kuliah</a>
        <a href="materi.php">Materi</a>
        <a href="tugas.php">Tugas</a>
    </div>

    <h1>Ringkasan LMS</h1>
    <div class="grid">
        <div class="stat">
            <span>Mata Kuliah</span><br>
            <strong><?= $jml_mk ?></strong>
        </div>
        <div class="stat">
            <span>Materi</span><br>
            <strong><?= $jml_materi ?></strong>
        </div>
        <div class="stat">
            <span>Tugas</span><br>
            <strong><?= $jml_tugas ?></strong>
        </div>
    </div>
</body>
</html>