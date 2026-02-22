<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pelanggan');

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

// Check if customer is used in any orders
$sql_check = "SELECT COUNT(*) as count FROM `pesanan` WHERE `pelanggan_id` = ?";
$stmt_check = mysqli_prepare($connection, $sql_check);
mysqli_stmt_bind_param($stmt_check, 'i', $id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$check_data = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

if ($check_data['count'] > 0) {
    echo "<script>
        alert('Pelanggan tidak dapat dihapus karena memiliki riwayat pesanan!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

$sql = "DELETE FROM `pelanggan` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    redirect('index.php');
} else {
    echo "<script>
        alert('Gagal menghapus pelanggan!');
        window.location.href = 'index.php';
    </script>";
    mysqli_stmt_close($stmt);
}
?>