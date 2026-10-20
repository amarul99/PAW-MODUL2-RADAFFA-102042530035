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

// PROSES SIMPAN / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $mk_id = intval($_POST['mata_kuliah_id'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $instruksi = trim($_POST['instruksi'] ?? '');
    $tenggat = trim($_POST['tenggat'] ?? '');
    $status = $_POST['status'] ?? '';

    $allowed_status = ['Draf', 'Aktif', 'Selesai'];

    if ($mk_id <= 0) {
        $msg = 'Mata kuliah harus dipilih.';
    } elseif ($judul === '') {
        $msg = 'Judul tugas harus diisi.';
    } elseif ($instruksi === '') {
        $msg = 'Instruksi tugas harus diisi.';
    } elseif ($tenggat === '') {
        $msg = 'Tanggal tenggat harus diisi.';
    } elseif (!($d = DateTime::createFromFormat('Y-m-d', $tenggat)) || $d->format('Y-m-d') !== $tenggat) {
        $msg = 'Format tanggal tidak valid.';
    } elseif (!in_array($status, $allowed_status)) {
        $msg = 'Status tidak valid.';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE tugas SET mata_kuliah_id=?, judul=?, instruksi=?, tenggat=?, status=? WHERE id=?");
            $stmt->bind_param("issssi", $mk_id, $judul, $instruksi, $tenggat, $status, $id);
            $stmt->execute();
            header("Location: tugas.php?msg=Tugas+berhasil+diubah");
            exit;
        } else {
            $stmt = $conn->prepare("INSERT INTO tugas (mata_kuliah_id, judul, instruksi, tenggat, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $mk_id, $judul, $instruksi, $tenggat, $status);
            $stmt->execute();
            header("Location: tugas.php?msg=Tugas+berhasil+ditambah");
            exit;
        }
    }
}

// PROSES HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) die("Token tidak valid.");
    $stmt = $conn->prepare("DELETE FROM tugas WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: tugas.php?msg=Tugas+berhasil+dihapus");
    exit;
}

$search = trim($_GET['search'] ?? '');
$filter_mk = intval($_GET['filter_mk'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tugas LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="index.php">← Beranda</a> | <a href="tugas.php?action=add">Tambah Tugas</a>
    <h1>Pengelolaan Tugas</h1>

    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'):
        $data = ['mata_kuliah_id'=>'', 'judul'=>'', 'instruksi'=>'', 'tenggat'=>'', 'status'=>'Draf'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
            // Validasi gagal: tampilkan kembali isian terakhir user
            $data = [
                'mata_kuliah_id' => $_POST['mata_kuliah_id'] ?? '',
                'judul'          => $_POST['judul'] ?? '',
                'instruksi'      => $_POST['instruksi'] ?? '',
                'tenggat'        => $_POST['tenggat'] ?? '',
                'status'         => $_POST['status'] ?? 'Draf',
            ];
        } elseif ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM tugas WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row_data = $stmt->get_result()->fetch_assoc();
            if ($row_data) {
                $data = $row_data;
            } else {
                echo '<div class="alert">Data tugas tidak ditemukan.</div>';
            }
        }
    ?>
        <div class="panel">
            <h2>Form Tugas</h2>
            <form method="POST" novalidate>
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

                <label>Judul Tugas:</label><br>
                <input type="text" name="judul" value="<?= e($data['judul']) ?>" required><br><br>

                <label>Instruksi Tugas:</label><br>
                <textarea name="instruksi" rows="4" required><?= e($data['instruksi']) ?></textarea><br><br>

                <label>Tenggat Waktu:</label><br>
                <input type="date" name="tenggat" value="<?= e($data['tenggat']) ?>" required><br><br>

                <label>Status:</label><br>
                <select name="status">
                    <option value="Draf" <?= $data['status'] == 'Draf' ? 'selected' : '' ?>>Draf</option>
                    <option value="Aktif" <?= $data['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Selesai" <?= $data['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                </select><br><br>

                <button type="submit" name="simpan">Simpan Data</button>
                <a href="tugas.php">Batal</a>
            </form>
        </div>

    <?php elseif ($action === 'delete'): ?>
        <div class="panel">
            <h2>Konfirmasi Hapus</h2>
            <p>Apakah Anda yakin ingin menghapus tugas ini?</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <button type="submit" name="hapus" style="color:red;">Ya, Hapus Data</button>
                <a href="tugas.php">Batal</a>
            </form>
        </div>

    <?php else: ?>
        <form method="GET">
            <input type="text" name="search" placeholder="Cari Judul Tugas..." value="<?= e($search) ?>">
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
            <a href="tugas.php">Reset</a>
        </form>

        <div class="panel">
            <table>
                <thead>
                    <tr><th>Judul</th><th>Mata Kuliah</th><th>Tenggat</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT t.id, t.judul, t.tenggat, t.status, mk.nama as nama_mk FROM tugas t JOIN mata_kuliah mk ON t.mata_kuliah_id = mk.id WHERE t.judul LIKE ?";
                    if ($filter_mk > 0) $query .= " AND t.mata_kuliah_id = $filter_mk";

                    $param = "%$search%";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $param);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res->num_rows === 0): ?>
                        <tr><td colspan="5">Data tidak ditemukan.</td></tr>
                    <?php else:
                        while ($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($row['judul']) ?></td>
                                <td><?= e($row['nama_mk']) ?></td>
                                <td><?= e($row['tenggat']) ?></td>
                                <td><?= e($row['status']) ?></td>
                                <td>
                                    <a href="tugas.php?action=edit&id=<?= $row['id'] ?>">Edit</a> |
                                    <a href="tugas.php?action=delete&id=<?= $row['id'] ?>">Hapus</a>
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