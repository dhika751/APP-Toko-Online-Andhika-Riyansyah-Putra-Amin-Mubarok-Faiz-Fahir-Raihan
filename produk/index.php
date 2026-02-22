<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
// Allow both admin and supplier
requireRoleAccess(['admin', 'supplier']);

require_once '../config/database.php';

// Get filter parameters
$kategori_filter = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, k.nama_kategori, s.nama_supplier 
        FROM `produk` p 
        LEFT JOIN `kategori_produk` k ON p.kategori_id = k.id
        LEFT JOIN `supplier` s ON p.supplier_id = s.id
        WHERE 1=1";

$params = [];
$types = '';

// Filter by Supplier if logged in as supplier
if ($_SESSION['role'] === 'supplier') {
    $user_id = $_SESSION['user_id'];
    // Get supplier ID
    $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
    $stmt_sup = mysqli_prepare($connection, $sql_sup);
    mysqli_stmt_bind_param($stmt_sup, 'i', $user_id);
    mysqli_stmt_execute($stmt_sup);
    $res_sup = mysqli_stmt_get_result($stmt_sup);
    $sup = mysqli_fetch_assoc($res_sup);

    if ($sup) {
        $sql .= " AND p.supplier_id = ?";
        $params[] = $sup['id'];
        $types .= 'i';
    } else {
        // Fallback if supplier data not found (shouldn't happen)
        $sql .= " AND 1=0";
    }
}

if ($kategori_filter) {
    $sql .= " AND p.kategori_id = ?";
    $params[] = $kategori_filter;
    $types .= 'i';
}

if ($search) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$sql .= " ORDER BY p.id DESC";

$stmt = mysqli_prepare($connection, $sql);

if ($params) {
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
    <h2>Daftar Produk</h2>
    <a href="add.php" class="btn btn-primary">+ Tambah Produk</a>
</div>

<!-- Filter Section -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-4">
                    <label for="kategori" class="form-label">Kategori</label>
                    <?= dropdownFromTable('kategori_produk', 'id', 'nama_kategori', $kategori_filter, 'kategori', '-- Semua Kategori --') ?>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari Produk</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="<?= htmlspecialchars($search) ?>" placeholder="Nama atau kode produk">
                </div>
                <div class="col-md-4">
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
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <?php if ($_SESSION['role'] !== 'supplier'): ?>
                        <th>Supplier</th>
                    <?php endif; ?>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_produk']) ?></td>
                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                        <td><?= formatRupiah($row['harga']) ?></td>
                        <td>
                            <span class="badge <?= $row['stok'] < 10 ? 'bg-danger' : 'bg-success' ?>">
                                <?= $row['stok'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['satuan']) ?></td>
                        <?php if ($_SESSION['role'] !== 'supplier'): ?>
                            <td><?= $row['supplier_id'] === null ? 'Admin' : htmlspecialchars($row['nama_supplier'] ?? '-') ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ($row['photo']): ?>
                                <img src="../uploads/produk/<?= htmlspecialchars($row['photo']) ?>" alt="Foto Produk"
                                    style="max-width: 50px; max-height: 50px;">
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                onclick="confirmAction(event, this.href, 'Yakin hapus produk ini?'); return false;">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">Belum ada data produk.</div>
<?php endif; ?>


<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>