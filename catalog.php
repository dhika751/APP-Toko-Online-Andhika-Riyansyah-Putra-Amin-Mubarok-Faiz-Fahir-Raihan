<?php
require_once 'lib/auth.php';
requireAuth();
require_once 'views/default/header.php';
require_once 'views/default/sidebar.php';
require_once 'views/default/topnav.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Katalog Produk</h2>
            <form action="" method="GET" class="d-flex" style="max-width: 400px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama produk..."
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if (isset($_GET['search'])): ?>
                        <a href="catalog.php" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="row">
            <!-- Example Product - In real app, loop from DB -->
            <?php
            // Fetch products
            $search_sql = "";
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = mysqli_real_escape_string($connection, $_GET['search']);
                $search_sql = " WHERE p.nama_produk LIKE '%$search%' OR k.nama_kategori LIKE '%$search%' ";
            }

            $query = "SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.kategori_id = k.id" . $search_sql;
            $result = mysqli_query($connection, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 product-card border-0 shadow-sm"
                            style="overflow: hidden; transition: transform 0.2s;">
                            <div
                                style="height: 200px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; position: relative;">
                                <?php if (!empty($row['photo']) && file_exists('uploads/produk/' . $row['photo'])): ?>
                                    <img src="<?= base_url('uploads/produk/' . $row['photo']) ?>"
                                        style="width:100%; height:100%; object-fit: cover; filter: <?= $row['stok'] <= 0 ? 'grayscale(100%)' : 'none' ?>;">
                                <?php else: ?>
                                    <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                <?php endif; ?>

                                <?php if ($row['stok'] <= 0): ?>
                                    <div
                                        class="position-absolute w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center">
                                        <span
                                            class="badge bg-danger fs-5 px-3 py-2 text-uppercase fw-bold border border-white">Habis
                                            / Sold</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <small class="text-muted fw-bold text-uppercase">
                                    <?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?>
                                </small>
                                <h5 class="fw-bold mt-1 mb-2">
                                    <?= htmlspecialchars($row['nama_produk']) ?>
                                </h5>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-primary fw-bold fs-5 mb-0">Rp
                                        <?= number_format($row['harga'], 0, ',', '.') ?>
                                    </p>
                                    <small class="text-muted">Stok: <?= $row['stok'] ?></small>
                                </div>

                                <form action="actions/cart_handler.php" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                    <?php if ($row['stok'] > 0): ?>
                                        <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                            <i class="bi bi-cart-plus me-1"></i> Beli
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary w-100 rounded-pill" disabled>
                                            <i class="bi bi-x-circle me-1"></i> Stok Habis
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12 text-center text-muted py-5">Belum ada produk.</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once 'views/default/footer.php'; ?>