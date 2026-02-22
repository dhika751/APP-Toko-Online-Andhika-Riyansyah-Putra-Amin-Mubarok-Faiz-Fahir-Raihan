<!-- Profile Edit Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                        <div class="modal-header border-0 bg-primary text-white"
                                style="background: var(--primary-gradient) !important;">
                                <h5 class="modal-title fw-bold" id="profileModalLabel">
                                        <i class="bi bi-person-gear me-2"></i> Pengaturan Profil
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                        </div>
                        <form action="<?= base_url('actions/update_profile.php') ?>" method="POST"
                                enctype="multipart/form-data">
                                <div class="modal-body p-4">
                                        <!-- Photo Preview -->
                                        <div class="text-center mb-4">
                                                <div class="position-relative d-inline-block">
                                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'U') ?>&background=435ebe&color=fff"
                                                                id="profilePreview"
                                                                class="rounded-circle border border-3 border-white shadow-sm"
                                                                style="width: 100px; height: 100px; object-fit: cover;">
                                                        <label for="photoInput"
                                                                class="position-absolute bottom-0 end-0 bg-light text-primary rounded-circle shadow-sm border border-2 border-white d-flex align-items-center justify-content-center"
                                                                style="width: 32px; height: 32px; cursor: pointer;">
                                                                <i class="bi bi-camera-fill"
                                                                        style="font-size: 0.9rem;"></i>
                                                        </label>
                                                        <input type="file" name="photo" id="photoInput" class="d-none"
                                                                accept="image/*" onchange="previewImage(this)">
                                                </div>
                                                <p class="text-muted small mt-2 mb-0">Klik ikon kamera untuk ganti foto
                                                </p>
                                        </div>

                                        <div class="mb-3">
                                                <label class="form-label fw-bold text-muted small">Username</label>
                                                <input type="text" name="username" class="form-control"
                                                        value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>"
                                                        required>
                                        </div>

                                        <div class="mb-3">
                                                <label class="form-label fw-bold text-muted small">Password Baru <span
                                                                class="fw-normal text-muted fst-italic">(Opsional)</span></label>
                                                <input type="password" name="password" class="form-control"
                                                        placeholder="Biarkan kosong jika tetap">
                                        </div>
                                </div>
                                <div class="modal-footer border-0 bg-light px-4 py-3">
                                        <button type="button" class="btn btn-light text-muted fw-bold"
                                                data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary fw-bold px-4">Simpan</button>
                                </div>
                        </form>
                </div>
        </div>
</div>

<script>
        function confirmAction(event, href, message) {
                event.preventDefault();

                // Check theme for styling
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

                Swal.fire({
                        title: 'Apakah Anda Yakin?',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#667eea', // Primary color match
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Lanjutkan!',
                        cancelButtonText: 'Batal',
                        background: isDark ? '#2d3748' : '#fff',
                        color: isDark ? '#f7fafc' : '#2d3748'
                }).then((result) => {
                        if (result.isConfirmed) {
                                window.location.href = href;
                        }
                });
                return false;
        }

        function confirmFormSubmit(event, form, message) {
                event.preventDefault();

                // Check theme for styling
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

                Swal.fire({
                        title: 'Apakah Anda Yakin?',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#667eea',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        background: isDark ? '#2d3748' : '#fff',
                        color: isDark ? '#f7fafc' : '#2d3748'
                }).then((result) => {
                        if (result.isConfirmed) {
                                form.submit();
                        }
                });
                return false;
        }

        function previewImage(input) {
                if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                                document.getElementById('profilePreview').src = e.target.result;
                        }
                        reader.readAsDataURL(input.files[0]);
                }
        }
</script>

<div class="watermark-fixed">
        @ 2026 Made By : Group 4
</div>
<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>
<!-- Loading Spinner -->
<div class="spinner-overlay" id="spinnerOverlay">
        <div class="spinner-container">
                <div class="spinner"></div>
                <p>Loading data...</p>
        </div>
</div>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
        document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('sidebar');
                const sidebarToggle = document.getElementById('sidebarToggle');
                const body = document.body;

                if (sidebarToggle) {
                        sidebarToggle.addEventListener('click', function () {
                                if (window.innerWidth >= 992) {
                                        body.classList.toggle('sidebar-collapsed');
                                        // Save preference
                                        const isCollapsed = body.classList.contains('sidebar-collapsed');
                                        localStorage.setItem('sidebar-collapsed', isCollapsed);
                                } else {
                                        sidebar.classList.toggle('active');
                                }
                        });
                }

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function (event) {
                        const isClickInsideSidebar = sidebar.contains(event.target);
                        const isClickInsideToggle = sidebarToggle.contains(event.target);

                        if (!isClickInsideSidebar && !isClickInsideToggle && window.innerWidth < 992 && sidebar.classList.contains('active')) {
                                sidebar.classList.remove('active');
                        }
                });
        });
</script>
<script>
        });
</script>
<script>
        document.addEventListener('DOMContentLoaded', function () {
                // Initialize SweetAlert2 Toast Mixin
                const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                });

                <?php if (isset($_SESSION['toast']) && is_array($_SESSION['toast'])): ?>
                        <?php foreach ($_SESSION['toast'] as $toast): ?>
                                Toast.fire({
                                        icon: '<?= $toast['type'] ?>',
                                        title: '<?= htmlspecialchars($toast['message']) ?>'
                                });
                        <?php endforeach; ?>
                        <?php unset($_SESSION['toast']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['welcome_message'])): ?>
                        Swal.fire({
                                title: 'Selamat Datang!',
                                text: 'Halo <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>, Selamat datang kembali!',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false,
                                width: '600px',
                                padding: '3em',
                                backdrop: `rgba(0,0,0,0.4) left top no-repeat`
                        });
                        <?php unset($_SESSION['welcome_message']); ?>
                <?php endif; ?>
        });
</script>
</body>

</html>