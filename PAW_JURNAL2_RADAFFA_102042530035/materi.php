<?php
require 'koneksi.php';

$msg = '';
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// PROSES SIMPAN / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $mk_id = intval($_POST['mata_kuliah_id']);
    $judul = trim($_POST['judul']);
    $isi = trim($_POST['isi']);
    $tautan = trim($_POST['tautan']);

    // Validasi Isian
    if ($mk_id <= 0) {
        $msg = 'Pilih mata kuliah terlebih dahulu.';
    } elseif (strlen($judul) < 3 || strlen($judul) > 150) {
        $msg = 'Judul materi harus 3-150 karakter.';
    } elseif ($isi === '') {
        $msg = 'Isi materi tidak boleh kosong.';
    } elseif ($tautan !== '' && (!preg_match('/^https?:\/\//i', $tautan) || !filter_var($tautan, FILTER_VALIDATE_URL))) {
        $msg = 'Tautan tidak valid. Gunakan format http:// atau https://, contoh: https://www.contoh.com';
    } else {
        try {
            if ($id > 0) { // Update
                $stmt = $conn->prepare("UPDATE materi SET mata_kuliah_id=?, judul=?, isi=?, tautan=? WHERE id=?");
                $stmt->bind_param("isssi", $mk_id, $judul, $isi, $tautan, $id);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    $msg = 'Tidak ada perubahan data.';
                } else {
                    header("Location: materi.php?msg=Materi+berhasil+diubah");
                    exit;
                }
            } else { // Insert
                $stmt = $conn->prepare("INSERT INTO materi (mata_kuliah_id, judul, isi, tautan) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $mk_id, $judul, $isi, $tautan);
                $stmt->execute();

                header("Location: materi.php?msg=Materi+berhasil+ditambah");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $msg = 'Data gagal disimpan. Pastikan mata kuliah yang dipilih valid.';
        }
    }
}

// PROSES HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token tidak valid.");
    }

    try {
        $stmt = $conn->prepare("DELETE FROM materi WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: materi.php?msg=Materi+berhasil+dihapus");
        exit;
    } catch (mysqli_sql_exception $e) {
        $msg = 'Materi gagal dihapus. Silakan coba lagi.';
    }
}

$search = trim($_GET['search'] ?? '');
$filter_mk = intval($_GET['filter_mk'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Materi LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="index.php">← Beranda</a> | <a href="materi.php?action=add">Tambah Materi</a>
    <h1>Pengelolaan Materi</h1>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert success"><?= e($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
        <div class="alert"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'):
        $data = ['mata_kuliah_id' => '', 'judul' => '', 'isi' => '', 'tautan' => ''];

        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM materi WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc() ?: $data;
        }

        // Pertahankan isian pengguna jika penyimpanan gagal
        if ($msg && isset($_POST['simpan'])) {
            $data = ['mata_kuliah_id' => $mk_id, 'judul' => $judul, 'isi' => $isi, 'tautan' => $tautan];
        }
    ?>
        <div class="panel">
            <h2>Form Materi</h2>
            <form method="POST">
                <label>Mata Kuliah:</label><br>
                <select name="mata_kuliah_id" required>
                    <option value="">Pilih Mata Kuliah</option>
                    <?php
                    $mks = $conn->query("SELECT id, kode, nama FROM mata_kuliah ORDER BY nama");
                    while ($mk = $mks->fetch_assoc()): ?>
                        <option value="<?= $mk['id'] ?>" <?= $data['mata_kuliah_id'] == $mk['id'] ? 'selected' : '' ?>>
                            <?= e($mk['kode']) ?> - <?= e($mk['nama']) ?>
                        </option>
                    <?php endwhile; ?>
                </select><br><br>
                <label>Judul Materi:</label><br>
                <input type="text" name="judul" value="<?= e($data['judul']) ?>" required><br><br>
                <label>Isi Materi:</label><br>
                <textarea name="isi" rows="5" required><?= e($data['isi']) ?></textarea><br><br>
                <label>Tautan Referensi (http/https):</label><br>
                <input type="text" name="tautan" value="<?= e($data['tautan']) ?>" placeholder="https://www.contoh.com"><br><br>
                <button type="submit" name="simpan">Simpan Data</button>
                <a href="materi.php">Batal</a>
            </form>
        </div>

    <?php elseif ($action === 'detail'):
        $stmt = $conn->prepare("SELECT m.*, mk.nama AS nama_mk, mk.kode FROM materi m JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id WHERE m.id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $detail = $stmt->get_result()->fetch_assoc();
    ?>
        <div class="panel">
            <?php if (!$detail): ?>
                <p>Data materi tidak ditemukan.</p>
                <a href="materi.php">Kembali</a>
            <?php else: ?>
                <h2>Detail Materi: <?= e($detail['judul']) ?></h2>
                <p><strong>Mata Kuliah:</strong> <?= e($detail['kode']) ?> - <?= e($detail['nama_mk']) ?></p>
                <p><strong>Isi:</strong><br><?= nl2br(e($detail['isi'])) ?></p>
                <p><strong>Tautan:</strong>
                    <?php if ($detail['tautan']): ?>
                        <a href="<?= e($detail['tautan']) ?>" target="_blank"><?= e($detail['tautan']) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </p>
                <a href="materi.php?action=edit&id=<?= $detail['id'] ?>">Edit Data</a> | <a href="materi.php">Kembali</a>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'delete'): ?>
        <div class="panel">
            <h2>Konfirmasi Hapus</h2>
            <p>Apakah Anda yakin ingin menghapus materi ini?</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="hapus" style="color:red;">Ya, Hapus Data</button>
                <a href="materi.php">Batal</a>
            </form>
        </div>

    <?php else: ?>
        <form method="GET">
            <input type="text" name="search" placeholder="Cari Judul Materi..." value="<?= e($search) ?>">
            <select name="filter_mk">
                <option value="0">Semua Mata Kuliah</option>
                <?php
                $mks = $conn->query("SELECT id, kode, nama FROM mata_kuliah ORDER BY nama");
                while ($mk = $mks->fetch_assoc()): ?>
                    <option value="<?= $mk['id'] ?>" <?= $filter_mk == $mk['id'] ? 'selected' : '' ?>>
                        <?= e($mk['kode']) ?> - <?= e($mk['nama']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Cari</button>
            <a href="materi.php">Reset</a>
        </form>

        <div class="panel">
            <table>
                <thead>
                    <tr><th>Judul</th><th>Mata Kuliah</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT m.id, m.judul, mk.nama AS nama_mk
                              FROM materi m
                              JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id
                              WHERE m.judul LIKE ?";
                    $param = "%$search%";

                    if ($filter_mk > 0) {
                        $query .= " AND m.mata_kuliah_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("si", $param, $filter_mk);
                    } else {
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $param);
                    }

                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res->num_rows === 0): ?>
                        <tr><td colspan="3">Data tidak ditemukan.</td></tr>
                    <?php else:
                        while ($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['judul']) ?></td>
                                <td><?= e($row['nama_mk']) ?></td>
                                <td>
                                    <a href="materi.php?action=detail&id=<?= $row['id'] ?>">Detail</a> |
                                    <a href="materi.php?action=edit&id=<?= $row['id'] ?>">Edit</a> |
                                    <a href="materi.php?action=delete&id=<?= $row['id'] ?>">Hapus</a>
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