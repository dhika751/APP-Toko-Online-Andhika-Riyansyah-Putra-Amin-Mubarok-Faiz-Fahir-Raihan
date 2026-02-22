<?php

require_once 'lib/auth.php';
require_once 'lib/functions.php';

if (isset($_SESSION['user_id']) && empty($_SESSION['role'])) {
    session_destroy();
}

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole($_SESSION['role']);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request. CSRF token mismatch.');
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        $role = login($username, $password);
        if ($role) {
            $_SESSION['welcome_message'] = true;
            redirectBasedOnRole($role);
        } else {
            $error = "Username atau password salah.";
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
    <title>Login - Toko Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Background Image Configuration */
            background-image: url('assets/default/images/image.jpg?v=<?= time() ?>');
            /* Updated to match your file */
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

        /* Overlay to ensure text readability if needed, though card is white */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            /* Dark overlay */
            z-index: -1;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            /* Almost invisible */
            backdrop-filter: blur(0px);
            /* Very subtle mist */
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            border: 2px solid rgba(255, 255, 255, 0.6);
            /* Clearer border */
            text-align: center;
        }

        .auth-logo {
            font-size: 2.2rem;
            /* Slightly larger */
            font-weight: 900;
            /* Extra Bold */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            display: inline-block;
            filter: drop-shadow(0 2px 2px rgba(255, 255, 255, 0.5));
            /* Crisp shadow */
            letter-spacing: -0.5px;
        }

        .auth-subtitle {
            color: #1a202c;
            /* Deep black */
            margin-bottom: 2rem;
            font-size: 1rem;
            font-weight: 700;
            /* Bold */
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
            /* White outline effect for readability */
            letter-spacing: 0.2px;
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
            text-align: left;
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

        .alert {
            text-align: left;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <div class="auth-card">
        <div class="auth-logo">Toko Online</div>
        <div class="auth-subtitle">Masuk untuk mengelola toko Anda</div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="text-start">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

            <div class="mb-3">
                <label class="form-label fw-bold text-dark">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-dark">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>

            <div class="auth-link">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>