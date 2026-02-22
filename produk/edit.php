<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
// Allow both admin and supplier
requireRoleAccess(['admin', 'supplier']);

require_once '../config/database.php';

$error = '';
$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM `produk` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    redirect('index.php');
}

// Access Control for Supplier
if ($_SESSION['role'] === 'supplier') {
    $user_id = $_SESSION['user_id'];
    $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
    $stmt_sup = mysqli_prepare($connection, $sql_sup);
    mysqli_stmt_bind_param($stmt_sup, 'i', $user_id);
    mysqli_stmt_execute($stmt_sup);
    $res_sup = mysqli_stmt_get_result($stmt_sup);
    $sup = mysqli_fetch_assoc($res_sup);

    // Check if product belongs to this supplier
    if (!$sup || $data['supplier_id'] != $sup['id']) {
        echo "<script>alert('Akses Ditolak: Produk ini bukan milik anda.'); window.location='index.php';</script>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = sanitize($_POST['nama_produk'] ?? '');
    $kategori_id = $_POST['kategori_id'] ?? null;
    $harga = $_POST['harga'] ?? 0;
    $stok = $_POST['stok'] ?? 0;
    $satuan = sanitize($_POST['satuan'] ?? 'pcs');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    // Auto-set supplier_id to NULL for admin-updated products
    $supplier_id = null;

    if (empty($nama_produk)) {
        $error = 'Nama produk harus diisi!';
    } else {
        // Handle image upload
        $photo_filename = $data['photo']; // Keep existing photo

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $new_photo = handle_product_image_upload($_FILES['photo']);
            if ($new_photo === false) {
                $error = 'Gagal upload foto produk. Pastikan file berupa gambar (JPG, PNG, GIF, WEBP) dan maksimal 2MB.';
            } else {
                // Delete old photo if exists
                if ($data['photo'] && file_exists(__DIR__ . '/../uploads/produk/' . $data['photo'])) {
                    unlink(__DIR__ . '/../uploads/produk/' . $data['photo']);
                }
                $photo_filename = $new_photo;
            }
        }

        if (!$error) {
            $sql = "UPDATE `produk` SET `nama_produk` = ?, `kategori_id` = ?, `harga` = ?, `stok` = ?, `satuan` = ?, `deskripsi` = ?, `photo` = ?, `supplier_id` = ? WHERE `id` = ?";
            $stmt = mysqli_prepare($connection, $sql);

            $kategori_id = $kategori_id ?: null;

            mysqli_stmt_bind_param($stmt, 'sidisssii', $nama_produk, $kategori_id, $harga, $stok, $satuan, $deskripsi, $photo_filename, $supplier_id, $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                redirect('index.php');
            } else {
                $error = 'Gagal mengupdate produk: ' . mysqli_error($connection);
                mysqli_stmt_close($stmt);
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
    <h2>Edit Produk</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="kode_produk" class="form-label">Kode Produk</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['kode_produk']) ?>"
                            disabled>
                    </div>

                    <div class="mb-3">
                        <label for="nama_produk" class="form-label">Nama Produk <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_produk" name="nama_produk"
                            value="<?= htmlspecialchars($data['nama_produk']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <?= dropdownFromTable('kategori_produk', 'id', 'nama_kategori', $data['kategori_id'], 'kategori_id', '-- Pilih Kategori --') ?>
                    </div>

                    <div class="mb-3">
                        <label for="harga" class="form-label">Harga <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="harga" name="harga" min="0" step="0.01"
                            value="<?= htmlspecialchars($data['harga']) ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stok" name="stok" min="0"
                            value="<?= htmlspecialchars($data['stok']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="satuan" class="form-label">Satuan</label>
                        <select class="form-control" id="satuan" name="satuan">
                            <option value="pcs" <?= $data['satuan'] == 'pcs' ? 'selected' : '' ?>>Pcs (Pieces)</option>
                            <option value="kg" <?= $data['satuan'] == 'kg' ? 'selected' : '' ?>>Kg (Kilogram)</option>
                            <option value="liter" <?= $data['satuan'] == 'liter' ? 'selected' : '' ?>>Liter</option>
                            <option value="meter" <?= $data['satuan'] == 'meter' ? 'selected' : '' ?>>Meter</option>
                            <option value="box" <?= $data['satuan'] == 'box' ? 'selected' : '' ?>>Box</option>
                            <option value="lusin" <?= $data['satuan'] == 'lusin' ? 'selected' : '' ?>>Lusin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Foto Produk</label>
                        <?php if ($data['photo']): ?>
                            <div class="mb-2">
                                <img src="../uploads/produk/<?= htmlspecialchars($data['photo']) ?>" alt="Foto Produk"
                                    style="max-width: 200px; max-height: 200px;">
                                <p class="text-muted small">Foto saat ini:
                                    <?= htmlspecialchars($data['photo']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto. Format: JPG, PNG, GIF,
                            WEBP. Maksimal 2MB</small>
                    </div>
                </div>
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