<?php
require_once 'lib/auth.php';
require_once 'lib/functions.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request. CSRF token mismatch.');
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // Get role from user selection (pelanggan or supplier)
    $role = $_POST['role'] ?? 'pelanggan';
    // Ensure role is either pelanggan or supplier
    $role = in_array($role, ['pelanggan', 'supplier']) ? $role : 'pelanggan';

    if (empty($username) || empty($password)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        // Validate password strength
        $passwordErrors = validatePassword($password, false);
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } else {
            $registrationResult = registerUser($username, $password, $role);
            if ($registrationResult === true) {
                $roleLabel = ($role === 'supplier') ? 'Supplier' : 'Pelanggan';
                $success = "Registrasi berhasil sebagai {$roleLabel}! Silakan login.";
            } else {
                $error = is_string($registrationResult) ? $registrationResult : "Username sudah digunakan atau registrasi gagal.";
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Toko Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Background Image Configuration */
            background-image: url('assets/default/images/image.jpg?v=<?= time() ?>');
            /* Using same image as login */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            /* Almost invisible */
            backdrop-filter: blur(3px);
            /* Very subtle mist */
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.6);
            /* Clearer border */
        }

        .auth-title {
            font-weight: 900;
            /* Extra Bold */
            color: #1a202c;
            /* Deep black */
            margin-bottom: 1.5rem;
            text-align: center;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
            /* White outline effect */
            font-size: 1.8rem;
            letter-spacing: -0.5px;
        }

        .form-label {
            color: #000 !important;
            font-size: 0.95rem;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
        }

        .auth-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .auth-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="auth-card">
        <h3 class="auth-title">Daftar Akun Baru</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Daftar Sebagai</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="role_pelanggan" value="pelanggan"
                            checked>
                        <label class="form-check-label fw-bold text-dark" for="role_pelanggan"
                            style="text-shadow: 0 1px 0 rgba(255,255,255,0.8);">
                            👤 Pelanggan
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" id="role_supplier" value="supplier">
                        <label class="form-check-label fw-bold text-dark" for="role_supplier"
                            style="text-shadow: 0 1px 0 rgba(255,255,255,0.8);">
                            🏭 Supplier
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Pilih username" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-dark">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Buat password kuat" required>
                <div class="form-text mt-1 text-dark fw-bold" style="text-shadow: 0 1px 0 rgba(255,255,255,0.8);">
                    Minimal 8 karakter, huruf besar, kecil, & angka.
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Daftar Sekarang</button>

            <div class="auth-link">
                Sudah punya akun? <a href="login.php">Login disini</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>