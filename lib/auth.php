<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Secure session settings - Standardized across the app
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Change to 1 if using HTTPS
    ini_set('session.cookie_path', '/');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

define('BASE_URL', $BASE_PATH);
/**
 * Authenticates user and sets session data.
 * Assumes session_start() was called BEFORE this function.
 */
function login($username, $password)
{
    global $connection;
    $username = sanitize($username);
    $sql = "SELECT id, username, password, role FROM users WHERE username=?";
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        die("Query Error: " . mysqli_error($connection));
    }
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    if ($user && password_verify($password, $user['password'])) {
        // DO NOT call session_start() here!
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['photo'] = null; // Photo column does not exist
        mysqli_stmt_close($stmt);
        return $user['role'];
    }
    mysqli_stmt_close($stmt);
    return false;
}
function registerUser($username, $password, $role)
{
    global $connection;
    // Get allowed roles from menu.json
    $menuConfig = loadMenuConfig();
    $allowedRoles = array_keys($menuConfig['roles'] ?? []);
    $username = sanitize($username);
    $role = in_array($role, $allowedRoles) ? $role : 'pelanggan'; // Default to pelanggan
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);

    // Start Transaction
    mysqli_begin_transaction($connection);

    try {
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $sql);
        if (!$stmt) {
            throw new Exception('Prepare users failed: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($stmt, "sss", $username, $hashedPass, $role);
        $result = mysqli_stmt_execute($stmt);

        if ($result && $role === 'pelanggan') {
            $user_id = mysqli_insert_id($connection);
            // Generate Kode Pelanggan
            $kode = 'PEL-' . date('YmdHis') . '-' . rand(100, 999);
            // Use username as default name if not provided
            $nama = $username;

            $sql_pelanggan = "INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, users_id) VALUES (?, ?, ?)";
            $stmt_pelanggan = mysqli_prepare($connection, $sql_pelanggan);
            if (!$stmt_pelanggan) {
                throw new Exception('Prepare pelanggan failed: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmt_pelanggan, "ssi", $kode, $nama, $user_id);
            mysqli_stmt_execute($stmt_pelanggan);
            mysqli_stmt_close($stmt_pelanggan);
        } elseif ($result && $role === 'supplier') {
            $user_id = mysqli_insert_id($connection);
            // Generate Kode Supplier
            $kode = 'SUP-' . date('YmdHis') . '-' . rand(100, 999);
            // Use username as default name if not provided
            $nama = $username;

            $sql_supplier = "INSERT INTO supplier (kode_supplier, nama_supplier, users_id) VALUES (?, ?, ?)";
            $stmt_supplier = mysqli_prepare($connection, $sql_supplier);
            if (!$stmt_supplier) {
                throw new Exception('Prepare supplier failed: ' . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmt_supplier, "ssi", $kode, $nama, $user_id);
            mysqli_stmt_execute($stmt_supplier);
            mysqli_stmt_close($stmt_supplier);
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($connection);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($connection);
        error_log('[registerUser] ' . $e->getMessage());
        return $e->getMessage(); // kembalikan pesan error agar bisa ditampilkan
    }
}
function requireAuth()
{
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}
function redirectBasedOnRole($role)
{
    $menuConfig = loadMenuConfig();
    if (isset($menuConfig['roles'][$role])) {
        $dashboard = $menuConfig['roles'][$role]['dashboard'] ?? 'login.php';
        header('Location: ' . $dashboard);
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}
?>