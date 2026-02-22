<?php
require_once 'lib/auth.php';
require_once 'lib/functions.php';
require_once 'config/database.php';

requireAuth();

// If we are strictly checking roles for access to dashboard, do it here.
// For now, assuming all logged in users can see dashboard, or at least admin.

// Helper to get counts
function getCount($table, $where = '')
{
    global $connection;
    $sql = "SELECT COUNT(*) as total FROM `$table`";
    if ($where)
        $sql .= " WHERE $where";
    $result = mysqli_query($connection, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function getSum($table, $field, $where = '')
{
    global $connection;
    $sql = "SELECT SUM($field) as total FROM `$table`";
    if ($where)
        $sql .= " WHERE $where";
    $result = mysqli_query($connection, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}



$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['user_id'] ?? 0;

if ($role === 'admin') {
    // ADMIN STATS
    $total_produk = getCount('produk');
    $total_pesanan = getCount('pesanan');
    $pesanan_pending = getCount('pesanan', "status = 'pending'");
    $total_pendapatan = getSum('pesanan', 'total_harga', "status IN ('diproses', 'dikirim', 'selesai')");
    $pesanan_hari_ini = getCount('pesanan', "DATE(tanggal_pesanan) = CURDATE()");
} elseif ($role === 'supplier') {
    // SUPPLIER STATS
    // 1. Get Supplier ID
    $supplier_id = 0;
    $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
    $stmt_sup = mysqli_prepare($connection, $sql_sup);
    mysqli_stmt_bind_param($stmt_sup, 'i', $user_id);
    mysqli_stmt_execute($stmt_sup);
    $res_sup = mysqli_stmt_get_result($stmt_sup);
    if ($row_sup = mysqli_fetch_assoc($res_sup)) {
        $supplier_id = $row_sup['id'];
    }
    mysqli_stmt_close($stmt_sup);

    // 2. Supplier Stats - count orders containing their products
    if ($supplier_id) {
        // Total orders with supplier's products (completed)
        $sql_completed = "SELECT COUNT(DISTINCT p.id) as total 
                         FROM pesanan p
                         JOIN detail_pesanan dp ON p.id = dp.pesanan_id
                         JOIN produk pr ON dp.produk_id = pr.id
                         WHERE pr.supplier_id = ? AND p.status = 'selesai'";
        $stmt = mysqli_prepare($connection, $sql_completed);
        mysqli_stmt_bind_param($stmt, 'i', $supplier_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $supplier_completed_orders = mysqli_fetch_assoc($res)['total'] ?? 0;
        mysqli_stmt_close($stmt);

        // Total revenue from completed orders
        $sql_revenue = "SELECT SUM(dp.subtotal) as total
                       FROM pesanan p
                       JOIN detail_pesanan dp ON p.id = dp.pesanan_id
                       JOIN produk pr ON dp.produk_id = pr.id
                       WHERE pr.supplier_id = ? AND p.status = 'selesai'";
        $stmt = mysqli_prepare($connection, $sql_revenue);
        mysqli_stmt_bind_param($stmt, 'i', $supplier_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $supplier_total_revenue = mysqli_fetch_assoc($res)['total'] ?? 0;
        mysqli_stmt_close($stmt);

        // Pending orders count
        $sql_pending = "SELECT COUNT(DISTINCT p.id) as total
                       FROM pesanan p
                       JOIN detail_pesanan dp ON p.id = dp.pesanan_id
                       JOIN produk pr ON dp.produk_id = pr.id
                       WHERE pr.supplier_id = ? AND p.status IN ('pending', 'diproses')";
        $stmt = mysqli_prepare($connection, $sql_pending);
        mysqli_stmt_bind_param($stmt, 'i', $supplier_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $supplier_pending_orders = mysqli_fetch_assoc($res)['total'] ?? 0;
        mysqli_stmt_close($stmt);
    } else {
        $supplier_completed_orders = 0;
        $supplier_total_revenue = 0;
        $supplier_pending_orders = 0;
    }
} else {
    // USER STATS
    // 1. Get Pelanggan ID
    $pelanggan_id = 0;
    $sql_pel = "SELECT id FROM pelanggan WHERE users_id = ?";
    $stmt_pel = mysqli_prepare($connection, $sql_pel);
    mysqli_stmt_bind_param($stmt_pel, 'i', $user_id);
    mysqli_stmt_execute($stmt_pel);
    $res_pel = mysqli_stmt_get_result($stmt_pel);
    if ($row_pel = mysqli_fetch_assoc($res_pel)) {
        $pelanggan_id = $row_pel['id'];
    }
    mysqli_stmt_close($stmt_pel);

    // 2. My Stats
    $my_total_orders = getCount('pesanan', "pelanggan_id = $pelanggan_id");
    $my_pending_orders = getCount('pesanan', "pelanggan_id = $pelanggan_id AND status = 'pending'");
    $my_total_spend = getSum('pesanan', 'total_harga', "pelanggan_id = $pelanggan_id AND status = 'selesai'");

    // 3. Cart Items
    $cart_count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['qty'];
        }
    }
}

// Recent orders - filter by role
$sql_recent = "SELECT p.*, pl.nama_pelanggan 
               FROM pesanan p 
               LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id";

if ($role === 'supplier' && isset($supplier_id) && $supplier_id) {
    // For supplier: show orders containing their products
    $sql_recent = "SELECT DISTINCT p.*, pl.nama_pelanggan 
                   FROM pesanan p 
                   LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
                   JOIN detail_pesanan dp ON p.id = dp.pesanan_id
                   JOIN produk pr ON dp.produk_id = pr.id
                   WHERE pr.supplier_id = $supplier_id";
} elseif ($role !== 'admin') {
    // Use the customer ID we fetched earlier
    if (isset($pelanggan_id) && $pelanggan_id) {
        $sql_recent .= " WHERE p.pelanggan_id = " . (int) $pelanggan_id;
    } else {
        $sql_recent .= " WHERE 1=0";
    }
}

$sql_recent .= " ORDER BY p.tanggal_pesanan DESC LIMIT 5";
$result_recent = mysqli_query($connection, $sql_recent);
?>

<?php include 'views/' . $THEME . '/header.php'; ?>
<?php include 'views/' . $THEME . '/sidebar.php'; ?>
<?php include 'views/' . $THEME . '/topnav.php'; ?>
<?php include 'views/' . $THEME . '/upper_block.php'; ?>

<div class="mb-4">
    <h2>Dashboard <?= $role === 'admin' ? 'Toko Online' : 'Saya' ?></h2>
    <p class="text-muted">Selamat datang kembali, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</p>
</div>

<div class="row mb-4">
    <?php if ($role === 'admin'): ?>
        <!-- ADMIN WIDGETS -->
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-box-seam me-2"></i>Total Produk</h5>
                    <h2 class="card-text"><?= $total_produk ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-cash-stack me-2"></i>Pendapatan</h5>
                    <h2 class="card-text"><?= formatRupiah($total_pendapatan) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clock-history me-2"></i>Pesanan Pending</h5>
                    <h2 class="card-text"><?= $pesanan_pending ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-bag-plus me-2"></i>Pesanan Baru</h5>
                    <h2 class="card-text"><?= $pesanan_hari_ini ?></h2>
                </div>
            </div>
        </div>
    <?php elseif ($role === 'supplier'): ?>
        <!-- SUPPLIER WIDGETS -->
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Pesanan Saya</h5>
                            <h2 class="card-text"><?= $supplier_completed_orders ?></h2>
                            <small class="text-white-50">(Pesanan Selesai)</small>
                        </div>
                        <i class="bi bi-bag-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Belanja</h5>
                            <h2 class="card-text"><?= formatRupiah($supplier_total_revenue) ?></h2>
                            <small class="text-white-50">(Pesanan Selesai)</small>
                        </div>
                        <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Keranjang Belanja</h5>
                            <h2 class="card-text"><?= $supplier_pending_orders ?> <small class="fs-6">Item</small></h2>
                        </div>
                        <i class="bi bi-cart3 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- USER WIDGETS -->
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Pesanan Saya</h5>
                            <h2 class="card-text"><?= $my_total_orders ?></h2>
                        </div>
                        <i class="bi bi-bag-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Belanja</h5>
                            <h2 class="card-text"><?= formatRupiah($my_total_spend) ?></h2>
                            <small class="text-white-50">(Pesanan Selesai)</small>
                        </div>
                        <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Keranjang Belanja</h5>
                            <h2 class="card-text"><?= $cart_count ?> <small class="fs-6">Item</small></h2>
                        </div>
                        <i class="bi bi-cart3 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <strong>Pesanan Terbaru</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_recent)):
                        $status_class = [
                            'pending' => 'warning',
                            'diproses' => 'info',
                            'dikirim' => 'primary',
                            'selesai' => 'success',
                            'dibatalkan' => 'danger'
                        ];
                        $badge_class = $status_class[$row['status']] ?? 'secondary';

                        // Calculate display total based on role
                        $display_total = $row['total_harga'];

                        if ($role === 'supplier' && isset($supplier_id) && $supplier_id) {
                            $sql_sub_total = "SELECT SUM(dp.subtotal) as total 
                                             FROM detail_pesanan dp 
                                             JOIN produk pr ON dp.produk_id = pr.id 
                                             WHERE dp.pesanan_id = ? AND pr.supplier_id = ?";
                            $stmt_sub = mysqli_prepare($connection, $sql_sub_total);
                            mysqli_stmt_bind_param($stmt_sub, 'ii', $row['id'], $supplier_id);
                            mysqli_stmt_execute($stmt_sub);
                            $res_sub = mysqli_stmt_get_result($stmt_sub);
                            if ($row_sub = mysqli_fetch_assoc($res_sub)) {
                                $display_total = $row_sub['total'] ?? 0;
                            }
                            mysqli_stmt_close($stmt_sub);
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_pesanan']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td><?= formatRupiah($display_total) ?></td>
                            <td><span class="badge bg-<?= $badge_class ?>"><?= ucfirst($row['status']) ?></span></td>
                            <td>
                                <a href="pesanan/view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Lihat</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="text-end mt-3">
                <a href="pesanan/index.php" class="btn btn-primary">Lihat Semua Pesanan</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'):
    // Data for Chart (Last 30 Days Revenue)
    $sales_data = [];
    $dates = [];

    // Initialize last 30 days with 0
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d/m', strtotime($date)); // Format for label
        $sales_data[$date] = 0;
    }

    $sql_chart = "SELECT DATE(tanggal_pesanan) as tgl, SUM(total_harga) as total 
                  FROM pesanan 
                  WHERE status IN ('diproses', 'dikirim', 'selesai') 
                  AND tanggal_pesanan >= DATE(NOW()) - INTERVAL 30 DAY
                  GROUP BY DATE(tanggal_pesanan)";

    $res_chart = mysqli_query($connection, $sql_chart);
    while ($row_chart = mysqli_fetch_assoc($res_chart)) {
        if (isset($sales_data[$row_chart['tgl']])) {
            $sales_data[$row_chart['tgl']] = (float) $row_chart['total'];
        }
    }

    $chart_labels = json_encode($dates);
    $chart_values = json_encode(array_values($sales_data));
    ?>
    <!-- Sales Chart -->
    <div class="card mt-4 mb-4">
        <div class="card-header">
            <strong>Grafik Pendapatan (30 Hari Terakhir)</strong>
        </div>
        <div class="card-body">
            <canvas id="salesChart" width="100" height="40"></canvas>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $chart_labels ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?= $chart_values ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.2)', // BOOTSTRAP SUCCESS COLOR with opacity
                    borderColor: 'rgba(25, 135, 84, 1)', // BOOTSTRAP SUCCESS COLOR
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value, index, values) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
<?php endif; ?>

<?php include 'views/' . $THEME . '/lower_block.php'; ?>
<?php include 'views/' . $THEME . '/footer.php'; ?>