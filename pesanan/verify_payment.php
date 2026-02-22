<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';
require_once '../config/database.php';

requireAuth();
requireRoleAccess(['admin']);

$id = $_GET['id'] ?? 0; // This is payment ID, not order ID
$action = $_GET['action'] ?? '';
$order_id = $_GET['order_id'] ?? 0;

if (!$id || !$action || !$order_id) {
    $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Invalid Request'];
    redirect("pesanan/view.php?id=$order_id");
}

if ($action === 'accept') {
    $status = 'dikonfirmasi';
    $message = 'Pembayaran berhasil dikonfirmasi!';
    $new_order_status = 'diproses';
} elseif ($action === 'reject') {
    $status = 'ditolak';
    $message = 'Pembayaran ditolak.';
    $new_order_status = 'pending'; // Revert to pending?
} else {
    $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Invalid Action'];
    redirect("pesanan/view.php?id=$order_id");
}

// Update Payment Status
$sql = "UPDATE `pembayaran` SET `status` = ? WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'si', $status, $id);

if (mysqli_stmt_execute($stmt)) {

    // Optional: Auto-update order status if accepted
    if ($action === 'accept') {
        $sql_order = "UPDATE `pesanan` SET `status` = ? WHERE `id` = ? AND `status` = 'pending'";
        $stmt_order = mysqli_prepare($connection, $sql_order);
        mysqli_stmt_bind_param($stmt_order, 'si', $new_order_status, $order_id);
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
        mysqli_stmt_bind_param($stmt_items, 'i', $order_id);
        mysqli_stmt_execute($stmt_items);
        $result_items = mysqli_stmt_get_result($stmt_items);

        // 2. Insert into transaksi_supplier
        while ($item = mysqli_fetch_assoc($result_items)) {
            $supplier_id = $item['supplier_id'];
            $total = $item['total_per_supplier'];

            // Check if already exists to prevent duplicate (idempotency)
            $check_sql = "SELECT id FROM transaksi_supplier WHERE pesanan_id = ? AND supplier_id = ?";
            $check_stmt = mysqli_prepare($connection, $check_sql);
            mysqli_stmt_bind_param($check_stmt, 'ii', $order_id, $supplier_id);
            mysqli_stmt_execute($check_stmt);

            if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) == 0) {
                // Set status based on order status: if already completed, mark as paid
                $transaction_status = ($new_order_status === 'selesai') ? 'paid_to_supplier' : 'pending';

                $insert_sql = "INSERT INTO transaksi_supplier (supplier_id, pesanan_id, tanggal, total_pendapatan, status)
                              VALUES (?, ?, NOW(), ?, ?)";
                $insert_stmt = mysqli_prepare($connection, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, 'iids', $supplier_id, $order_id, $total, $transaction_status);
                mysqli_stmt_execute($insert_stmt);
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
        mysqli_stmt_close($stmt_items);
        // -------------------------------------------
    }

    $_SESSION['toast'][] = ['type' => 'success', 'message' => $message];
} else {
    $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Database error: ' . mysqli_error($connection)];
}

redirect("pesanan/view.php?id=$order_id");
?>