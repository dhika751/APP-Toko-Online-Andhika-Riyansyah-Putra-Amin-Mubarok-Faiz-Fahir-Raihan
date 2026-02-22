<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('stok');

require_once '../config/database.php';

// Get filter
$produk_filter = $_GET['produk'] ?? '';

$sql = "SELECT p.*, k.nama_kategori 
        FROM `produk` p
        LEFT JOIN `kategori_produk` k ON p.kategori_id = k.id
        WHERE 1=1";

if ($produk_filter) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
}

$sql .= " ORDER BY p.stok ASC, p.nama_produk ASC";

$stmt = mysqli_prepare($connection, $sql);

if ($produk_filter) {
    $search_param = "%$produk_filter%";
    mysqli_stmt_bind_param($stmt, 'ss', $search_param, $search_param);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Daftar Stok Produk</h2>
                <div>
                    <a href="add.php" class="btn btn-primary">+ Tambah Stok Masuk/Keluar</a>
                    <a href="history.php" class="btn btn-info">📊 Riwayat Stok</a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="produk" class="form-label">Cari Produk</label>
                                <input type="text" class="form-control" id="produk" name="produk" value="<?= htmlspecialchars($produk_filter) ?>" placeholder="Nama atau kode produk">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="index.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Produk</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Satuan</th>
                                <th>Harga</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                                $stok_status = '';
                                $stok_class = '';
                                
                                if ($row['stok'] == 0) {
                                    $stok_status = 'Habis';
                                    $stok_class = 'bg-danger';
                                } elseif ($row['stok'] < 10) {
                                    $stok_status = 'Stok Menipis';
                                    $stok_class = 'bg-warning';
                                } else {
                                    $stok_status = 'Tersedia';
                                    $stok_class = 'bg-success';
                                }
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['kode_produk']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge <?= $stok_class ?>">
                                            <?= $row['stok'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['satuan']) ?></td>
                                    <td><?= formatRupiah($row['harga']) ?></td>
                                    <td><?= $stok_status ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Belum ada data produk.</div>
            <?php endif; ?>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
