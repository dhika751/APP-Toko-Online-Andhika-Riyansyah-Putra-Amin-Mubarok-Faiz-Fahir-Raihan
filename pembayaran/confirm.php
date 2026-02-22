<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembayaran');

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['confirm', 'reject'])) {
    redirect('index.php');
}

$status = ($action == 'confirm') ? 'dikonfirmasi' : 'ditolak';

// Begin transaction
mysqli_begin_transaction($connection);

try {
    // 1. Update payment status
    $sql = "UPDATE `pembayaran` SET `status` = ? WHERE `id` = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal update status pembayaran.");
    }
    mysqli_stmt_close($stmt);

    // 2. Update order status if confirmed
    if ($action == 'confirm') {
        // Get pesanan_id
        $sql_get = "SELECT `pesanan_id` FROM `pembayaran` WHERE `id` = ?";
        $stmt_get = mysqli_prepare($connection, $sql_get);
        mysqli_stmt_bind_param($stmt_get, 'i', $id);
        mysqli_stmt_execute($stmt_get);
        $result = mysqli_stmt_get_result($stmt_get);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_get);

        if ($row) {
            $pesanan_id = $row['pesanan_id'];
            $sql_order = "UPDATE `pesanan` SET `status` = 'diproses' WHERE `id` = ?";
            $stmt_order = mysqli_prepare($connection, $sql_order);
            mysqli_stmt_bind_param($stmt_order, 'i', $pesanan_id);
            mysqli_stmt_execute($stmt_order);
            mysqli_stmt_close($stmt_order);

            // --- AUTO-GENERATE SUPPLIER TRANSACTIONS ---
            // 1. Get all items in this order grouped by supplier
            $sql_items = "SELECT p.supplier_id, SUM(dp.subtotal) as total_per_supplier 
                          FROM `detail_pesanan` dp
                          JOIN `produk` p ON dp.produk_id = p.id
                          WHERE dp.pesanan_id = ? AND p.supplier_id IS NOT NULL
                          GROUP BY p.supplier_id";

            $stmt_items = mysqli_prepare($connection, $sql_items);
            mysqli_stmt_bind_param($stmt_items, 'i', $pesanan_id);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            // 2. Insert into transaksi_supplier
            while ($item = mysqli_fetch_assoc($result_items)) {
                $supplier_id = $item['supplier_id'];
                $total = $item['total_per_supplier'];

                // Check if already exists to prevent duplicate (idempotency)
                $check_sql = "SELECT id FROM transaksi_supplier WHERE pesanan_id = ? AND supplier_id = ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, 'ii', $pesanan_id, $supplier_id);
                mysqli_stmt_execute($check_stmt);

                if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) == 0) {
                    $insert_sql = "INSERT INTO transaksi_supplier (supplier_id, pesanan_id, tanggal, total_pendapatan, status)
                                  VALUES (?, ?, NOW(), ?, 'pending')";
                    $insert_stmt = mysqli_prepare($connection, $insert_sql);
                    mysqli_stmt_bind_param($insert_stmt, 'iid', $supplier_id, $pesanan_id, $total);
                    mysqli_stmt_execute($insert_stmt);
                    mysqli_stmt_close($insert_stmt);
                }
                mysqli_stmt_close($check_stmt);
            }
            mysqli_stmt_close($stmt_items);
            // -------------------------------------------
        }
    }

    mysqli_commit($connection);
    redirect('index.php');

} catch (Exception $e) {
    mysqli_rollback($connection);
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='index.php';</script>";
}
?>