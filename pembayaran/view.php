<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembayaran');

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

$sql = "SELECT pb.*, p.kode_pesanan, p.total_harga as total_tagihan, pl.nama_pelanggan 
        FROM `pembayaran` pb
        LEFT JOIN `pesanan` p ON pb.pesanan_id = p.id
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        WHERE pb.id = ?";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    redirect('index.php');
}
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Detail Pembayaran</h2>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Informasi Pembayaran</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Kode Bayar</th>
                        <td>
                            <?= htmlspecialchars($data['kode_pembayaran']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal</th>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($data['tanggal_bayar'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Jumlah Bayar</th>
                        <td>
                            <?= formatRupiah($data['jumlah_bayar']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Metode</th>
                        <td>
                            <?= htmlspecialchars($data['metode_bayar']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?= ucfirst($data['status']) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Informasi Pesanan</div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Kode Pesanan</th>
                        <td>
                            <?= htmlspecialchars($data['kode_pesanan']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Pelanggan</th>
                        <td>
                            <?= htmlspecialchars($data['nama_pelanggan']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Total Tagihan</th>
                        <td>
                            <?= formatRupiah($data['total_tagihan']) ?>
                        </td>
                    </tr>
                </table>
                <a href="../pesanan/view.php?id=<?= $data['pesanan_id'] ?>" class="btn btn-info btn-sm">Lihat Detail
                    Pesanan</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Bukti Pembayaran</div>
            <div class="card-body text-center">
                <?php if ($data['bukti_bayar']): ?>
                    <?php
                    $ext = strtolower(pathinfo($data['bukti_bayar'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])):
                        ?>
                        <img src="../uploads/pembayaran/<?= htmlspecialchars($data['bukti_bayar']) ?>" class="img-fluid"
                            alt="Bukti Bayar">
                    <?php else: ?>
                        <a href="../uploads/pembayaran/<?= htmlspecialchars($data['bukti_bayar']) ?>" class="btn btn-primary"
                            target="_blank">Download Bukti Bayar</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Tidak ada bukti pembayaran yang diupload.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>