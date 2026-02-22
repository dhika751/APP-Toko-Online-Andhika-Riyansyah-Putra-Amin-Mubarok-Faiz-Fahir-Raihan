<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$ids = $_POST['ids'] ?? [];

if (empty($ids)) {
    $_SESSION['error_message'] = "Tidak ada item yang dipilih";
    header('Location: index.php');
    exit;
}

// Validate all IDs are integers
$ids = array_filter($ids, 'is_numeric');
$ids = array_map('intval', $ids);

if (empty($ids)) {
    $_SESSION['error_message'] = "ID tidak valid";
    header('Location: index.php');
    exit;
}

mysqli_begin_transaction($connection);

try {
    $deleted_count = 0;

    foreach ($ids as $id) {
        // Get purchase details to restore stock
        // CORRECTED TABLE NAME: detail_pembelian
        $sql_get = "SELECT pd.produk_id, pd.jumlah 
                   FROM detail_pembelian pd 
                   WHERE pd.pembelian_id = ?";
        $stmt_get = mysqli_prepare($connection, $sql_get);

        if (!$stmt_get) {
            throw new Exception("Error preparing select statement: " . mysqli_error($connection));
        }

        mysqli_stmt_bind_param($stmt_get, 'i', $id);
        mysqli_stmt_execute($stmt_get);
        $result_get = mysqli_stmt_get_result($stmt_get);

        // Restore stock for each product (subtract because it's purchase)
        while ($detail = mysqli_fetch_assoc($result_get)) {
            $sql_update_stock = "UPDATE produk SET stok = stok - ? WHERE id = ?";
            $stmt_stock = mysqli_prepare($connection, $sql_update_stock);

            if (!$stmt_stock) {
                throw new Exception("Error preparing stock update: " . mysqli_error($connection));
            }

            mysqli_stmt_bind_param($stmt_stock, 'ii', $detail['jumlah'], $detail['produk_id']);

            if (!mysqli_stmt_execute($stmt_stock)) {
                throw new Exception("Error updating stock: " . mysqli_stmt_error($stmt_stock));
            }
            mysqli_stmt_close($stmt_stock);
        }
        mysqli_stmt_close($stmt_get);

        // Delete purchase details
        // CORRECTED TABLE NAME: detail_pembelian
        $sql_detail = "DELETE FROM detail_pembelian WHERE pembelian_id = ?";
        $stmt_detail = mysqli_prepare($connection, $sql_detail);

        if (!$stmt_detail) {
            throw new Exception("Error preparing detail delete: " . mysqli_error($connection));
        }

        mysqli_stmt_bind_param($stmt_detail, 'i', $id);

        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Error deleting details: " . mysqli_stmt_error($stmt_detail));
        }
        mysqli_stmt_close($stmt_detail);

        // Delete purchase
        $sql_pembelian = "DELETE FROM pembelian WHERE id = ?";
        $stmt_pembelian = mysqli_prepare($connection, $sql_pembelian);

        if (!$stmt_pembelian) {
            throw new Exception("Error preparing pembelian delete: " . mysqli_error($connection));
        }

        mysqli_stmt_bind_param($stmt_pembelian, 'i', $id);

        if (mysqli_stmt_execute($stmt_pembelian)) {
            $deleted_count++;
        } else {
            throw new Exception("Error deleting pembelian: " . mysqli_stmt_error($stmt_pembelian));
        }
        mysqli_stmt_close($stmt_pembelian);
    }

    mysqli_commit($connection);

    $_SESSION['success_message'] = "$deleted_count pembelian berhasil dihapus dan stok dikembalikan.";
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    mysqli_rollback($connection);
    $_SESSION['error_message'] = "Gagal menghapus pembelian: " . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>