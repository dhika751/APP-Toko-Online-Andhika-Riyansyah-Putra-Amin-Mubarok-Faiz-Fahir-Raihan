<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembayaran');

require_once '../config/database.php';

$error = '';

// Get unpaid orders
$sql_orders = "SELECT p.id, p.kode_pesanan, p.total_harga, pl.nama_pelanggan 
               FROM `pesanan` p
               LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
               WHERE p.status IN ('pending', 'diproses')
               ORDER BY p.id DESC";
$result_orders = mysqli_query($connection, $sql_orders);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesanan_id = $_POST['pesanan_id'] ?? null;
    $jumlah_bayar = $_POST['jumlah_bayar'] ?? 0;
    $metode_bayar = $_POST['metode_bayar'] ?? '';

    if (!$pesanan_id) {
        $error = 'Pesanan harus dipilih!';
    } elseif ($jumlah_bayar <= 0) {
        $error = 'Jumlah bayar harus lebih dari 0!';
    } else {
        // Handle file upload
        $bukti_bayar = '';
        if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] == UPLOAD_ERR_OK) {
            $bukti_bayar = handle_payment_proof_upload($_FILES['bukti_bayar']);
            if ($bukti_bayar === false) {
                $error = 'Gagal upload bukti bayar.';
            }
        }

        if (!$error) {
            $kode_pembayaran = generateCode('BYR', 'pembayaran', 'kode_pembayaran');

            $sql = "INSERT INTO `pembayaran` (`kode_pembayaran`, `pesanan_id`, `tanggal_bayar`, `jumlah_bayar`, `metode_bayar`, `bukti_bayar`, `status`) 
                    VALUES (?, ?, NOW(), ?, ?, ?, 'pending')";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, 'sidss', $kode_pembayaran, $pesanan_id, $jumlah_bayar, $metode_bayar, $bukti_bayar);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // Redirect back to index
                redirect('index.php');
            } else {
                $error = 'Gagal menyimpan pembayaran: ' . mysqli_error($connection);
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
    <h2>Tambah Pembayaran</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="pesanan_id" class="form-label">Pesanan <span class="text-danger">*</span></label>
                <select class="form-control" id="pesanan_id" name="pesanan_id" required onchange="updateTotal(this)">
                    <option value="">-- Pilih Pesanan --</option>
                    <?php while ($order = mysqli_fetch_assoc($result_orders)): ?>
                        <option value="<?= $order['id'] ?>" data-total="<?= $order['total_harga'] ?>">
                            <?= $order['kode_pesanan'] ?> -
                            <?= $order['nama_pelanggan'] ?> (Total:
                            <?= formatRupiah($order['total_harga']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="jumlah_bayar" class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="jumlah_bayar" name="jumlah_bayar" min="1" required>
            </div>

            <div class="mb-3">
                <label for="metode_bayar" class="form-label">Metode Pembayaran</label>
                <select class="form-control" id="metode_bayar" name="metode_bayar">
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="Tunai">Tunai</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="bukti_bayar" class="form-label">Bukti Pembayaran</label>
                <input type="file" class="form-control" id="bukti_bayar" name="bukti_bayar"
                    accept="image/*,application/pdf">
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    function updateTotal(select) {
        var option = select.options[select.selectedIndex];
        var total = option.getAttribute('data-total');
        if (total) {
            document.getElementById('jumlah_bayar').value = total;
        }
    }
</script>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>