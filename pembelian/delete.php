<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

// Only allow admin for deletion with stock rollback
if (!userCanAccess(['admin'])) {
    redirect('index.php');
}

require_once '../config/database.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // 1. Get purchase status and details for Rollback
    $sql_check = "SELECT status FROM `pembelian` WHERE `id` = ?";
    $stmt = mysqli_prepare($connection, $sql_check);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $check_res = mysqli_stmt_get_result($stmt);
    $pembelian = mysqli_fetch_assoc($check_res);
    mysqli_stmt_close($stmt);

    if ($pembelian) {
        // Begin Transaction
        mysqli_begin_transaction($connection);

        try {
            // Rollback stock ONLY if status was 'selesai' (meaning stock was added)
            // If pending, stock was never added, so no need to rollback.
            if ($pembelian['status'] === 'selesai') {
                $sql_detail = "SELECT produk_id, jumlah FROM `detail_pembelian` WHERE `pembelian_id` = ?";
                $stmt_detail = mysqli_prepare($connection, $sql_detail);
                mysqli_stmt_bind_param($stmt_detail, 'i', $id);
                mysqli_stmt_execute($stmt_detail);
                $res_detail = mysqli_stmt_get_result($stmt_detail);

                while ($row = mysqli_fetch_assoc($res_detail)) {
                    // Reduce stock (Rollback 'masuk')
                    // 'keluar' here means we are reversing a purchase, effectively reducing what we added
                    // updateProductStock(id, qty, 'keluar') -> reduces stock
                    updateProductStock($row['produk_id'], $row['jumlah'], 'keluar');
                }
                mysqli_stmt_close($stmt_detail);
            }

            // Delete master record (details deleted via CASCADE FK)
            $sql_del = "DELETE FROM `pembelian` WHERE `id` = ?";
            $stmt_del = mysqli_prepare($connection, $sql_del);
            mysqli_stmt_bind_param($stmt_del, 'i', $id);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);

            mysqli_commit($connection);
            echo "<script>alert('Pembelian berhasil dihapus dan stok dikembalikan (jika sudah masuk).'); window.location.href='index.php';</script>";

        } catch (Exception $e) {
            mysqli_rollback($connection);
            echo "<script>alert('Gagal menghapus: " . $e->getMessage() . "'); window.location.href='index.php';</script>";
        }

    } else {
        redirect('index.php');
    }
} else {
    redirect('index.php');
}
?>