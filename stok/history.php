<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('stok');

require_once '../config/database.php';

// Get filter
$produk_filter = $_GET['produk'] ?? '';
$jenis_filter = $_GET['jenis'] ?? '';

$sql = "SELECT s.*, p.nama_produk, p.kode_produk 
        FROM `stok_keluar_masuk` s
        LEFT JOIN `produk` p ON s.produk_id = p.id
        WHERE 1=1";

$params = [];
$types = '';

if ($produk_filter) {
    $sql .= " AND s.produk_id = ?";
    $params[] = $produk_filter;
    $types .= 'i';
}

if ($jenis_filter) {
    $sql .= " AND s.jenis = ?";
    $params[] = $jenis_filter;
    $types .= 's';
}

$sql .= " ORDER BY s.tanggal DESC";

$stmt = mysqli_prepare($connection, $sql);

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Riwayat Stok Keluar/Masuk</h2>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>

            <!-- Filter Section -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="produk" class="form-label">Produk</label>
                                <?= dropdownFromTable('produk', 'id', 'nama_produk', $produk_filter, 'produk', '-- Semua Produk --') ?>
                            </div>
                            <div class="col-md-3">
                                <label for="jenis" class="form-label">Jenis</label>
                                <select class="form-control" id="jenis" name="jenis">
                                    <option value="">-- Semua Jenis --</option>
                                    <option value="masuk" <?= $jenis_filter == 'masuk' ? 'selected' : '' ?>>Stok Masuk</option>
                                    <option value="keluar" <?= $jenis_filter == 'keluar' ? 'selected' : '' ?>>Stok Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="history.php" class="btn btn-secondary">Reset</a>
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
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                                $badge_class = $row['jenis'] == 'masuk' ? 'bg-success' : 'bg-danger';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($row['nama_produk'] ?? 'Produk Dihapus') ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= ucfirst($row['jenis']) ?></span></td>
                                    <td><?= $row['jumlah'] ?></td>
                                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Belum ada riwayat stok.</div>
            <?php endif; ?>


<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
