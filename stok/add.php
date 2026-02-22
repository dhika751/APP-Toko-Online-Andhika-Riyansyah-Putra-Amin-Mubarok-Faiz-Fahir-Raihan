<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('stok');

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk_id = $_POST['produk_id'] ?? null;
    $jenis = $_POST['jenis'] ?? 'masuk';
    $jumlah = $_POST['jumlah'] ?? 0;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d H:i');
    $keterangan = sanitize($_POST['keterangan'] ?? '');

    if (!$produk_id) {
        $error = 'Produk harus dipilih!';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        // Check stock for keluar
        if ($jenis == 'keluar') {
            if (!checkProductStock($produk_id, $jumlah)) {
                $error = 'Stok tidak mencukupi!';
            }
        }

        if (!$error) {
            // Update stock
            if (updateProductStock($produk_id, $jumlah, $jenis)) {
                redirect('index.php');
            } else {
                $error = 'Gagal mengupdate stok!';
            }
        }
    }
}
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="mb-3">
    <h2>Tambah Stok Masuk/Keluar</h2>
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
                <label for="produk_id" class="form-label">Produk <span class="text-danger">*</span></label>
                <?= dropdownFromTable('produk', 'id', 'nama_produk', '', 'produk_id', '-- Pilih Produk --') ?>
            </div>

            <div class="mb-3">
                <label for="jenis" class="form-label">Jenis <span class="text-danger">*</span></label>
                <select class="form-control" id="jenis" name="jenis" required>
                    <option value="masuk">Stok Masuk</option>
                    <option value="keluar">Stok Keluar</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" required>
            </div>

            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" id="tanggal" name="tanggal"
                    value="<?= date('Y-m-d\TH:i') ?>" required>
            </div>

            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
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