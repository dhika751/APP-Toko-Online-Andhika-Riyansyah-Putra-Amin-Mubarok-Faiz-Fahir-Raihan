<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('supplier');

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

// Get supplier data including users_id
$sql_get = "SELECT users_id FROM `supplier` WHERE `id` = ?";
$stmt_get = mysqli_prepare($connection, $sql_get);
mysqli_stmt_bind_param($stmt_get, 'i', $id);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$supplier_data = mysqli_fetch_assoc($result_get);
mysqli_stmt_close($stmt_get);

if (!$supplier_data) {
    echo "<script>
        alert('Supplier tidak ditemukan!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Check if supplier is used by any products
$sql_check = "SELECT COUNT(*) as count FROM `produk` WHERE `supplier_id` = ?";
$stmt_check = mysqli_prepare($connection, $sql_check);
mysqli_stmt_bind_param($stmt_check, 'i', $id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$check_data = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

if ($check_data['count'] > 0) {
    echo "<script>
        alert('Supplier tidak dapat dihapus karena masih digunakan oleh produk!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Begin transaction to delete both supplier and user account
mysqli_begin_transaction($connection);

try {
    // 1. Delete supplier record
    $sql_supplier = "DELETE FROM `supplier` WHERE `id` = ?";
    $stmt_supplier = mysqli_prepare($connection, $sql_supplier);
    mysqli_stmt_bind_param($stmt_supplier, 'i', $id);

    if (!mysqli_stmt_execute($stmt_supplier)) {
        throw new Exception("Gagal menghapus supplier!");
    }
    mysqli_stmt_close($stmt_supplier);

    // 2. Delete user account if exists
    if ($supplier_data['users_id'] !== null) {
        $sql_user = "DELETE FROM `users` WHERE `id` = ?";
        $stmt_user = mysqli_prepare($connection, $sql_user);
        mysqli_stmt_bind_param($stmt_user, 'i', $supplier_data['users_id']);

        if (!mysqli_stmt_execute($stmt_user)) {
            throw new Exception("Gagal menghapus akun user!");
        }
        mysqli_stmt_close($stmt_user);
    }

    // Commit transaction
    mysqli_commit($connection);
    redirect('index.php');

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($connection);
    echo "<script>
        alert('" . $e->getMessage() . "');
        window.location.href = 'index.php';
    </script>";
}
?>