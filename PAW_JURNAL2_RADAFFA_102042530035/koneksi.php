<?php
session_start();

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "db_lms_paw";
$port   = 3307;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Generate CSRF Token untuk keamanan form hapus
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fungsi pembantu keamanan
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>