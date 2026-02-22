<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pesanan');

require_once '../config/database.php';

$error = '';
$id = $_GET['id'] ?? 0;

// Get pesanan master data
$sql_master = "SELECT * FROM `pesanan` WHERE `id` = ?";
$stmt_master = mysqli_prepare($connection, $sql_master);
mysqli_stmt_bind_param($stmt_master, 'i', $id);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$pesanan = mysqli_fetch_assoc($result_master);
mysqli_stmt_close($stmt_master);

if (!$pesanan) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $pesanan['status'];
    $metode_pembayaran = sanitize($_POST['metode_pembayaran'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');

    $old_status = $pesanan['status'];

    $sql_update = "UPDATE `pesanan` SET `status` = ?, `metode_pembayaran` = ?, `catatan` = ? WHERE `id` = ?";
    $stmt_update = mysqli_prepare($connection, $sql_update);
    mysqli_stmt_bind_param($stmt_update, 'sssi', $status, $metode_pembayaran, $catatan, $id);

    if (mysqli_stmt_execute($stmt_update)) {
        mysqli_stmt_close($stmt_update);

        // AUTO-UPDATE SUPPLIER TRANSACTION STATUS when order becomes 'selesai'
        if ($status === 'selesai' && $old_status !== 'selesai') {
            $sql_update_supplier = "UPDATE `transaksi_supplier` 
                                   SET `status` = 'paid_to_supplier' 
                                   WHERE `pesanan_id` = ? AND `status` = 'pending'";
            $stmt_supplier = mysqli_prepare($connection, $sql_update_supplier);
            mysqli_stmt_bind_param($stmt_supplier, 'i', $id);
            mysqli_stmt_execute($stmt_supplier);
            mysqli_stmt_close($stmt_supplier);
        }

        redirect('index.php');
    } else {
        $error = 'Gagal mengupdate pesanan: ' . mysqli_error($connection);
        mysqli_stmt_close($stmt_update);
    }
}

// Get detail pesanan
$sql_detail = "SELECT dp.*, pr.nama_produk, pr.harga, pr.satuan 
               FROM `detail_pesanan` dp
               LEFT JOIN `produk` pr ON dp.produk_id = pr.id
               WHERE dp.pesanan_id = ?";
$stmt_detail = mysqli_prepare($connection, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, 'i', $id);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="mb-3">
    <h2>Edit Status Pesanan</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <strong>Detail Produk (Read-only)</strong>
    </div>
    <div class="card-body">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($detail = mysqli_fetch_assoc($result_detail)): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($detail['nama_produk'] ?? 'Produk Dihapus') ?>
                        </td>
                        <td>
                            <?= formatRupiah($detail['harga_satuan']) ?>
                        </td>
                        <td>
                            <?= $detail['jumlah'] ?>
                            <?= htmlspecialchars($detail['satuan'] ?? '') ?>
                        </td>
                        <td>
                            <?= formatRupiah($detail['subtotal']) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">TOTAL:</th>
                    <th>
                        <?= formatRupiah($pesanan['total_harga']) ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Kode Pesanan</label>
                        <input type="text" class="form-control"
                            value="<?= htmlspecialchars($pesanan['kode_pesanan']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Pesanan</label>
                        <input type="text" class="form-control"
                            value="<?= date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending" <?= $pesanan['status'] == 'pending' ? 'selected' : '' ?>>Pending
                            </option>
                            <option value="diproses" <?= $pesanan['status'] == 'diproses' ? 'selected' : '' ?>>Diproses
                            </option>
                            <option value="dikirim" <?= $pesanan['status'] == 'dikirim' ? 'selected' : '' ?>>Dikirim
                            </option>
                            <option value="selesai" <?= $pesanan['status'] == 'selesai' ? 'selected' : '' ?>>Selesai
                            </option>
                            <option value="dibatalkan" <?= $pesanan['status'] == 'dibatalkan' ? 'selected' : '' ?>>
                                Dibatalkan</option>
                        </select>

                        <?php if (in_array($pesanan['status'], ['diproses', 'dikirim', 'selesai'])): ?>
                            <div class="form-text text-danger mt-2" id="refund-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <strong>Perhatian:</strong> Pesanan ini statusnya <u><?= ucfirst($pesanan['status']) ?></u>
                                (Sudah Bayar).
                                Jika dibatalkan, pastikan uang dikembalikan ke pelanggan.
                            </div>
                        <?php endif; ?>

                        <script>
                            document.querySelector('form').addEventListener('submit', function (e) {
                                var statusSelect = document.getElementById('status');
                                var originalStatus = '<?= $pesanan['status'] ?>';
                                var paidStatuses = ['diproses', 'dikirim', 'selesai'];

                                if (statusSelect.value === 'dibatalkan' && paidStatuses.includes(originalStatus)) {
                                    var confirmCancel = confirm("⚠️ PERINGATAN REFUND ⚠️\n\nPesanan ini memiliki status '" + originalStatus + "' yang artinya sudah dibayar.\n\nJika Anda membatalkan pesanan ini, Anda WAJIB mengembalikan dana (refund) kepada pelanggan.\n\nApakah Anda yakin ingin melanjutkan pembatalan?");
                                    if (!confirmCancel) {
                                        e.preventDefault();
                                    }
                                }
                            });
                        </script>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                        <select class="form-control" id="metode_pembayaran" name="metode_pembayaran">
                            <option value="Transfer Bank" <?= $pesanan['metode_pembayaran'] == 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank</option>
                            <option value="COD" <?= $pesanan['metode_pembayaran'] == 'COD' ? 'selected' : '' ?>>COD (Cash
                                on Delivery)</option>
                            <option value="E-Wallet" <?= $pesanan['metode_pembayaran'] == 'E-Wallet' ? 'selected' : '' ?>>
                                E-Wallet</option>
                            <option value="Tunai" <?= $pesanan['metode_pembayaran'] == 'Tunai' ? 'selected' : '' ?>>Tunai
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="catatan" class="form-label">Catatan</label>
                        <textarea class="form-control" id="catatan" name="catatan"
                            rows="3"><?= htmlspecialchars($pesanan['catatan']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning">
                <strong>Catatan:</strong> Edit pesanan hanya mengubah status, metode pembayaran, dan catatan. Untuk
                mengubah produk, silakan hapus dan buat pesanan baru.
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