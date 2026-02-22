<?php
session_start();
/**
 * Generate Mock Bank Transfer Receipt
 * For testing/simulation purposes only
 */

require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAuth();

$id = $_GET['id'] ?? 0; // Payment ID

// Get payment and order data
$sql = "SELECT pb.*, p.kode_pesanan, p.total_harga 
        FROM `pembayaran` pb
        LEFT JOIN `pesanan` p ON pb.pesanan_id = p.id
        WHERE pb.id = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$payment) {
    $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Data pembayaran tidak ditemukan!'];
    redirect('pesanan/index.php');
}

// Generate random transaction reference
$ref_number = 'TRF' . date('YmdHis') . rand(100, 999);
$timestamp = date('d/m/Y H:i:s');

// Check if in embed mode
$embed_mode = isset($_GET['embed']) && $_GET['embed'] == '1';

// Handle Direct Submission (Kirim Konfirmasi)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_confirmation'])) {
    if ($payment['status'] === 'menunggu_bukti') {
        $sql_update = "UPDATE `pembayaran` SET `status` = 'pending' WHERE `id` = ?";
        $stmt_update = mysqli_prepare($connection, $sql_update);
        mysqli_stmt_bind_param($stmt_update, 'i', $id);

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['toast'][] = [
                'type' => 'success',
                'message' => 'Konfirmasi pembayaran telah dikirim ke Admin! Silakan tunggu verifikasi.'
            ];
            redirect("pesanan/view.php?id=" . $payment['pesanan_id']);
        } else {
            $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Gagal mengirim konfirmasi: ' . mysqli_error($connection)];
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $_SESSION['toast'][] = ['type' => 'warning', 'message' => 'Konfirmasi sudah dikirim sebelumnya.'];
        redirect("pesanan/view.php?id=" . $payment['pesanan_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Transfer -
        <?= $payment['bank_pengirim'] ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: #f0f0f0;
            padding: 20px;
        }

        .receipt-container {
            max-width: 400px;
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
        }

        .receipt-header.bca {
            background: linear-gradient(135deg, #003d82 0%, #0059b3 100%);
        }

        .receipt-header.bri {
            background: linear-gradient(135deg, #003d7a 0%, #0066cc 100%);
        }

        .receipt-header.mandiri {
            background: linear-gradient(135deg, #00509e 0%, #fdb913 100%);
        }

        .bank-logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .receipt-title {
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

        .embed-mode .action-buttons,
        .embed-mode .no-print {
            display: none !important;
        }

        .embed-mode body {
            padding: 10px;
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

<body class="<?= $embed_mode ? 'embed-mode' : '' ?>">
    <div class="no-print text-center mb-3">
        <h5>Bukti Transfer</h5>
    </div>

    <div class="receipt-container" id="receipt">
        <!-- Header -->
        <div class="receipt-header <?= strtolower($payment['bank_pengirim']) ?>">
            <div class="bank-logo">
                <?= strtoupper($payment['bank_pengirim']) ?>
            </div>
            <div class="receipt-title">BUKTI TRANSFER</div>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <!-- Reference Number -->
            <div class="receipt-row">
                <div class="receipt-label">Nomor Referensi</div>
                <div class="receipt-value" style="font-family: monospace;">
                    <?= $ref_number ?>
                </div>
            </div>

            <!-- Date & Time -->
            <div class="receipt-row">
                <div class="receipt-label">Tanggal & Waktu</div>
                <div class="receipt-value">
                    <?= $timestamp ?>
                </div>
            </div>

            <!-- From -->
            <div class="receipt-row">
                <div class="receipt-label">Dari</div>
                <div class="receipt-value">
                    <?= htmlspecialchars($payment['nama_pengirim']) ?>
                </div>
                <div class="receipt-label mt-1">Rekening</div>
                <div class="receipt-value">
                    <?= htmlspecialchars($payment['nomor_rekening_pengirim']) ?>
                </div>
            </div>

            <!-- To -->
            <div class="receipt-row">
                <div class="receipt-label">Kepada</div>
                <div class="receipt-value">TOKO SUMBER JAYA</div>
                <div class="receipt-label mt-1">Rekening</div>
                <div class="receipt-value">
                    <?php
                    // Show appropriate account based on bank
                    if (stripos($payment['bank_pengirim'], 'bca') !== false) {
                        echo '123-456-7890';
                    } else {
                        echo '098-765-4321';
                    }
                    ?>
                </div>
            </div>

            <!-- Amount -->
            <div class="receipt-row">
                <div class="receipt-label">Jumlah Transfer</div>
                <div class="receipt-amount">
                    <?= formatRupiah($payment['jumlah_bayar']) ?>
                </div>
            </div>

            <!-- Berita/Note -->
            <div class="receipt-row">
                <div class="receipt-label">Berita</div>
                <div class="receipt-value">Pembayaran #
                    <?= htmlspecialchars($payment['kode_pesanan']) ?>
                </div>
            </div>

            <!-- Status -->
            <div class="receipt-row text-center">
                <span class="receipt-status">
                    <i class="bi bi-check-circle-fill"></i> BERHASIL
                </span>
            </div>

            <!-- Footer Reference -->
            <div class="receipt-ref">
                Simpan bukti ini sebagai referensi transaksi Anda
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <div class="mb-3">
            <button onclick="window.print()" class="btn btn-primary btn-action">
                <i class="bi bi-printer"></i> Print Struk
            </button>
            <button onclick="downloadReceipt()" class="btn btn-success btn-action">
                <i class="bi bi-download"></i> Download PNG
            </button>
        </div>

        <?php if ($payment['status'] === 'menunggu_bukti'): ?>
            <div class="card border-primary mb-3">
                <div class="card-body">
                    <p class="mb-3 text-primary fw-bold">Klik tombol di bawah untuk mengirim data struk di atas ke Admin sebagai bukti transfer.</p>
                    <form method="POST" action="">
                        <button type="submit" name="submit_confirmation" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-send-fill"></i> KIRIM KONFIRMASI KE ADMIN
                        </button>
                    </form>
                </div>
            </div>
            <div class="text-center">
                <a href="upload_bukti.php?id=<?= $payment['pesanan_id'] ?>" class="text-muted">
                    <small>Atau upload foto bukti transfer secara manual</small>
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="view.php?id=<?= $payment['pesanan_id'] ?>" class="btn btn-secondary btn-action">
                <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function downloadReceipt() {
            const receipt = document.getElementById('receipt');

            html2canvas(receipt, {
                scale: 2,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                // Convert to blob and download
                canvas.toBlob(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'Bukti_Transfer_<?= $payment['kode_pesanan'] ?>_<?= date('YmdHis') ?>.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }, 'image/png');
            });
        }
    </script>
</body>

</html>