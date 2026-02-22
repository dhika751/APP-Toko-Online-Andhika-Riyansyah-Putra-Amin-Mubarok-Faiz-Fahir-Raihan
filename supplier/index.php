<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('supplier');

require_once '../config/database.php';

// Only show suppliers who registered via public form (users_id IS NOT NULL)
// Admin-created suppliers (users_id IS NULL) should not appear here
$result = mysqli_query($connection, "SELECT s.*, u.username FROM `supplier` s 
    INNER JOIN `users` u ON s.users_id = u.id 
    WHERE s.users_id IS NOT NULL 
    ORDER BY s.id DESC");
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Daftar Supplier</h2>
    <!-- Removed "Tambah Supplier" button - suppliers are auto-added via registration -->
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Kode Supplier</th>
                    <th>Nama Supplier</th>
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
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['kode_supplier']) ?></td>
                        <td><?= htmlspecialchars($row['nama_supplier']) ?></td>
                        <td>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                onclick="confirmAction(event, this.href, 'Yakin hapus supplier ini? Akun user juga akan terhapus!'); return false;">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">Belum ada data supplier.</div>
<?php endif; ?>


<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>