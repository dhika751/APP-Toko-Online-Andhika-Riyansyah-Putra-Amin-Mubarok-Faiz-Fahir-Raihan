<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();

// Check if user is admin or supplier
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$supplier_id = null;

if ($role === 'supplier') {
    // Get supplier ID from users_id
    $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
    $stmt_sup = mysqli_prepare($connection, $sql_sup);
    mysqli_stmt_bind_param($stmt_sup, 'i', $user_id);
    mysqli_stmt_execute($stmt_sup);
    $res_sup = mysqli_stmt_get_result($stmt_sup);
    $sup = mysqli_fetch_assoc($res_sup);

    if ($sup) {
        $supplier_id = $sup['id'];
    } else {
        die("Error: Akun anda tidak terhubung dengan data supplier.");
    }
} elseif ($role === 'admin') {
    // Admin can view all or filter by supplier
    $supplier_id = $_GET['supplier_id'] ?? null;
} else {
    redirect('../index.php'); // Access denied
}

require_once '../config/database.php';

// Prepare query - support both pesanan and pembelian
$sql = "SELECT ts.*, 
               p.kode_pesanan, pl.nama_pelanggan, 
               pm.kode_pembelian,
               s.nama_supplier
        FROM `transaksi_supplier` ts
        LEFT JOIN `pesanan` p ON ts.pesanan_id = p.id
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        LEFT JOIN `pembelian` pm ON ts.pembelian_id = pm.id
        LEFT JOIN `supplier` s ON ts.supplier_id = s.id
        WHERE 1=1";

$params = [];
$types = "";

if ($supplier_id) {
    $sql .= " AND ts.supplier_id = ?";
    $params[] = $supplier_id;
    $types .= "i";
}

$sql .= " ORDER BY ts.tanggal DESC";

$stmt = mysqli_prepare($connection, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Riwayat Pendapatan Supplier</h2>
</div>

<div class="card">
    <div class="card-body">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kode Transaksi</th>
                            <?php if ($role === 'admin'): ?>
                                <th>Supplier</th>
                            <?php endif; ?>
                            <th class="text-end">Total Pendapatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)):
                            ?>
                            <tr>
                                <td>
                                    <?= $no++ ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?>
                                </td>
                                <td>
                                    <?php if ($row['kode_pesanan']): ?>
                                        <?= htmlspecialchars($row['kode_pesanan']) ?><br>
                                        <small class="text-muted">Pelanggan:
                                            <?= htmlspecialchars($row['nama_pelanggan'] ?? '-') ?>
                                        </small>
                                    <?php elseif ($row['kode_pembelian']): ?>
                                        <?= htmlspecialchars($row['kode_pembelian']) ?><br>
                                        <small class="text-muted badge bg-info">Pembelian dari Supplier</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($role === 'admin'): ?>
                                    <td>
                                        <?= htmlspecialchars($row['nama_supplier'] ?? '-') ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-end fw-bold">
                                    <?= formatRupiah($row['total_pendapatan']) ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Menunggu Pencairan</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Sudah Dicairkan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="cetak_struk_supplier.php?id=<?= $row['id'] ?>" target="_blank"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-printer"></i> Cetak Struk
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Belum ada riwayat transaksi.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>