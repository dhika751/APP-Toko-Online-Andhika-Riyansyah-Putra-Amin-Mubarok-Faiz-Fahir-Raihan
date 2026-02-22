<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('users');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    // Basic User Validations
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Semua field harus diisi.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL insert
        $stmt = mysqli_prepare($connection, "INSERT INTO `users` (username, password, role) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);

        try {
            if (mysqli_stmt_execute($stmt)) {
                $success = "Pengguna berhasil ditambahkan.";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Gagal menyimpan: " . mysqli_stmt_error($stmt);
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Duplicate entry
                $error = "Username sudah digunakan.";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>


<h2>Tambah Pengguna</h2>
<?php if ($error): ?>
    <?= showAlert($error, 'danger') ?>
<?php endif; ?>
<?php if ($success): ?>
    <?= showAlert($success, 'success') ?>
    <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
<?php else: ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username*</label>
            <input type="text" name="username" class="form-control"
                value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password*</label>
            <input type="password" name="password" class="form-control" required>
            <div class="form-text">Minimal 6 karakter.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Role*</label>
            <select name="role" class="form-select" required>
                <option value="">-- Pilih Role --</option>
                <?php foreach (getRoleLabels() as $r => $label): ?>
                    <option value="<?= $r ?>" <?= (isset($role) && $role === $r) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
<?php endif; ?>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>