<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
// Allow both admin and supplier
requireRoleAccess(['admin', 'supplier']);

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = sanitize($_POST['nama_produk'] ?? '');
    $kategori_id = $_POST['kategori_id'] ?? null;
    $harga = $_POST['harga'] ?? 0;
    $stok = $_POST['stok'] ?? 0;
    $satuan = sanitize($_POST['satuan'] ?? 'pcs');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    // Determine supplier_id
    $supplier_id = null;
    if ($_SESSION['role'] === 'supplier') {
        // Get supplier id from users table relation
        $user_id = $_SESSION['user_id'];
        $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
        $stmt_sup = mysqli_prepare($connection, $sql_sup);
        mysqli_stmt_bind_param($stmt_sup, 'i', $user_id);
        mysqli_stmt_execute($stmt_sup);
        $res_sup = mysqli_stmt_get_result($stmt_sup);
        if ($row_sup = mysqli_fetch_assoc($res_sup)) {
            $supplier_id = $row_sup['id'];
        } else {
            $error = 'Error: Data supplier tidak ditemukan untuk akun ini.';
        }
    }

    if (empty($nama_produk)) {
        $error = 'Nama produk harus diisi!';
    } elseif (!$error) { // Proceed only if no error above
        // Handle image upload
        $photo_filename = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photo_filename = handle_product_image_upload($_FILES['photo']);
            if ($photo_filename === false) {
                $error = 'Gagal upload foto produk. Pastikan file berupa gambar (JPG, PNG, GIF, WEBP) dan maksimal 2MB.';
            }
        }

        if (!$error) {
            // Auto-generate kode produk
            $kode_produk = generateCode('PRD', 'produk', 'kode_produk');

            $sql = "INSERT INTO `produk` (`kode_produk`, `nama_produk`, `kategori_id`, `harga`, `stok`, `satuan`, `deskripsi`, `photo`, `supplier_id`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $sql);

            // Handle null values
            $kategori_id = $kategori_id ?: null;

            mysqli_stmt_bind_param($stmt, 'ssidisssi', $kode_produk, $nama_produk, $kategori_id, $harga, $stok, $satuan, $deskripsi, $photo_filename, $supplier_id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                redirect('index.php');
            } else {
                $error = 'Gagal menambahkan produk: ' . mysqli_error($connection);
                mysqli_stmt_close($stmt);

                // Delete uploaded photo if database insert fails
                if ($photo_filename && file_exists(__DIR__ . '/../uploads/produk/' . $photo_filename)) {
                    unlink(__DIR__ . '/../uploads/produk/' . $photo_filename);
                }
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
    <h2>Tambah Produk</h2>
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
                        <label for="nama_produk" class="form-label">Nama Produk <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                    </div>

                    <div class="mb-3">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <?= dropdownFromTable('kategori_produk', 'id', 'nama_kategori', '', 'kategori_id', '-- Pilih Kategori --') ?>
                    </div>

                    <div class="mb-3">
                        <label for="harga" class="form-label">Harga <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="harga" name="harga" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="stok" class="form-label">Stok Awal <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stok" name="stok" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="satuan" class="form-label">Satuan</label>
                        <select class="form-control" id="satuan" name="satuan">
                            <option value="pcs">Pcs (Pieces)</option>
                            <option value="kg">Kg (Kilogram)</option>
                            <option value="liter">Liter</option>
                            <option value="meter">Meter</option>
                            <option value="box">Box</option>
                            <option value="lusin">Lusin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Foto Produk</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, GIF, WEBP. Maksimal 2MB</small>
                    </div>
                </div>
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