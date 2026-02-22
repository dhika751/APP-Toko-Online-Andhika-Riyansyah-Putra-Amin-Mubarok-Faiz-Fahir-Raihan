<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembayaran');

require_once '../config/database.php';

$sql = "SELECT pb.*, p.kode_pesanan, pl.nama_pelanggan 
        FROM `pembayaran` pb
        LEFT JOIN `pesanan` p ON pb.pesanan_id = p.id
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        ORDER BY pb.id DESC";

$result = mysqli_query($connection, $sql);
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Daftar Pembayaran</h2>
    <a href="add.php" class="btn btn-primary">+ Tambah Pembayaran</a>
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Bayar</th>
                    <th>Pesanan</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $status_class = [
                        'pending' => 'warning',
                        'menunggu_bukti' => 'info',
                        'dikonfirmasi' => 'success',
                        'ditolak' => 'danger'
                    ];
                    $badge_class = $status_class[$row['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_pembayaran']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['kode_pesanan']) ?><br>
                            <small><?= htmlspecialchars($row['nama_pelanggan'] ?? '-') ?></small>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal_bayar'])) ?></td>
                        <td><?= formatRupiah($row['jumlah_bayar']) ?></td>
                        <td><?= htmlspecialchars($row['metode_bayar']) ?></td>
                        <td><span class="badge bg-<?= $badge_class ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td>
                            <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Lihat</a>
                            <?php if ($row['status'] == 'pending'): ?>
                                <a href="confirm.php?id=<?= $row['id'] ?>&action=confirm" class="btn btn-sm btn-success"
                                    onclick="confirmAction(event, this.href, 'Konfirmasi pembayaran ini?'); return false;">Terima</a>
                                <a href="confirm.php?id=<?= $row['id'] ?>&action=reject" class="btn btn-sm btn-danger"
                                    onclick="confirmAction(event, this.href, 'Tolak pembayaran ini?'); return false;">Tolak</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">Belum ada data pembayaran.</div>
<?php endif; ?>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>