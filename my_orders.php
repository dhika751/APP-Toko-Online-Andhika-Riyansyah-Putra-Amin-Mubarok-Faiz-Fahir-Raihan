<?php
require_once 'lib/auth.php';
requireAuth();
require_once 'views/default/header.php';
require_once 'views/default/sidebar.php';
require_once 'views/default/topnav.php';

$user_id = $_SESSION['user_id'];

// Get orders for logged-in user
$sql = "SELECT p.*, 
        (SELECT status FROM pembayaran WHERE pesanan_id = p.id ORDER BY id DESC LIMIT 1) as payment_status
        FROM `pesanan` p 
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        WHERE pl.users_id = ?
        ORDER BY p.id DESC";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4 fw-bold">Pesanan Saya</h2>

        <?php
        // Check for any cancelled orders to show alert
        $has_cancelled = false;
        mysqli_data_seek($result, 0); // Reset pointer
        while ($chk = mysqli_fetch_assoc($result)) {
            if ($chk['status'] == 'dibatalkan') {
                $has_cancelled = true;
                break;
            }
        }
        mysqli_data_seek($result, 0); // Reset pointer again for display
        ?>

        <?php if ($has_cancelled): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                <strong>Pemberitahuan Pembatalan:</strong> Ada pesanan Anda yang dibatalkan.
                Jika pesanan tersebut sudah Anda bayar, dana akan kami <strong>REFUND (Kembalikan)</strong> sepenuhnya.
                Silakan hubungi admin jika dana belum diterima dalam 1x24 jam.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">No. Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary">
                                            #<?= htmlspecialchars($row['kode_pesanan']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-calendar text-muted me-1"></i>
                                            <?= date('d M Y H:i', strtotime($row['tanggal_pesanan'])) ?>
                                        </td>
                                        <td class="fw-bold">
                                            Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = 'bg-secondary';
                                            if ($row['status'] == 'pending')
                                                $badgeClass = 'bg-warning text-dark';
                                            elseif ($row['status'] == 'diproses')
                                                $badgeClass = 'bg-info text-dark';
                                            elseif ($row['status'] == 'dikirim')
                                                $badgeClass = 'bg-primary';
                                            elseif ($row['status'] == 'selesai')
                                                $badgeClass = 'bg-success';
                                            elseif ($row['status'] == 'dibatalkan')
                                                $badgeClass = 'bg-danger';
                                            ?>
                                            <span class="badge rounded-pill <?= $badgeClass ?>">
                                                <?= ucfirst($row['status']) ?>
                                                <?php if ($row['status'] == 'dibatalkan'): ?>
                                                    <br><small class="text-white-50" style="font-size: 0.7em;">(Dana
                                                        Dikembalikan)</small>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // Payment Status Badge
                                            if ($row['metode_pembayaran'] == 'cod') {
                                                echo '<span class="badge bg-secondary">COD</span>';
                                            } else {
                                                // Transfer method - check payment status
                                                // PRIORITY: If order is Selesai/Dikirim, imply Lunas (Admin might have skipped payment verify step)
                                                if (in_array($row['status'], ['selesai', 'dikirim'])) {
                                                    echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lunas</span>';
                                                } elseif ($row['payment_status'] == 'dikonfirmasi') {
                                                    echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lunas</span>';
                                                } elseif ($row['payment_status'] == 'pending') {
                                                    echo '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending</span>';
                                                } elseif ($row['payment_status'] == 'menunggu_bukti') {
                                                    echo '<span class="badge bg-info"><i class="bi bi-hourglass-split me-1"></i>Menunggu Bukti</span>';
                                                } else {
                                                    // Default: check if status pending
                                                    if ($row['status'] == 'pending') {
                                                        echo '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Belum Lunas</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">' . ucfirst($row['payment_status'] ?? '-') . '</span>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <!-- Detail Button Placeholder -->
                                            <a href="pesanan/view.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 text-muted">Belum ada riwayat pesanan</h4>
                    <p class="text-muted">Anda belum melakukan checkout barang apapun.</p>
                    <a href="catalog.php" class="btn btn-primary mt-3">Mulai Belanja</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
mysqli_stmt_close($stmt);
require_once 'views/default/footer.php';
?>