<?php
require_once 'lib/auth.php';
require_once 'lib/functions.php';

requireAuth();

$userId = $_SESSION['user_id'];
$success = $error = '';

// Get current user data
$stmt = mysqli_prepare($connection, "SELECT username, photo FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$currentUser = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Handle Photo Upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                // Update DB
                $stmt = mysqli_prepare($connection, "UPDATE users SET photo = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $destPath, $userId);
                mysqli_stmt_execute($stmt);
                $currentUser['photo'] = $destPath; // Update local var for display
                $success .= "Foto profil berhasil diperbarui. ";
            } else {
                $error .= "Gagal mengupload foto. ";
            }
        } else {
            $error .= "Format file tidak didukung (hanya JPG, PNG, GIF). ";
        }
    }

    // Handle Username Update
    if (!empty($username) && $username !== $currentUser['username']) {
        // Check availability
        $stmt = mysqli_prepare($connection, "SELECT id FROM users WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, "si", $username, $userId);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_fetch($stmt)) {
            $error .= "Username sudah digunakan user lain. ";
        } else {
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($connection, "UPDATE users SET username = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $username, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['username'] = $username;
                $currentUser['username'] = $username;
                $success .= "Username berhasil diperbarui. ";
            }
        }
    }

    // Handle Password Update
    if (!empty($password)) {
        if (strlen($password) < 6) { // Simple check
             $error .= "Password minimal 6 karakter. ";
        } else {
            $newJwt = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($connection, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newJwt, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $success .= "Password berhasil diubah. ";
            }
        }
    }
}

require_once 'views/default/header.php';
require_once 'views/default/sidebar.php';
require_once 'views/default/topnav.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4 fw-bold">Pengaturan Profil</h2>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <?php 
                        $photoPath = !empty($currentUser['photo']) && file_exists($currentUser['photo']) 
                            ? $currentUser['photo'] 
                            : "https://ui-avatars.com/api/?name=".urlencode($currentUser['username'])."&background=435ebe&color=fff&size=150";
                        ?>
                        <img src="<?= $photoPath ?>" alt="Profile" class="rounded-circle mb-3 shadow" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($currentUser['username']) ?></h4>
                        <p class="text-muted"><?= ucfirst($_SESSION['role']) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-primary">Edit Informasi</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted">Ganti Foto Profil</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <div class="form-text">Format: JPG, JPEG, PNG. Maksimal 2MB.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted">Password Baru (Opsional)</label>
                                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/default/footer.php'; ?>
