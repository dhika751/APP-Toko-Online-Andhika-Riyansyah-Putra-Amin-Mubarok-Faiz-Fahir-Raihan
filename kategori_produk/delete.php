<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('kategori_produk');

require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

// Check if category is used by any products
$sql_check = "SELECT COUNT(*) as count FROM `produk` WHERE `kategori_id` = ?";
$stmt_check = mysqli_prepare($connection, $sql_check);
mysqli_stmt_bind_param($stmt_check, 'i', $id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$check_data = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

if ($check_data['count'] > 0) {
    echo "<script>
        alert('Kategori tidak dapat dihapus karena masih digunakan oleh produk!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Delete category
$sql = "DELETE FROM `kategori_produk` WHERE `id` = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    redirect('index.php');
} else {
    echo "<script>
        alert('Gagal menghapus kategori!');
        window.location.href = 'index.php';
    </script>";
    mysqli_stmt_close($stmt);
}
?>