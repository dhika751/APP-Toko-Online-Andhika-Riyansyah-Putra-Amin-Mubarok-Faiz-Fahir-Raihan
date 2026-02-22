<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('laporan');

require_once '../config/database.php';

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Get sales data
$sql = "SELECT p.*, pl.nama_pelanggan 
        FROM `pesanan` p 
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        WHERE DATE(p.tanggal_pesanan) BETWEEN ? AND ?
        AND p.status IN ('diproses', 'dikirim', 'selesai')
        ORDER BY p.tanggal_pesanan DESC";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $date_from, $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Calculate totals
$total_pesanan = 0;
$total_pendapatan = 0;
$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
    $total_pesanan++;
    $total_pendapatan += $row['total_harga'];
}

mysqli_stmt_close($stmt);
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<style>
    @media print {
        .no-print {
            display: none;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h2>Laporan Penjualan</h2>
    <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
</div>

<!-- Filter Section -->
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                        value="<?= htmlspecialchars($date_from) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                        value="<?= htmlspecialchars($date_to) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Pesanan</h5>
                <h3><?= $total_pesanan ?> Pesanan</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Pendapatan</h5>
                <h3 class="text-success"><?= formatRupiah($total_pendapatan) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Detail Penjualan (<?= date('d/m/Y', strtotime($date_from)) ?> -
            <?= date('d/m/Y', strtotime($date_to)) ?>)</strong>
    </div>
    <div class="card-body">
        <?php if (count($orders) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Pesanan</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Metode Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($orders as $order):
                            $status_class = [
                                'pending' => 'warning',
                                'diproses' => 'info',
                                'dikirim' => 'primary',
                                'selesai' => 'success'
                            ];
                            $badge_class = $status_class[$order['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($order['kode_pesanan']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['tanggal_pesanan'])) ?></td>
                                <td><?= htmlspecialchars($order['nama_pelanggan'] ?? '-') ?></td>
                                <td><?= formatRupiah($order['total_harga']) ?></td>
                                <td><span class="badge bg-<?= $badge_class ?>"><?= ucfirst($order['status']) ?></span></td>
                                <td><?= htmlspecialchars($order['metode_pembayaran']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">TOTAL PENDAPATAN:</th>
                            <th colspan="3"><?= formatRupiah($total_pendapatan) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Tidak ada data penjualan untuk periode ini.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>