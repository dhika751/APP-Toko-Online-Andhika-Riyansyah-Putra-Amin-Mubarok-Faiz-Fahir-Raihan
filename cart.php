<?php
require_once 'lib/auth.php';
requireAuth();
require_once 'views/default/header.php';
require_once 'views/default/sidebar.php';
require_once 'views/default/topnav.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4 fw-bold">Keranjang Saya</h2>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 text-muted">Keranjang masih kosong</h4>
                        <a href="catalog.php" class="btn btn-primary mt-3">Mulai Belanja</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th style="width: 150px;">Jumlah</th>
                                    <th>Subtotal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                foreach ($_SESSION['cart'] as $id => $item):
                                    $subtotal = $item['price'] * $item['qty'];
                                    $total += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['photo']) && file_exists($item['photo'])): ?>
                                                    <img src="<?= base_url($item['photo']) ?>"
                                                        style="width: 50px; height: 50px; object-fit: cover;" class="rounded me-3">
                                                <?php else: ?>
                                                    <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                                                        style="width: 50px; height: 50px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($item['category']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Rp
                                            <?= number_format($item['price'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <form action="actions/cart_handler.php" method="POST" class="d-flex">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                                <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1"
                                                    class="form-control form-control-sm text-center"
                                                    onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="fw-bold">Rp
                                            <?= number_format($subtotal, 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <form action="actions/cart_handler.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="confirmFormSubmit(event, this.form, 'Yakin ingin menghapus produk ini dari keranjang?'); return false;"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">Total Belanja:</td>
                                    <td class="text-primary fs-5">Rp
                                        <?= number_format($total, 0, ',', '.') ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="catalog.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Lanjut
                            Belanja</a>
                        <a href="checkout.php" class="btn btn-primary px-4"><i class="bi bi-credit-card me-2"></i> Checkout
                            Sekarang</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/default/footer.php'; ?>