<header class="top-navbar">
    <div class="navbar-left">
        <h1 class="page-title">
            <?php
            // Determine page title based on URL roughly
            $path = $_SERVER['PHP_SELF'];
            if (strpos($path, 'index.php') !== false && !strpos($path, '/', 1)) {
                echo 'Dashboard';
            } elseif (strpos($path, 'kategori') !== false) {
                echo 'Kategori Produk';
            } elseif (strpos($path, 'produk') !== false) {
                echo 'Produk';
            } elseif (strpos($path, 'supplier') !== false) {
                echo 'Supplier';
            } elseif (strpos($path, 'pelanggan') !== false) {
                echo 'Pelanggan';
            } elseif (strpos($path, 'pesanan') !== false) {
                echo 'Pesanan';
            } elseif (strpos($path, 'pembayaran') !== false) {
                echo 'Pembayaran';
            } elseif (strpos($path, 'stok') !== false) {
                echo 'Stok & Inventori';
            } elseif (strpos($path, 'laporan') !== false) {
                echo 'Laporan';
            } else {
                echo 'Toko Online';
            }
            ?>
        </h1>
    </div>
    <div class="navbar-right d-flex align-items-center">
        <button class="theme-toggle btn btn-link text-dark p-0 me-3" id="themeToggle" title="Ganti Mode (Gelap/Terang)">
            <i class="bi bi-moon-fill" id="themeIcon" style="font-size: 1.2rem;"></i>
        </button>

        <div class="d-flex align-items-center me-3">
            <?php
            $cartCount = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['qty'];
                }
            }
            ?>
            <a href="<?= base_url('cart.php') ?>" class="btn btn-link text-dark p-0 position-relative me-3"
                style="font-size: 1.2rem;">
                <i class="bi bi-cart3"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                        style="font-size: 0.6rem;">
                        <?= $cartCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="notification-wrapper position-relative">
                <?php
                $notifs = get_notifications($_SESSION['user_id'], $_SESSION['role'] ?? 'user');
                $hasNotifs = count($notifs) > 0;
                ?>
                <button class="notification-trigger btn btn-link text-dark p-0" id="notificationTrigger"
                    style="font-size: 1.2rem;">
                    <i class="bi bi-bell<?= $hasNotifs ? '-fill' : '' ?>"></i>
                    <?php if ($hasNotifs): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.6rem;">
                            <?= count($notifs) ?>
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    <?php endif; ?>
                </button>

                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="card border-0">
                        <div class="card-header bg-primary text-white py-3">
                            <h6 class="mb-0 text-white fw-bold"><i class="bi bi-bell me-2"></i>Notifikasi</h6>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                            <?php if ($hasNotifs): ?>
                                <?php foreach ($notifs as $notif): ?>
                                    <a href="<?= $notif['link'] ?>"
                                        class="list-group-item list-group-item-action d-flex align-items-start p-3 border-bottom">
                                        <div class="me-3 mt-1">
                                            <div class="bg-<?= $notif['type'] ?> bg-opacity-10 text-<?= $notif['type'] ?> rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 40px; height: 40px;">
                                                <i class="bi <?= $notif['icon'] ?> fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 text-wrap" style="line-height: 1.4; font-size: 0.9rem;">
                                                <?= htmlspecialchars($notif['message']) ?>
                                            </p>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i><?= $notif['time'] ?>
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-5 text-center text-muted">
                                    <div class="mb-3">
                                        <i class="bi bi-bell-slash text-light bg-secondary bg-opacity-25 rounded-circle p-3"
                                            style="font-size: 2rem;"></i>
                                    </div>
                                    <p class="mb-0 fw-bold">Tidak ada notifikasi baru</p>
                                    <small>Semua notifikasi akan muncul di sini</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasNotifs && ($_SESSION['role'] ?? 'user') === 'admin'): ?>
                            <div class="card-footer bg-light text-center py-2">
                                <a href="<?= base_url('pesanan/index.php') ?>" class="text-decoration-none small fw-bold">Lihat
                                    Semua Pesanan</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-menu-wrapper" style="position: relative;">
            <button class="user-menu-trigger" id="userMenuTrigger">
                <?php
                // Calculate absolute path for file check
                $img_path = $_SESSION['photo'] ?? '';
                $abs_path = __DIR__ . '/../../' . $img_path; // views/default/../../ -> root/
                
                $photo = !empty($img_path) && file_exists($abs_path)
                    ? base_url($img_path)
                    : "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username'] ?? 'User') . "&background=435ebe&color=fff";
                ?>
                <img src="<?= $photo ?>" alt="User" class="user-avatar">
            </button>

            <div class="user-dropdown-menu" id="userDropdown">
                <div class="user-dropdown-header">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                    <div class="user-role"><?= ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Admin')) ?></div>
                </div>
                <div class="dropdown-content">
                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="bi bi-person"></i> Profil Saya
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class="bi bi-gear"></i> Pengaturan
                    </a>
                    <div class="sidebar-divider" style="margin: 0.5rem 0;"></div>
                    <a href="<?= base_url('logout.php') ?>" class="dropdown-item text-danger"
                        onclick="confirmAction(event, this.href, 'Apakah Anda yakin ingin keluar?'); return false;">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // JS Logic for Topnav interactions
    document.addEventListener('DOMContentLoaded', function () {
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        // Load saved icon State
        const currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
        }

        // Theme Toggle
        themeToggle.addEventListener('click', function () {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Toggle Icon
            if (newTheme === 'dark') {
                themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
            } else {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
            }
        });

        // User Dropdown
        const userTrigger = document.getElementById('userMenuTrigger');
        const userDropdown = document.getElementById('userDropdown');

        userTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            // Close other dropdowns
            notificationDropdown.classList.remove('show');
        });

        // Notification Dropdown
        const notificationTrigger = document.getElementById('notificationTrigger');
        const notificationDropdown = document.getElementById('notificationDropdown');

        if (notificationTrigger) {
            notificationTrigger.addEventListener('click', function (e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                // Close other dropdowns
                userDropdown.classList.remove('show');
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target) && !userTrigger.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
            if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationTrigger.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    });
</script>