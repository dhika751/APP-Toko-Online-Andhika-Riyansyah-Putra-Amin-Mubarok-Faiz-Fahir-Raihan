<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = $_POST['id'] ?? null;
$new_status = $_POST['status'] ?? null;

if (!$id || !$new_status) {
    showAlert("Data tidak valid!", 'danger');
    redirect('index.php');
}

// Allowed new statuses
if (!in_array($new_status, ['selesai', 'dibatalkan'])) {
    showAlert("Status tidak valid!", 'danger');
    redirect("view.php?id=$id");
}

// 1. Get current purchase
$sql = "SELECT `status` FROM `pembelian` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pembelian = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pembelian) {
    showAlert("Pembelian tidak ditemukan!", 'danger');
    redirect('index.php');
}

if ($pembelian['status'] !== 'pending') {
    showAlert("Hanya pembelian dengan status Pending yang dapat diubah!", 'danger');
    redirect("view.php?id=$id");
}

mysqli_begin_transaction($connection);

try {
    // 2. If changing to 'selesai', add stock
    if ($new_status === 'selesai') {
        $sql_detail = "SELECT `produk_id`, `jumlah` FROM `detail_pembelian` WHERE `pembelian_id` = ?";
        $stmt_detail = mysqli_prepare($connection, $sql_detail);
        mysqli_stmt_bind_param($stmt_detail, 'i', $id);
        mysqli_stmt_execute($stmt_detail);
        $result_detail = mysqli_stmt_get_result($stmt_detail);

        while ($item = mysqli_fetch_assoc($result_detail)) {
            // Update stock (add)
            $update_res = updateProductStock($item['produk_id'], $item['jumlah'], 'masuk');
            if (!$update_res) {
                throw new Exception("Gagal mengupdate stok untuk produk ID: " . $item['produk_id']);
            }
        }
        mysqli_stmt_close($stmt_detail);

        // --- AUTO-GENERATE SUPPLIER TRANSACTIONS ---
        // Get supplier_id and total from pembelian
        $sql_pembelian_info = "SELECT `supplier_id`, `total_harga` FROM `pembelian` WHERE `id` = ?";
        $stmt_info = mysqli_prepare($connection, $sql_pembelian_info);
        mysqli_stmt_bind_param($stmt_info, 'i', $id);
        mysqli_stmt_execute($stmt_info);
        $result_info = mysqli_stmt_get_result($stmt_info);
        $pembelian_info = mysqli_fetch_assoc($result_info);
        mysqli_stmt_close($stmt_info);

        if ($pembelian_info && $pembelian_info['supplier_id']) {
            $supplier_id = $pembelian_info['supplier_id'];
            $total = $pembelian_info['total_harga'];

            // Check if transaction already exists to prevent duplicate
            $check_sql = "SELECT id FROM transaksi_supplier WHERE pembelian_id = ? AND supplier_id = ?";
            $check_stmt = mysqli_prepare($connection, $check_sql);
            mysqli_stmt_bind_param($check_stmt, 'ii', $id, $supplier_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) == 0) {
                // Create supplier transaction record
                $insert_sql = "INSERT INTO transaksi_supplier (supplier_id, pembelian_id, tanggal, total_pendapatan, status)
                              VALUES (?, ?, NOW(), ?, 'pending')";
                $insert_stmt = mysqli_prepare($connection, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, 'iid', $supplier_id, $id, $total);
                mysqli_stmt_execute($insert_stmt);
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
        // -------------------------------------------
    }

    // 3. Update status
    $sql_update = "UPDATE `pembelian` SET `status` = ? WHERE `id` = ?";
    $stmt_update = mysqli_prepare($connection, $sql_update);
    mysqli_stmt_bind_param($stmt_update, 'si', $new_status, $id);

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Gagal mengupdate status pembelian: " . mysqli_error($connection));
    }
    mysqli_stmt_close($stmt_update);

    mysqli_commit($connection);

    $msg_status = $new_status === 'selesai' ? "berhasil diproses dan stok telah ditambahkan" : "telah dibatalkan";
    showAlert("Pembelian $msg_status.", 'success');

} catch (Exception $e) {
    mysqli_rollback($connection);
    showAlert("Terjadi kesalahan: " . $e->getMessage(), 'danger');
}

redirect("pembelian/view.php?id=$id");
?>