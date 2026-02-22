<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$result = mysqli_query($connection, "SELECT p.*, s.nama_supplier 
                                     FROM `pembelian` p 
                                     LEFT JOIN `supplier` s ON p.supplier_id = s.id 
                                     ORDER BY p.tanggal DESC");
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Riwayat Pembelian</h2>
    <div>
        <button type="button" id="bulkDeleteBtn" class="btn btn-danger" style="display:none;"
            onclick="bulkDeletePembelian()">
            <i class="fas fa-trash"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
        </button>
        <a href="add.php" class="btn btn-primary">+ Tambah Pembelian</a>
    </div>
</div>

<?php if (mysqli_num_rows($result) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th width="30">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    </th>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Kode</th>
                    <th>Supplier</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)):
                    $status_class = [
                        'pending' => 'warning',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger'
                    ];
                    $badge = $status_class[$row['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="pembelian-checkbox" value="<?= $row['id'] ?>"
                                onchange="updateSelectedCount()">
                        </td>
                        <td>
                            <?= $row['id'] ?>
                        </td>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['kode_pembelian']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['nama_supplier'] ?? '-') ?>
                        </td>
                        <td>
                            <?= formatRupiah($row['total_harga']) ?>
                        </td>
                        <td><span class="badge bg-<?= $badge ?>">
                                <?= ucfirst($row['status']) ?>
                            </span></td>
                        <td>
                            <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Lihat</a>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                onclick="confirmAction(event, this.href, 'Yakin hapus? Stok akan dikembalikan (dikurangi).'); return false;">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">Belum ada data pembelian.</div>
<?php endif; ?>

<script>
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.pembelian-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.pembelian-checkbox:checked');
        const count = checkboxes.length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';

        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.pembelian-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.checked = count === allCheckboxes.length && count > 0;
    }

    function bulkDeletePembelian() {
        const checkboxes = document.querySelectorAll('.pembelian-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);

        if (ids.length === 0) {
            alert('Pilih minimal 1 pembelian untuk dihapus');
            return;
        }

        if (confirm(`Yakin hapus ${ids.length} pembelian terpilih? Stok akan dikembalikan (dikurangi).`)) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'bulk_delete.php';

            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>