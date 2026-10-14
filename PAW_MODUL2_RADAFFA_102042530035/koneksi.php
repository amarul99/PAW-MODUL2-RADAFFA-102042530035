<?php
    $host = "localhost";
    $user = "root";
    $password = " ";
    $port = "3307";
    $database = "paw_modul2_showroom";

$conn = mysqli_connect('127.0.0.1', 'root', '', 'paw_modul2_showroom', 3307);
mysqli_set_charset($conn, 'utf8mb4');

if ($conn) {
    echo 'Koneksi berhasil.';
} else {
    echo 'Koneksi gagal.';
}
?>