<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('kategori_produk');

require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    if (empty($nama_kategori)) {
        $error = 'Nama kategori harus diisi!';
    } else {
        // Auto-generate kode kategori
        $kode_kategori = generateCode('KAT', 'kategori_produk', 'kode_kategori');

        $sql = "INSERT INTO `kategori_produk` (`kode_kategori`, `nama_kategori`, `deskripsi`) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $kode_kategori, $nama_kategori, $deskripsi);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirect('index.php');
        } else {
            $error = 'Gagal menambahkan kategori: ' . mysqli_error($connection);
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="mb-3">
    <h2>Tambah Kategori Produk</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>