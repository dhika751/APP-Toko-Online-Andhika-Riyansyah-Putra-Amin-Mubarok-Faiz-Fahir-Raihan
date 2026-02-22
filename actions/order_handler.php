<?php
session_start();
require_once '../config/database.php';
require_once '../lib/functions.php';

// Check Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF? For now skip complex CSRF, just basic checks

    if (empty($_SESSION['cart'])) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Keranjang belanja kosong!'];
        header('Location: ../cart.php');
        exit;
    }

    $userId = $_SESSION['user_id'];
    $nama = sanitize($_POST['nama'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $metode_bayar = sanitize($_POST['metode_pembayaran'] ?? 'transfer');

    // Basic validation
    if (empty($nama) || empty($telepon) || empty($alamat)) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Nama, Telepon, dan Alamat wajib diisi!'];
        header('Location: ../checkout.php');
        exit;
    }

    mysqli_begin_transaction($connection);

    try {
        // 1. Get or Update Pelanggan
        // Check if pelanggan exists for this user
        $stmt = mysqli_prepare($connection, "SELECT id FROM pelanggan WHERE users_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pelanggan = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $pelangganId = 0;

        if ($pelanggan) {
            $pelangganId = $pelanggan['id'];
            // Update details
            $stmt = mysqli_prepare($connection, "UPDATE pelanggan SET nama_pelanggan=?, telepon=?, alamat=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssi", $nama, $telepon, $alamat, $pelangganId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // Create new pelanggan linked to user
            // Generate Kode
            $kode = 'PEL-' . date('YmdHis') . '-' . rand(100, 999);
            $stmt = mysqli_prepare($connection, "INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, telepon, alamat, users_id) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssi", $kode, $nama, $telepon, $alamat, $userId);
            mysqli_stmt_execute($stmt);
            $pelangganId = mysqli_insert_id($connection);
            mysqli_stmt_close($stmt);
        }

        // 2. Validate Stock & Calculate Total
        $totalHarga = 0;
        foreach ($_SESSION['cart'] as $prodId => $item) {
            // Check stock using function from functions.php
            if (!checkProductStock($prodId, $item['qty'])) {
                throw new Exception("Stok untuk produk {$item['name']} tidak mencukupi (Diminta: {$item['qty']}). Transaksi dibatalkan.");
            }
            $totalHarga += ($item['price'] * $item['qty']);
        }

        // 3. Insert Pesanan
        $kodePesanan = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);
        $tanggal = date('Y-m-d H:i:s');
        $status = 'pending';

        $stmt = mysqli_prepare($connection, "INSERT INTO pesanan (kode_pesanan, tanggal_pesanan, pelanggan_id, total_harga, status, metode_pembayaran, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssidsss", $kodePesanan, $tanggal, $pelangganId, $totalHarga, $status, $metode_bayar, $catatan);
        mysqli_stmt_execute($stmt);
        $pesananId = mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);

        // 4. Insert Detail Pesanan
        $stmt = mysqli_prepare($connection, "INSERT INTO detail_pesanan (pesanan_id, produk_id, jumlah, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");

        foreach ($_SESSION['cart'] as $prodId => $item) {
            $subtotal = $item['price'] * $item['qty'];
            mysqli_stmt_bind_param($stmt, "iiidd", $pesananId, $prodId, $item['qty'], $item['price'], $subtotal);
            mysqli_stmt_execute($stmt);

            // Update Stock (Reduce)
            // Function is in lib/functions.php
            updateProductStock($prodId, $item['qty'], 'keluar');
        }
        mysqli_stmt_close($stmt);

        // 5. Commit & Clear Cart
        mysqli_commit($connection);
        unset($_SESSION['cart']);

        $_SESSION['toast'][] = ['type' => 'success', 'message' => 'Pesanan berhasil dibuat! Silakan lihat instruksi pembayaran di halaman Pesanan Saya.'];
        header('Location: ../my_orders.php');
        exit;

    } catch (Exception $e) {
        mysqli_rollback($connection);
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Gagal memproses pesanan: ' . $e->getMessage()];
        header('Location: ../checkout.php');
        exit;
    }
} else {
    header('Location: ../checkout.php');
    exit;
}
