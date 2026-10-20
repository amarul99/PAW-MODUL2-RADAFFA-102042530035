<?php
require 'koneksi.php';

// Pastikan session aktif & token CSRF tersedia
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// CEK TOKEN CSRF UNTUK SEMUA REQUEST POST
$token_valid = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '');
    if (!$token_valid) {
        http_response_code(403);
        $msg = 'Token keamanan tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman lalu coba lagi.';
    }
}

// PROSES TAMBAH / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid && isset($_POST['simpan'])) {
    $kode = trim($_POST['kode'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $dosen = trim($_POST['dosen'] ?? '');
    $semester_raw = trim($_POST['semester'] ?? '');
    $semester = intval($semester_raw);

    // Validasi Isian
    if ($kode === '') {
        $msg = 'Kode mata kuliah harus diisi.';
    } elseif (!preg_match('/^[A-Z0-9\-]{3,20}$/', $kode)) {
        $msg = 'Kode harus 3-20 karakter (Huruf Kapital, Angka, atau Hyphen).';
    } elseif ($nama === '') {
        $msg = 'Nama mata kuliah harus diisi.';
    } elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
        $msg = 'Nama mata kuliah harus 3-100 karakter.';
    } elseif ($dosen === '') {
        $msg = 'Dosen pengampu harus diisi.';
    } elseif ($semester_raw === '') {
        $msg = 'Semester harus diisi.';
    } elseif (!ctype_digit($semester_raw)) {
        $msg = 'Semester harus berupa angka.';
    } elseif ($semester < 1 || $semester > 14) {
        $msg = 'Semester tidak valid. Semester harus antara 1 sampai 14.';
    } else {
        try {
            if ($id > 0) { // Update
                $stmt = $conn->prepare("UPDATE mata_kuliah SET kode=?, nama=?, dosen=?, semester=? WHERE id=?");
                $stmt->bind_param("sssii", $kode, $nama, $dosen, $semester, $id);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    $msg = "Tidak ada perubahan data.";
                } else {
                    header("Location: mata_kuliah.php?msg=Data+berhasil+diubah");
                    exit;
                }
            } else { // Insert
                $stmt = $conn->prepare("INSERT INTO mata_kuliah (kode, nama, dosen, semester) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $kode, $nama, $dosen, $semester);
                $stmt->execute();

                header("Location: mata_kuliah.php?msg=Data+berhasil+ditambah");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $msg = "Kode mata kuliah sudah digunakan!";
            } else {
                $msg = "Data gagal disimpan. Silakan coba lagi.";
            }
        }
    }
}

// PROSES HAPUS (Gunakan POST + CSRF Token)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid && isset($_POST['hapus'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM mata_kuliah WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: mata_kuliah.php?msg=Mata+kuliah+berhasil+dihapus");
        exit;
    } catch (mysqli_sql_exception $e) {
        $msg = "Data belum terhapus. Mata kuliah masih memiliki materi atau tugas. Hapus data terkait terlebih dahulu.";
    }
}

// PENCARIAN
$search = trim($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Mata Kuliah</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="index.php">← Beranda</a> | <a href="mata_kuliah.php?action=add">Tambah Mata Kuliah</a>
    <h1>Pengelolaan Mata Kuliah</h1>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert success"><?= e($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
        <div class="alert"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'):
        $data = ['kode' => '', 'nama' => '', 'dosen' => '', 'semester' => ''];

        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc() ?: $data;
        }

        // Pertahankan isian pengguna jika penyimpanan gagal
        if ($msg && isset($_POST['simpan'])) {
            $data = [
                'kode'     => $_POST['kode'] ?? '',
                'nama'     => $_POST['nama'] ?? '',
                'dosen'    => $_POST['dosen'] ?? '',
                'semester' => $_POST['semester'] ?? '',
            ];
        }
    ?>
        <div class="panel">
            <h2><?= $id ? 'Edit' : 'Tambah' ?> Mata Kuliah</h2>
            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <label>Kode MK:</label><br>
                <input type="text" name="kode" value="<?= e($data['kode']) ?>" required><br><br>
                <label>Nama MK:</label><br>
                <input type="text" name="nama" value="<?= e($data['nama']) ?>" required><br><br>
                <label>Dosen Pengampu:</label><br>
                <input type="text" name="dosen" value="<?= e($data['dosen']) ?>" required><br><br>
                <label>Semester (1-14):</label><br>
                <input type="number" name="semester" value="<?= e($data['semester']) ?>" min="1" max="14" required><br><br>
                <button type="submit" name="simpan">Simpan Data</button>
                <a href="mata_kuliah.php">Batal</a>
            </form>
        </div>

    <?php elseif ($action === 'delete'): ?>
        <div class="panel">
            <h2>Konfirmasi Hapus</h2>
            <p>Apakah Anda yakin ingin menghapus mata kuliah ini?</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <button type="submit" name="hapus" style="color:red;">Ya, Hapus Data</button>
                <a href="mata_kuliah.php">Batal</a>
            </form>
        </div>

    <?php else: ?>
        <form method="GET">
            <input type="text" name="search" placeholder="Cari Kode / Nama MK..." value="<?= e($search) ?>">
            <button type="submit">Cari</button>
            <a href="mata_kuliah.php">Reset</a>
        </form>

        <div class="panel">
            <table>
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>Dosen / Semester</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM mata_kuliah WHERE kode LIKE ? OR nama LIKE ? ORDER BY nama";
                    $param = "%$search%";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $param, $param);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res->num_rows === 0): ?>
                        <tr><td colspan="4">Data tidak ditemukan.</td></tr>
                    <?php else:
                        while ($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['kode']) ?></td>
                                <td><?= e($row['nama']) ?></td>
                                <td><?= e($row['dosen']) ?> / Sem <?= e($row['semester']) ?></td>
                                <td>
                                    <a href="mata_kuliah.php?action=edit&id=<?= $row['id'] ?>">Edit</a> |
                                    <a href="mata_kuliah.php?action=delete&id=<?= $row['id'] ?>">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile;
                    endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>