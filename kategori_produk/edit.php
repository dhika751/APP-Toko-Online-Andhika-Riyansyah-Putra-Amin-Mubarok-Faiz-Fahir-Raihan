<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('kategori_produk');

require_once '../config/database.php';

$error = '';
$id = $_GET['id'] ?? 0;

// Fetch existing data
$sql = "SELECT * FROM `kategori_produk` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    if (empty($nama_kategori)) {
        $error = 'Nama kategori harus diisi!';
    } else {
        $sql = "UPDATE `kategori_produk` SET `nama_kategori` = ?, `deskripsi` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $nama_kategori, $deskripsi, $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirect('index.php');
        } else {
            $error = 'Gagal mengupdate kategori: ' . mysqli_error($connection);
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
    <h2>Edit Kategori Produk</h2>
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
                <label for="kode_kategori" class="form-label">Kode Kategori</label>
                <input type="text" class="form-control" id="kode_kategori"
                    value="<?= htmlspecialchars($data['kode_kategori']) ?>" disabled>
            </div>

            <div class="mb-3">
                <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                    value="<?= htmlspecialchars($data['nama_kategori']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi"
                    rows="3"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>