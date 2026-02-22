<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('users');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect('index.php');
}

$stmt = mysqli_prepare($connection, "SELECT * FROM `users` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    redirect('index.php');
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if (empty($username) || empty($role)) {
        $error = "Username dan Role wajib diisi.";
    } else {

        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = "Password baru minimal 6 karakter.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE `users` SET username = ?, password = ?, role = ? WHERE id = ?";
                $stmt_update = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt_update, "sssi", $username, $hashed_password, $role, $id);
            }
        } else {
            // Update without password
            $sql = "UPDATE `users` SET username = ?, role = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt_update, "ssi", $username, $role, $id);
        }

        if (!$error) {
            try {
                if (mysqli_stmt_execute($stmt_update)) {
                    $success = "Pengguna berhasil diperbarui.";
                    // Refresh data
                    $data['username'] = $username;
                    $data['role'] = $role;
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 2000);
                    </script>";
                } else {
                    $error = "Gagal menyimpan: " . mysqli_stmt_error($stmt_update);
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $error = "Username sudah digunakan.";
                } else {
                    $error = "Terjadi kesalahan database: " . $e->getMessage();
                }
            }
            mysqli_stmt_close($stmt_update);
        }
    }
}
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>


<h2>Edit Pengguna</h2>
<?php if ($error): ?>
    <?= showAlert($error, 'danger') ?>
<?php endif; ?>
<?php if ($success): ?>
    <?= showAlert($success, 'success') ?>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label class="form-label">Username*</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']) ?>"
            required>
    </div>
    <div class="mb-3">
        <label class="form-label">Password Baru <small>(Biarkan kosong jika tidak ingin mengganti)</small></label>
        <input type="password" name="password" class="form-control">
        <div class="form-text">Minimal 6 karakter.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Role*</label>
        <select name="role" class="form-select" required>
            <option value="">-- Pilih Role --</option>
            <?php foreach (getRoleLabels() as $r => $label): ?>
                <option value="<?= $r ?>" <?= ($data['role'] === $r) ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="index.php" class="btn btn-secondary">Batal</a>
</form>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>