<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();

$id = $_GET['id'] ?? 0;
if (!$id) {
    die("Invalid request");
}

require_once '../config/database.php';

// Get transaction details and verify access - support both pesanan and pembelian
$sql = "SELECT ts.*, 
               p.kode_pesanan, pl.nama_pelanggan, pl.alamat as alamat_pelanggan, pl.telepon as telepon_pelanggan,
               pm.kode_pembelian,
               s.nama_supplier
        FROM `transaksi_supplier` ts
        LEFT JOIN `pesanan` p ON ts.pesanan_id = p.id
        LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
        LEFT JOIN `pembelian` pm ON ts.pembelian_id = pm.id
        LEFT JOIN `supplier` s ON ts.supplier_id = s.id
        WHERE ts.id = ?";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaksi = mysqli_fetch_assoc($result);

if (!$transaksi) {
    die("Transaksi tidak ditemukan.");
}

// Access Control check
if ($_SESSION['role'] === 'supplier') {
    // Verify currently logged in supplier owns this transaction
    $user_id = $_SESSION['user_id'];
    $sql_check = "SELECT id FROM supplier WHERE users_id = ? AND id = ?";
    $stmt_check = mysqli_prepare($connection, $sql_check);
    mysqli_stmt_bind_param($stmt_check, 'ii', $user_id, $transaksi['supplier_id']);
    mysqli_stmt_execute($stmt_check);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) === 0) {
        die("Akses ditolak. Transaksi ini bukan milik anda.");
    }
} elseif ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Get items based on transaction type
$supplier_id = $transaksi['supplier_id'];

if ($transaksi['pesanan_id']) {
    // Get ordered items ONLY for this supplier
    $pesanan_id = $transaksi['pesanan_id'];

    $sql_items = "SELECT dp.*, p.nama_produk, p.kode_produk
                  FROM `detail_pesanan` dp
                  JOIN `produk` p ON dp.produk_id = p.id
                  WHERE dp.pesanan_id = ? AND p.supplier_id = ?";

    $stmt_items = mysqli_prepare($connection, $sql_items);
    mysqli_stmt_bind_param($stmt_items, 'ii', $pesanan_id, $supplier_id);
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
} elseif ($transaksi['pembelian_id']) {
    // Get purchased items for this pembelian
    $pembelian_id = $transaksi['pembelian_id'];

    $sql_items = "SELECT dp.*, p.nama_produk, p.kode_produk, dp.harga_beli as harga_satuan
                  FROM `detail_pembelian` dp
                  JOIN `produk` p ON dp.produk_id = p.id
                  WHERE dp.pembelian_id = ?";

    $stmt_items = mysqli_prepare($connection, $sql_items);
    mysqli_stmt_bind_param($stmt_items, 'i', $pembelian_id);
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
} else {
    die("Transaksi tidak memiliki referensi pesanan atau pembelian yang valid.");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Supplier - <?= $transaksi['kode_pesanan'] ?: $transaksi['kode_pembelian'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: #f0f0f0;
            padding: 20px;
        }

        .receipt-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .receipt-header {
            padding: 20px;
            text-align: center;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .receipt-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .receipt-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .receipt-body {
            padding: 25px;
        }

        .receipt-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e0e0e0;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 3px;
        }

        .receipt-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        .receipt-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2ecc71;
            text-align: right;
        }

        .item-table {
            width: 100%;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .item-table th {
            text-align: left;
            padding-bottom: 5px;
            color: #666;
            font-weight: 600;
            border-bottom: 1px solid #eee;
        }

        .item-table td {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }

        .item-table tr:last-child td {
            border-bottom: none;
        }

        .receipt-status {
            display: inline-block;
            padding: 8px 16px;
            background: #2ecc71;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .receipt-ref {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #999;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .action-buttons {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
        }

        .btn-action {
            margin: 5px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .action-buttons,
            .no-print {
                display: none !important;
            }

            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container" id="receipt">
        <!-- Header -->
        <div class="receipt-header">
            <div class="receipt-title">STRUK PENDAPATAN</div>
            <div class="receipt-subtitle">TOKO SUMBER JAYA</div>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <!-- Reference -->
            <div class="receipt-row d-flex justify-content-between">
                <div>
                    <div class="receipt-label">Kode Referensi</div>
                    <div class="receipt-value" style="font-family: monospace;">
                        <?= $transaksi['kode_pesanan'] ?: $transaksi['kode_pembelian'] ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="receipt-label">Tanggal</div>
                    <div class="receipt-value">
                        <?= date('d/m/Y H:i', strtotime($transaksi['tanggal'])) ?>
                    </div>
                </div>
            </div>

            <!-- Supplier Info -->
            <div class="receipt-row">
                <div class="receipt-label">Supplier (Penerima)</div>
                <div class="receipt-value">
                    <?= htmlspecialchars($transaksi['nama_supplier']) ?>
                </div>
            </div>

            <!-- Customer Info (if order) -->
            <?php if ($transaksi['nama_pelanggan']): ?>
                <div class="receipt-row">
                    <div class="receipt-label">Pelanggan (Sumber)</div>
                    <div class="receipt-value">
                        <?= htmlspecialchars($transaksi['nama_pelanggan']) ?>
                    </div>
                    <?php if ($transaksi['alamat_pelanggan']): ?>
                        <div class="receipt-label mt-1">Alamat</div>
                        <div class="receipt-value small text-muted">
                            <?= htmlspecialchars($transaksi['alamat_pelanggan']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Items -->
            <div class="receipt-row">
                <div class="receipt-label mb-2">Rincian Produk Terjual</div>
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $calculated_total = 0;
                        if (mysqli_num_rows($result_items) > 0) {
                            mysqli_data_seek($result_items, 0); // Reset pointer just in case
                            while ($item = mysqli_fetch_assoc($result_items)):
                                $calculated_total += $item['subtotal'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['nama_produk']) ?></div>
                                        <div class="small text-muted">@ <?= number_format($item['harga_satuan'], 0, ',', '.') ?>
                                        </div>
                                    </td>
                                    <td class="text-end" valign="top">x<?= $item['jumlah'] ?></td>
                                    <td class="text-end" valign="top"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endwhile;
                        } else {
                            echo "<tr><td colspan='3' class='text-center'>Tidak ada item detail</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Total -->
            <div class="receipt-row">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="receipt-label">TOTAL PENDAPATAN</div>
                    <div class="receipt-amount">
                        Rp <?= number_format($transaksi['total_pendapatan'], 0, ',', '.') ?>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="text-center mt-4">
                <?php if ($transaksi['status'] == 'paid_to_supplier'): ?>
                    <span class="receipt-status">
                        <i class="bi bi-check-circle-fill me-1"></i> SUDAH DICAIRKAN
                    </span>
                <?php else: ?>
                    <span class="receipt-status bg-warning text-dark">
                        <i class="bi bi-clock-history me-1"></i> MENUNGGU PENCAIRAN
                    </span>
                <?php endif; ?>
            </div>

            <!-- Footer Reference -->
            <div class="receipt-ref">
                ID Transaksi: #<?= $transaksi['id'] ?><br>
                Simpan struk ini sebagai bukti pendapatan Anda.
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-primary btn-action">
            <i class="bi bi-printer"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-action">
            <i class="bi bi-x-lg"></i> Tutup
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</body>

</html>