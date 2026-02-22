<aside class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <a href="#" class="sidebar-logo">
                <i class="bi bi-shop"></i>
                <span>Toko Online</span>
            </a>
        </div>

        <?php
        $current_page = $_SERVER['PHP_SELF'];
        function isActive($path)
        {
            global $current_page;
            // Check if the current page path contains the segment surrounded by slashes
            // This prevents 'produk' matching 'kategori_produk'
            if ($path === 'index.php') {
                // For dashboard, ensure we are at root and index.php
                // Check if NOT in any of the known modules
                $modules = ['kategori', 'produk', 'pesanan', 'laporan', 'stok', 'pembayaran', 'pelanggan', 'supplier'];
                foreach ($modules as $module) {
                    if (strpos($current_page, $module) !== false)
                        return '';
                }
                return basename($current_page) === 'index.php' ? 'active' : '';
            }
            return strpos($current_page, "/$path/") !== false ? 'active' : '';
        }
        ?>

        <nav class="sidebar-menu">
            <div class="sidebar-item">
                <a href="<?= base_url('index.php') ?>" class="sidebar-link <?= isActive('index.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="sidebar-header-sm">Master Data</div>

                <div class="sidebar-item">
                    <a href="<?= base_url('kategori_produk/index.php') ?>"
                        class="sidebar-link <?= isActive('kategori_produk') ?>">
                        <i class="bi bi-tags"></i>
                        <span>Kategori</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="<?= base_url('users/index.php') ?>" class="sidebar-link <?= isActive('users') ?>">
                        <i class="bi bi-person-badge"></i>
                        <span>Pengguna</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="<?= base_url('produk/index.php') ?>" class="sidebar-link <?= isActive('produk') ?>">
                        <i class="bi bi-box-seam"></i>
                        <span>Produk</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="<?= base_url('supplier/index.php') ?>" class="sidebar-link <?= isActive('supplier') ?>">
                        <i class="bi bi-truck"></i>
                        <span>Supplier</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="<?= base_url('pelanggan/index.php') ?>" class="sidebar-link <?= isActive('pelanggan') ?>">
                        <i class="bi bi-people"></i>
                        <span>Pelanggan</span>
                    </a>
                </div>

                <div class="sidebar-divider"></div>
                <div class="sidebar-header-sm">Transaksi</div>

                <div class="sidebar-item">
                    <a href="<?= base_url('pesanan/index.php') ?>" class="sidebar-link <?= isActive('pesanan') ?>">
                        <i class="bi bi-cart"></i>
                        <span>Pesanan</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="<?= base_url('stok/index.php') ?>" class="sidebar-link <?= isActive('stok') ?>">
                        <i class="bi bi-boxes"></i>
                        <span>Stok & Inventori</span>
                    </a>
                </div>

                <div class="sidebar-divider"></div>
                <div class="sidebar-header-sm">Laporan</div>

                <div class="sidebar-item">
                    <a href="<?= base_url('laporan/penjualan.php') ?>" class="sidebar-link <?= isActive('laporan') ?>">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Laporan Penjualan</span>
                    </a>
                </div>
        </div>
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'supplier'): ?>
        <div class="sidebar-header-sm">Supplier Area</div>

        <div class="sidebar-item">
            <a href="<?= base_url('supplier/riwayat_transaksi.php') ?>" class="sidebar-link <?= isActive('supplier') ?>">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Penjualan</span>
            </a>
        </div>
        <div class="sidebar-item">
            <a href="<?= base_url('produk/index.php') ?>" class="sidebar-link <?= isActive('produk') ?>">
                <i class="bi bi-box-seam"></i>
                <span>Kelola Produk</span>
            </a>
        </div>
    <?php else: // Menu for Pelanggan ?>
        <div class="sidebar-header-sm">Belanja</div>

        <div class="sidebar-item">
            <a href="<?= base_url('catalog.php') ?>"
                class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'catalog.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-shop-window"></i>
                <span>Katalog Produk</span>
            </a>
        </div>
        <div class="sidebar-item">
            <a href="<?= base_url('cart.php') ?>"
                class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'cart.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-cart3"></i>
                <span>Keranjang Saya</span>
            </a>
        </div>
        <div class="sidebar-item">
            <a href="<?= base_url('my_orders.php') ?>"
                class="sidebar-link <?= strpos($_SERVER['PHP_SELF'], 'my_orders.php') !== false ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i>
                <span>Pesanan Saya</span>
            </a>
        </div>
    <?php endif; ?>
    </nav>
    </div>

    <!-- Floating Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-chevron-left"></i>
    </button>
</aside>