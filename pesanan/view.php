<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';
require_once '../config/database.php';

requireAuth();

// Initialize variables
$id = $_GET['id'] ?? 0;
$error_message = '';

// Get pesanan master data
$sql_master = "SELECT p.*, pl.nama_pelanggan, pl.alamat, pl.telepon 
               FROM `pesanan` p 
               LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
               WHERE p.id = ?";
$stmt_master = mysqli_prepare($connection, $sql_master);
mysqli_stmt_bind_param($stmt_master, 'i', $id);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$pesanan = mysqli_fetch_assoc($result_master);
mysqli_stmt_close($stmt_master);

if (!$pesanan) {
    redirect('../index.php');
}

// ACCESS CONTROL CHECK
$userRole = $_SESSION['role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;
$supplier_id = 0;

if ($userRole === 'admin') {
    // Admin allowed
} elseif ($userRole === 'supplier') {
    // Get supplier ID
    $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
    $stmt_sup = mysqli_prepare($connection, $sql_sup);
    mysqli_stmt_bind_param($stmt_sup, 'i', $userId);
    mysqli_stmt_execute($stmt_sup);
    $res_sup = mysqli_stmt_get_result($stmt_sup);
    if ($row_sup = mysqli_fetch_assoc($res_sup)) {
        $supplier_id = $row_sup['id'];
    }
    mysqli_stmt_close($stmt_sup);

    // Check if this order contains supplier's products
    $sql_check = "SELECT COUNT(*) as count FROM detail_pesanan dp 
                  JOIN produk pr ON dp.produk_id = pr.id 
                  WHERE dp.pesanan_id = ? AND pr.supplier_id = ?";
    $stmt_check = mysqli_prepare($connection, $sql_check);
    mysqli_stmt_bind_param($stmt_check, 'ii', $id, $supplier_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $check_row = mysqli_fetch_assoc($result_check);

    if ($check_row['count'] == 0) {
        // Order doesn't contain supplier's products
        showAccessDenied(['admin', 'own_order', 'supplier']);
    }
    mysqli_stmt_close($stmt_check);
} else {
    // Check if this order belongs to the logged-in user (via pelanggan table)
    $sql_check = "SELECT id FROM pelanggan WHERE users_id = ? AND id = ?";
    $stmt_check = mysqli_prepare($connection, $sql_check);
    mysqli_stmt_bind_param($stmt_check, 'ii', $userId, $pesanan['pelanggan_id']);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) === 0) {
        // Not their order
        showAccessDenied(['admin', 'own_order']);
    }
    mysqli_stmt_close($stmt_check);
}

// Get pesanan detail - filter by supplier if supplier is viewing
if ($userRole === 'supplier' && $supplier_id) {
    // Supplier: only show their products
    $sql_detail = "SELECT dp.*, pr.nama_produk, pr.satuan 
                   FROM `detail_pesanan` dp
                   LEFT JOIN `produk` pr ON dp.produk_id = pr.id
                   WHERE dp.pesanan_id = ? AND pr.supplier_id = ?";
    $stmt_detail = mysqli_prepare($connection, $sql_detail);
    mysqli_stmt_bind_param($stmt_detail, 'ii', $id, $supplier_id);
} else {
    // Admin or Customer: show all products
    $sql_detail = "SELECT dp.*, pr.nama_produk, pr.satuan 
                   FROM `detail_pesanan` dp
                   LEFT JOIN `produk` pr ON dp.produk_id = pr.id
                   WHERE dp.pesanan_id = ?";
    $stmt_detail = mysqli_prepare($connection, $sql_detail);
    mysqli_stmt_bind_param($stmt_detail, 'i', $id);
}
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

// Get payment data if any
$sql_pembayaran = "SELECT * FROM `pembayaran` WHERE `pesanan_id` = ? ORDER BY `id` DESC LIMIT 1";
$stmt_pay = mysqli_prepare($connection, $sql_pembayaran);
mysqli_stmt_bind_param($stmt_pay, 'i', $id);
mysqli_stmt_execute($stmt_pay);
$result_pay = mysqli_stmt_get_result($stmt_pay);
$pembayaran_data = mysqli_fetch_assoc($result_pay);
mysqli_stmt_close($stmt_pay);

// Ensure Theme is set
$use_theme = $THEME ?? 'default';

// Determine Back URL
$back_url = 'index.php'; // Default for Admin
if ($userRole === 'supplier') {
    $back_url = '../index.php'; // For Suppliers -> Dashboard
} elseif ($userRole !== 'admin') {
    $back_url = '../my_orders.php'; // For Customers
}
?>

<?php include '../views/' . $use_theme . '/header.php'; ?>
<?php include '../views/' . $use_theme . '/sidebar.php'; ?>
<?php include '../views/' . $use_theme . '/topnav.php'; ?>
<?php include '../views/' . $use_theme . '/upper_block.php'; ?>

<style>
    @media print {
        .no-print {
            display: none;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h2>Detail Pesanan</h2>
    <div>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
        <a href="<?= $back_url ?>" class="btn btn-primary">Kembali</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h4>Informasi Pesanan</h4>
                <table class="table table-sm">
                    <tr>
                        <th width="150">Kode Pesanan:</th>
                        <td>
                            <?= htmlspecialchars($pesanan['kode_pesanan']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal:</th>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php
                            $status_class = [
                                'pending' => 'warning',
                                'dikonfirmasi' => 'info',
                                'diproses' => 'info',
                                'dikirim' => 'primary',
                                'selesai' => 'success',
                                'dibatalkan' => 'danger'
                            ];
                            $badge_class = $status_class[$pesanan['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badge_class ?>">
                                <?= ucfirst($pesanan['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Metode Bayar:</th>
                        <td>
                            <?= htmlspecialchars($pesanan['metode_pembayaran']) ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h4>Informasi Pelanggan</h4>
                <table class="table table-sm">
                    <tr>
                        <th width="150">Nama:</th>
                        <td>
                            <?= htmlspecialchars($pesanan['nama_pelanggan']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Alamat:</th>
                        <td>
                            <?= htmlspecialchars($pesanan['alamat']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Telepon:</th>
                        <td>
                            <?= htmlspecialchars($pesanan['telepon']) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($pesanan['catatan']): ?>
            <div class="alert alert-info">
                <strong>Catatan:</strong>
                <?= htmlspecialchars($pesanan['catatan']) ?>
            </div>
        <?php endif; ?>

        <!-- PAYMENT GUIDE SECTION -->
        <?php if ($pesanan['status'] === 'pending'): ?>
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <strong><i class="bi bi-wallet2"></i> Panduan Pembayaran</strong>
                </div>
                <div class="card-body">
                    <?php if ($pesanan['metode_pembayaran'] == 'transfer'): ?>
                        <!-- Transfer Payment Instructions -->
                        <p class="mb-2">Silakan selesaikan pembayaran agar pesanan Anda segera diproses.</p>

                        <div class="alert alert-light border">
                            <h6 class="fw-bold mb-3"><i class="bi bi-bank me-2"></i>Rekening Tujuan Transfer:</h6>
                            <div class="mb-2">
                                <span class="badge bg-primary me-2">BCA</span>
                                <strong>123-456-7890</strong> <span class="text-muted">a.n. TOKO SUMBER JAYA</span>
                            </div>
                            <div>
                                <span class="badge bg-primary me-2">BRI</span>
                                <strong>098-765-4321</strong> <span class="text-muted">a.n. TOKO SUMBER JAYA</span>
                            </div>
                        </div>

                        <?php if ($pembayaran_data): ?>
                            <!-- Payment confirmation exists -->
                            <?php if ($pembayaran_data['status'] == 'menunggu_bukti'): ?>
                                <!-- Stage 1 completed, waiting for proof upload -->
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Telah Konfirmasi Pembayaran!</strong><br>
                                    Silakan selesaikan transfer dan upload bukti pembayaran.
                                </div>

                                <!-- Show Receipt Preview -->
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong><i class="bi bi-receipt"></i> Struk Transfer (Simulasi)</strong>
                                    </div>
                                    <div class="card-body p-0">
                                        <iframe src="generate_struk.php?id=<?= $pembayaran_data['id'] ?>&embed=1"
                                            style="width: 100%; height: 600px; border: none;" title="Struk Transfer"></iframe>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="generate_struk.php?id=<?= $pembayaran_data['id'] ?>" class="btn btn-info btn-lg"
                                        target="_blank">
                                        <i class="bi bi-receipt me-2"></i>Buka Struk (Print/Download)
                                    </a>
                                    <a href="upload_bukti.php?id=<?= $pesanan['id'] ?>" class="btn btn-success btn-lg">
                                        <i class="bi bi-cloud-upload me-2"></i>Upload Bukti Transfer
                                    </a>
                                </div>

                            <?php elseif ($pembayaran_data['status'] == 'pending'): ?>
                                <!-- Proof uploaded, waiting for admin verification -->
                                <div class="alert alert-info">
                                    <i class="bi bi-clock-history me-2"></i>
                                    <strong>Bukti Pembayaran Terkirim!</strong><br>
                                    Bukti transfer Anda sudah diupload pada
                                    <?= date('d M Y H:i', strtotime($pembayaran_data['tanggal_bayar'])) ?>.<br>
                                    <small class="text-muted">Menunggu verifikasi dari admin.</small>
                                </div>
                                <?php if ($pembayaran_data['bukti_bayar']): ?>
                                    <div class="text-center">
                                        <img src="../uploads/pembayaran/<?= $pembayaran_data['bukti_bayar'] ?>" alt="Bukti Transfer"
                                            class="img-thumbnail" style="max-width: 300px;">
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($pembayaran_data['status'] == 'dikonfirmasi'): ?>
                                <!-- Payment confirmed by admin -->
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Pembayaran Lunas!</strong><br>
                                    Pembayaran Anda telah dikonfirmasi oleh admin.
                                </div>
                                <?php if ($pembayaran_data['bukti_bayar']): ?>
                                    <div class="text-center">
                                        <img src="../uploads/pembayaran/<?= $pembayaran_data['bukti_bayar'] ?>" alt="Bukti Transfer"
                                            class="img-thumbnail" style="max-width: 300px;">
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- Other status (ditolak, etc) -->
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle me-2"></i>
                                    <strong>Status:</strong>
                                    <?= ucfirst($pembayaran_data['status']) ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- No payment confirmation yet -->
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <strong>Belum Konfirmasi Pembayaran!</strong><br>
                                Silakan konfirmasi data pembayaran Anda terlebih dahulu sebelum melakukan transfer.
                            </div>
                            <a href="konfirmasi.php?id=<?= $pesanan['id'] ?>" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-credit-card me-2"></i>Konfirmasi Pembayaran
                            </a>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- COD Payment -->
                        <div class="alert alert-info">
                            <i class="bi bi-cash me-2"></i>
                            <strong>Pembayaran COD (Cash on Delivery)</strong><br>
                            Silakan siapkan uang tunai sebesar <strong>
                                <?= formatRupiah($pesanan['total_harga']) ?>
                            </strong> saat kurir mengantarkan paket.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <!-- END PAYMENT GUIDE SECTION -->

        <?php if ($userRole === 'admin' && $pembayaran_data): ?>
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Verifikasi Pembayaran & Bukti (Struk)</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6 border-end">
                            <h6 class="text-muted">Status Pembayaran</h6>
                            <?php if ($pembayaran_data['status'] == 'dikonfirmasi'): ?>
                                <div class="alert alert-success d-flex align-items-center mb-0">
                                    <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                                    <div>
                                        <h5 class="alert-heading mb-0">Sudah Membayar</h5>
                                        <small>Terverifikasi pada
                                            <?= date('d M Y H:i', strtotime($pembayaran_data['updated_at'] ?? $pembayaran_data['tanggal_bayar'])) ?></small>
                                    </div>
                                </div>
                            <?php elseif ($pembayaran_data['status'] == 'pending'): ?>
                                <div class="alert alert-warning d-flex align-items-center mb-0">
                                    <i class="bi bi-exclamation-circle-fill fs-4 me-3"></i>
                                    <div>
                                        <h5 class="alert-heading mb-0">Menunggu Verifikasi</h5>
                                        <small>User telah mengupload bukti transfer</small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex gap-2">
                                        <a href="verify_payment.php?id=<?= $pembayaran_data['id'] ?>&order_id=<?= $pesanan['id'] ?>&action=accept"
                                            class="btn btn-success btn-sm"
                                            onclick="return confirm('Apakah Anda yakin ingin mengkonfirmasi pembayaran ini? Status pesanan akan berubah menjadi Diproses.')">
                                            <i class="bi bi-check-lg"></i> Terima Pembayaran
                                        </a>
                                        <a href="verify_payment.php?id=<?= $pembayaran_data['id'] ?>&order_id=<?= $pesanan['id'] ?>&action=reject"
                                            class="btn btn-danger btn-sm" onclick="return confirm('Tolak pembayaran ini?')">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </a>
                                    </div>
                                    <small class="text-muted fst-italic d-block mt-2">Silakan cek mutasi bank sesuai struk di
                                        samping sebelum konfirmasi.</small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <strong>Status:</strong> <?= ucfirst($pembayaran_data['status']) ?>
                                </div>
                            <?php endif; ?>

                            <table class="table table-sm mt-3 mb-0">
                                <tr>
                                    <td><i class="bi bi-calendar3 me-2"></i>Tanggal Konfirmasi</td>
                                    <td>: <?= date('d M Y H:i', strtotime($pembayaran_data['tanggal_bayar'])) ?></td>
                                </tr>
                                <tr>
                                    <td><i class="bi bi-bank me-2"></i>Bank Pengirim</td>
                                    <td>: <?= htmlspecialchars($pembayaran_data['bank_pengirim'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td><i class="bi bi-person me-2"></i>Nama Pengirim</td>
                                    <td>: <?= htmlspecialchars($pembayaran_data['nama_pengirim'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td><i class="bi bi-credit-card me-2"></i>No. Rekening</td>
                                    <td>: <?= htmlspecialchars($pembayaran_data['nomor_rekening_pengirim'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6 text-center">
                            <h6 class="text-muted mb-3">Bukti Transfer / Struk</h6>
                            <?php if ($pembayaran_data['bukti_bayar']): ?>
                                <div class="position-relative d-inline-block">
                                    <img src="../uploads/pembayaran/<?= $pembayaran_data['bukti_bayar'] ?>" alt="Bukti Struk"
                                        class="img-fluid img-thumbnail shadow-sm" style="max-height: 300px; cursor: pointer;"
                                        onclick="window.open(this.src, '_blank')">
                                    <div class="mt-2">
                                        <a href="../uploads/pembayaran/<?= $pembayaran_data['bukti_bayar'] ?>" target="_blank"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-zoom-in me-1"></i> Perbesar Gambar
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-3 border rounded bg-light">
                                    <h6 class="fw-bold mb-2">E-Receipt / Struk Elektronik</h6>
                                    <p class="small text-muted mb-3">Pelanggan mengkonfirmasi tanpa upload foto. Silakan cek
                                        mutasi bank berdasarkan data pengirim di samping.</p>
                                    <a href="generate_struk.php?id=<?= $pembayaran_data['id'] ?>" target="_blank"
                                        class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-receipt"></i> Lihat Struk Digital
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h5>Detail Produk</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Produk</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $calculated_total = 0;
                    while ($detail = mysqli_fetch_assoc($result_detail)):
                        $calculated_total += $detail['subtotal'];
                        ?>
                        <tr>
                            <td>
                                <?= $no++ ?>
                            </td>
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
                        <th colspan="4" class="text-end">TOTAL:</th>
                        <th>
                            <?php if ($userRole === 'supplier'): ?>
                                <?= formatRupiah($calculated_total) ?>
                            <?php else: ?>
                                <?= formatRupiah($pesanan['total_harga']) ?>
                            <?php endif; ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../views/' . $use_theme . '/lower_block.php'; ?>
<?php include '../views/' . $use_theme . '/footer.php'; ?>