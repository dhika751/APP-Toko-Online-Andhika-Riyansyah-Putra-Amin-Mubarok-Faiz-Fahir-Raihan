<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pesanan');

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

// Get pesanan data
$sql = "SELECT * FROM `pesanan` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    redirect('index.php');
}

// Get detail pesanan to restore stock
$sql_detail = "SELECT * FROM `detail_pesanan` WHERE `pesanan_id` = ?";
$stmt_detail = mysqli_prepare($connection, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, 'i', $id);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

// Begin transaction
mysqli_begin_transaction($connection);

try {
    // Restore stock for each product
    while ($detail = mysqli_fetch_assoc($result_detail)) {
        if ($detail['produk_id']) {
            updateProductStock($detail['produk_id'], $detail['jumlah'], 'masuk');
        }
    }
    mysqli_stmt_close($stmt_detail);

    // Delete pesanan (detail will be deleted automatically due to CASCADE)
    $sql_delete = "DELETE FROM `pesanan` WHERE `id` = ?";
    $stmt_delete = mysqli_prepare($connection, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, 'i', $id);

    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Gagal menghapus pesanan!");
    }

    mysqli_stmt_close($stmt_delete);

    // Commit transaction
    mysqli_commit($connection);

    redirect('index.php');

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($connection);

    echo "<script>
        alert('Gagal menghapus pesanan: " . addslashes($e->getMessage()) . "');
        window.location.href = 'index.php';
    </script>";
}
?>