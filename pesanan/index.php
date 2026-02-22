<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pesanan');

require_once '../config/database.php';


// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$payment_status_filter = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sql = "SELECT p.*, pl.nama_pelanggan, 
        (SELECT status FROM pembayaran WHERE pesanan_id = p.id ORDER BY id DESC LIMIT 1) as status_bayar
        FROM `pesanan` p 
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        WHERE 1=1";

$params = [];
$types = '';

if ($status_filter) {
    // If status filter is 'pending', we still want both pending order and pending payment
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($payment_status_filter) {
    if ($payment_status_filter === 'pending') {
        // Special case for notification: Find orders with specific payment status
        $sql .= " AND EXISTS (SELECT 1 FROM pembayaran py WHERE py.pesanan_id = p.id AND py.status = ?)";
        $params[] = $payment_status_filter;
        $types .= 's';
    }
}

if ($date_from) {
    $sql .= " AND DATE(p.tanggal_pesanan) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $sql .= " AND DATE(p.tanggal_pesanan) <= ?";
    $params[] = $date_to;
    $types .= 's';
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
    <h2>Daftar Pesanan</h2>
    <div>
        <button type="button" id="bulkDeleteBtn" class="btn btn-danger" style="display:none;"
            onclick="bulkDeletePesanan()">
            <i class="fas fa-trash"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
        </button>
        <a href="add.php" class="btn btn-primary">+ Tambah Pesanan</a>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">-- Semua Status --</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="diproses" <?= $status_filter == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="dikirim" <?= $status_filter == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="selesai" <?= $status_filter == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="dibatalkan" <?= $status_filter == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                        value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                        value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-3">
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
                    <th width="30">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    </th>
                    <th>No</th>
                    <th>Kode Pesanan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Metode Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $status_class = [
                        'pending' => 'warning',
                        'diproses' => 'info',
                        'dikirim' => 'primary',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger'
                    ];
                    $badge_class = $status_class[$row['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="pesanan-checkbox" value="<?= $row['id'] ?>"
                                onchange="updateSelectedCount()">
                        </td>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kode_pesanan']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_pelanggan'] ?? '-') ?></td>
                        <td><?= formatRupiah($row['total_harga']) ?></td>
                        <td><span class="badge bg-<?= $badge_class ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td>
                            <?= htmlspecialchars($row['metode_pembayaran']) ?>
                            <?php if (($row['status_bayar'] ?? '') === 'pending'): ?>
                                <br><span class="badge bg-info text-dark" style="font-size: 0.7em;"><i
                                        class="bi bi-hourglass-split"></i> Bukti Uploaded</span>
                            <?php elseif (($row['status_bayar'] ?? '') === 'dikonfirmasi'): ?>
                                <br><span class="badge bg-success" style="font-size: 0.7em;"><i class="bi bi-check-circle"></i>
                                    Lunas</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Lihat</a>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                                onclick="confirmAction(event, this.href, 'Yakin hapus pesanan ini? Stok akan dikembalikan.'); return false;">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">Belum ada data pesanan.</div>
<?php endif; ?>

<script>
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.pesanan-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.pesanan-checkbox:checked');
        const count = checkboxes.length;
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';

        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.pesanan-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.checked = count === allCheckboxes.length && count > 0;
    }

    function bulkDeletePesanan() {
        const checkboxes = document.querySelectorAll('.pesanan-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);

        if (ids.length === 0) {
            alert('Pilih minimal 1 pesanan untuk dihapus');
            return;
        }

        if (confirm(`Yakin hapus ${ids.length} pesanan terpilih? Stok akan dikembalikan.`)) {
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