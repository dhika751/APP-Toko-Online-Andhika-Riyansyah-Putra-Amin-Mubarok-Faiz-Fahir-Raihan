<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
// Allow both admin and supplier
requireRoleAccess(['admin', 'supplier']);

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

// Get product data
$sql = "SELECT * FROM `produk` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    redirect('index.php');
}

// Access Control for Supplier
if ($_SESSION['role'] === 'supplier') {
    $user_id = $_SESSION['user_id'];
    $sql_sup = "SELECT id FROM supplier WHERE users_id = ?";
    $stmt_sup = mysqli_prepare($connection, $sql_sup);
    mysqli_stmt_bind_param($stmt_sup, 'i', $user_id);
    mysqli_stmt_execute($stmt_sup);
    $res_sup = mysqli_stmt_get_result($stmt_sup);
    $sup = mysqli_fetch_assoc($res_sup);

    // Check if product belongs to this supplier
    if (!$sup || $data['supplier_id'] != $sup['id']) {
        echo "<script>alert('Akses Ditolak: Produk ini bukan milik anda.'); window.location='index.php';</script>";
        exit;
    }
}

// Check if product is used in order details
$sql_check = "SELECT COUNT(*) as count FROM `detail_pesanan` WHERE `produk_id` = ?";
$stmt_check = mysqli_prepare($connection, $sql_check);
mysqli_stmt_bind_param($stmt_check, 'i', $id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$check_data = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

if ($check_data['count'] > 0) {
    echo "<script>
        alert('Produk tidak dapat dihapus karena sudah ada dalam pesanan!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Delete product
$sql = "DELETE FROM `produk` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Delete photo file if exists
    if ($data['photo'] && file_exists(__DIR__ . '/../uploads/produk/' . $data['photo'])) {
        unlink(__DIR__ . '/../uploads/produk/' . $data['photo']);
    }

    redirect('index.php');
} else {
    echo "<script>
        alert('Gagal menghapus produk!');
        window.location.href = 'index.php';
    </script>";
    mysqli_stmt_close($stmt);
}
?>