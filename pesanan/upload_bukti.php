<?php
session_start();
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

$id = $_GET['id'] ?? 0;

// Get order data
$sql = "SELECT p.*, 
        (SELECT id FROM pembayaran WHERE pesanan_id = p.id AND status = 'menunggu_bukti' ORDER BY id DESC LIMIT 1) as payment_id
        FROM `pesanan` p WHERE p.id = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Pesanan tidak ditemukan!'];
    redirect('pesanan/index.php');
}

// Check if payment record exists with status 'menunggu_bukti'
if (!$pesanan['payment_id']) {
    $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Anda belum mengkonfirmasi pembayaran atau bukti sudah diupload!'];
    redirect("pesanan/view.php?id=$id");
}

$error = '';

// Handle file upload submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'File bukti pembayaran wajib diupload!'];
        redirect("pesanan/upload_bukti.php?id=$id");
    }

    $bukti_bayar = handle_payment_proof_upload($_FILES['bukti_bayar']);
    if ($bukti_bayar === false) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Gagal upload file. Pastikan file gambar (JPG/PNG) atau PDF dengan ukuran maksimal 2MB.'];
        redirect("pesanan/upload_bukti.php?id=$id");
    }

    try {
        // Update payment record with bukti_bayar and change status to 'pending'
        $sql = "UPDATE `pembayaran` 
                SET `bukti_bayar` = ?, `status` = 'pending' 
                WHERE `id` = ? AND `status` = 'menunggu_bukti'";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $bukti_bayar, $pesanan['payment_id']);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $_SESSION['toast'][] = [
                'type' => 'success',
                'message' => 'Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.'
            ];
            redirect("pesanan/view.php?id=$id");
        } else {
            throw new Exception('Gagal menyimpan bukti pembayaran: ' . mysqli_error($connection));
        }
    } catch (Exception $e) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => $e->getMessage()];
        redirect("pesanan/upload_bukti.php?id=$id");
    }
}

// Use default theme if not set
$THEME = $THEME ?? 'default';

require_once '../views/' . $THEME . '/header.php';
require_once '../views/' . $THEME . '/sidebar.php';
require_once '../views/' . $THEME . '/topnav.php';
require_once '../views/' . $THEME . '/upper_block.php';
?>

<div class="col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-cloud-upload me-2"></i>Upload Bukti Transfer</h4>
        <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Pesanan #
                <?= htmlspecialchars($pesanan['kode_pesanan']) ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Total Tagihan: <strong>
                    <?= formatRupiah($pesanan['total_harga']) ?>
                </strong>
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Informasi:</strong> Upload bukti transfer dari bank setelah Anda melakukan pembayaran.
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-image"></i> Bukti Pembayaran</h6>

                <div class="mb-3">
                    <label class="form-label">Upload Bukti Transfer <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="bukti_bayar" accept="image/*,application/pdf"
                        required>
                    <div class="form-text">Format: JPG, PNG, PDF. Maksimal 2MB.</div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-upload"></i> Upload Bukti
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../views/' . $THEME . '/lower_block.php';
require_once '../views/' . $THEME . '/footer.php';
?>