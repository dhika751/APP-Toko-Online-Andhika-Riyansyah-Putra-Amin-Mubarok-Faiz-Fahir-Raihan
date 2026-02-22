<?php
require_once 'lib/auth.php';
requireAuth();
require_once 'views/default/header.php';
require_once 'views/default/sidebar.php';
require_once 'views/default/topnav.php';
?>
<?php
if (empty($_SESSION['cart'])) {
    echo "<script>window.location.href='cart.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
// Fetch existing data if any
$query = "SELECT * FROM pelanggan WHERE users_id = '$user_id' LIMIT 1";
$result = mysqli_query($connection, $query);
$pelanggan = mysqli_fetch_assoc($result);

$nama = $pelanggan['nama_pelanggan'] ?? $_SESSION['username']; // Fallback to username
$telepon = $pelanggan['telepon'] ?? '';
$alamat = $pelanggan['alamat'] ?? '';

?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4 fw-bold">Checkout Pesanan</h2>

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt me-2"></i>Informasi Pengiriman</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="actions/order_handler.php" method="POST" id="checkoutForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Penerima</label>
                                <input type="text" name="nama" class="form-control"
                                    value="<?= htmlspecialchars($nama) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nomor Telepon / WhatsApp</label>
                                <input type="text" name="telepon" class="form-control"
                                    value="<?= htmlspecialchars($telepon) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="3"
                                    placeholder="Jalan, RT/RW, Kelurahan, Kecamatan..."
                                    required><?= htmlspecialchars($alamat) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Catatan Pesanan (Opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2"
                                    placeholder="Contoh: Tolong packing kayu..."></textarea>
                            </div>

                            <h5 class="mt-4 mb-3 fw-bold"><i class="bi bi-wallet2 me-2"></i>Metode Pembayaran</h5>
                            <div class="d-flex gap-3 mb-4">
                                <div class="form-check border p-3 rounded w-100">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="transfer"
                                        value="transfer" checked>
                                    <label class="form-check-label w-100 fw-bold" for="transfer">
                                        Transfer Bank
                                        <small class="d-block text-muted">BCA, BRI</small>
                                    </label>
                                </div>
                                <div class="form-check border p-3 rounded w-100">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="cod"
                                        value="cod">
                                    <label class="form-check-label w-100 fw-bold" for="cod">
                                        COD (Bayar di Tempat)
                                        <small class="d-block text-muted">Bayar saat barang sampai</small>
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Info:</strong> Setelah order dibuat, Anda akan melihat instruksi pembayaran
                                lengkap di halaman "Pesanan Saya".
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 20px;">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Ringkasan Pesanan</h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-group list-group-flush mb-3">
                            <?php
                            $total = 0;
                            foreach ($_SESSION['cart'] as $item):
                                $subtotal = $item['price'] * $item['qty'];
                                $total += $subtotal;
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                        <small class="text-muted"><?= $item['qty'] ?> x Rp
                                            <?= number_format($item['price'], 0, ',', '.') ?></small>
                                    </div>
                                    <span class="fw-bold">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="d-flex justify-content-between mb-4 mt-4">
                            <h5 class="fw-bold">Total Tagihan</h5>
                            <h4 class="fw-bold text-primary">Rp <?= number_format($total, 0, ',', '.') ?></h4>
                        </div>

                        <button type="submit" form="checkoutForm"
                            class="btn btn-primary w-100 py-3 rounded-pill fw-bold">
                            <i class="bi bi-check-circle me-2"></i> Buat Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/default/footer.php'; ?>