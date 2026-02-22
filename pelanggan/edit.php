<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pelanggan');

require_once '../config/database.php';

$error = '';
$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM `pelanggan` WHERE `id` = ?";
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
    $nama_pelanggan = sanitize($_POST['nama_pelanggan'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($nama_pelanggan)) {
        $error = 'Nama pelanggan harus diisi!';
    } else {
        $sql = "UPDATE `pelanggan` SET `nama_pelanggan` = ?, `alamat` = ?, `telepon` = ?, `email` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssi', $nama_pelanggan, $alamat, $telepon, $email, $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirect('index.php');
        } else {
            $error = 'Gagal mengupdate pelanggan: ' . mysqli_error($connection);
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
    <h2>Edit Pelanggan</h2>
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
                <label for="kode_pelanggan" class="form-label">Kode Pelanggan</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['kode_pelanggan']) ?>"
                    disabled>
            </div>

            <div class="mb-3">
                <label for="nama_pelanggan" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan"
                    value="<?= htmlspecialchars($data['nama_pelanggan']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat"
                    rows="3"><?= htmlspecialchars($data['alamat']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="telepon" class="form-label">Telepon</label>
                <input type="text" class="form-control" id="telepon" name="telepon"
                    value="<?= htmlspecialchars($data['telepon']) ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= htmlspecialchars($data['email']) ?>">
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