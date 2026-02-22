<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id)
    redirect('index.php');

// Get master data
$sql_master = "SELECT p.*, s.nama_supplier, s.alamat, s.telepon
               FROM `pembelian` p
               LEFT JOIN `supplier` s ON p.supplier_id = s.id
               WHERE p.id = ?";
$stmt = mysqli_prepare($connection, $sql_master);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$pembelian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pembelian)
    redirect('index.php');

// Get details
$sql_detail = "SELECT dp.*, p.nama_produk, p.kode_produk, p.satuan
               FROM `detail_pembelian` dp
               LEFT JOIN `produk` p ON dp.produk_id = p.id
               WHERE dp.pembelian_id = ?";
$stmt = mysqli_prepare($connection, $sql_detail);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result_detail = mysqli_stmt_get_result($stmt);
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Detail Pembelian #
        <?= htmlspecialchars($pembelian['kode_pembelian']) ?>
    </h2>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">Info Pembelian</div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="150">Tanggal</td>
                        <td>:
                            <?= date('d M Y H:i', strtotime($pembelian['tanggal'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>: <span class="badge bg-secondary">
                                <?= ucfirst($pembelian['status']) ?>
                            </span>
                            <?php if ($pembelian['status'] === 'pending'): ?>
                                <div class="mt-2">
                                    <form action="update_status.php" method="POST" class="d-inline"
                                        onsubmit="return confirm('Apakah Anda yakin ingin memproses pembelian ini? Stok akan ditambahkan.');">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <input type="hidden" name="status" value="selesai">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg"></i> Proses Selesai
                                        </button>
                                    </form>
                                    <form action="update_status.php" method="POST" class="d-inline ms-1"
                                        onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pembelian ini?');">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <input type="hidden" name="status" value="dibatalkan">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-lg"></i> Batalkan
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Catatan</td>
                        <td>:
                            <?= nl2br(htmlspecialchars($pembelian['catatan'] ?? '-')) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-info text-dark">Info Supplier</div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="150">Nama</td>
                        <td>:
                            <?= htmlspecialchars($pembelian['nama_supplier'] ?? 'Unknown') ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td>:
                            <?= htmlspecialchars($pembelian['alamat'] ?? '-') ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Telepon</td>
                        <td>:
                            <?= htmlspecialchars($pembelian['telepon'] ?? '-') ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Daftar Item</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Produk</th>
                        <th>Nama Produk</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result_detail)):
                        ?>
                        <tr>
                            <td>
                                <?= $no++ ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['kode_produk']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['nama_produk']) ?>
                            </td>
                            <td class="text-end">
                                <?= formatRupiah($row['harga_beli']) ?>
                            </td>
                            <td class="text-center">
                                <?= $row['jumlah'] ?>
                                <?= $row['satuan'] ?>
                            </td>
                            <td class="text-end">
                                <?= formatRupiah($row['subtotal']) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Total Pembelian</th>
                        <th class="text-end">
                            <?= formatRupiah($pembelian['total_harga']) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>