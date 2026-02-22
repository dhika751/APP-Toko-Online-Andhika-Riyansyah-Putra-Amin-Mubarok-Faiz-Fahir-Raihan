<?php
require_once '../config/database.php';
require_once '../lib/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

$userId = $_SESSION['user_id'];
$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../index.php';

// Prepare Success/Error messages for Toasts (using query params for simplicity or session)
$_SESSION['toast'] = [];

// 1. Update Photo if provided
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileInfo = pathinfo($_FILES['photo']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($ext, $allowed)) {
        $newFilename = 'user_' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $newFilename;
        $dbPath = 'assets/uploads/profiles/' . $newFilename; // Path for DB

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $stmt = mysqli_prepare($connection, "UPDATE users SET photo = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $dbPath, $userId);
            mysqli_stmt_execute($stmt);
            $_SESSION['photo'] = $dbPath; // Update Session
            $_SESSION['toast'][] = ['type' => 'success', 'message' => 'Foto profil berhasil diupdate!'];
        }
    } else {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Format file tidak didukung!'];
    }
}

// 2. Update Username
if (!empty($username) && $username !== $_SESSION['username']) {
    // Check duplication
    $stmt = mysqli_prepare($connection, "SELECT id FROM users WHERE username = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, "si", $username, $userId);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_fetch($stmt)) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Username sudah dipakai!'];
    } else {
        mysqli_stmt_close($stmt); // Close previous stmt
        $stmt = mysqli_prepare($connection, "UPDATE users SET username = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $username, $userId);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['username'] = $username;
            $_SESSION['toast'][] = ['type' => 'success', 'message' => 'Username berhasil diganti!'];
        }
    }
}

// 3. Update Password
if (!empty($password)) {
    if (strlen($password) < 6) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Password minimal 6 karakter!'];
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($connection, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $userId);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['toast'][] = ['type' => 'success', 'message' => 'Password berhasil diubah!'];
        }
    }
}

header("Location: " . $redirectUrl);
exit;
?>