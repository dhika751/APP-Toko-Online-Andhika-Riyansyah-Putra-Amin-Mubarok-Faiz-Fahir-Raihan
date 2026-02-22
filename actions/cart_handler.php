<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = $_POST['product_id'] ?? 0;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($action === 'add') {
        // Fetch product details
        $stmt = mysqli_prepare($connection, "SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori_produk k ON p.kategori_id = k.id WHERE p.id = ?");
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Check Stock
            if ($row['stok'] <= 0) {
                $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Stok produk habis!'];
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../catalog.php'));
                exit;
            }

            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['qty']++;
            } else {
                $_SESSION['cart'][$productId] = [
                    'name' => $row['nama_produk'],
                    'price' => $row['harga'],
                    'qty' => 1,
                    'photo' => $row['photo'],
                    'category' => $row['nama_kategori']
                ];
            }
            $_SESSION['toast'][] = ['type' => 'success', 'message' => 'Produk ditambahkan ke keranjang!'];
        }
        mysqli_stmt_close($stmt);
    } elseif ($action === 'update') {
        $qty = max(1, intval($_POST['qty'] ?? 1));
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['qty'] = $qty;
            $_SESSION['toast'][] = ['type' => 'info', 'message' => 'Jumlah diupdate!'];
        }
    } elseif ($action === 'remove') {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['toast'][] = ['type' => 'warning', 'message' => 'Produk dihapus dari keranjang.'];
        }
    }
}

// Redirect back
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../cart.php'));
exit;
?>