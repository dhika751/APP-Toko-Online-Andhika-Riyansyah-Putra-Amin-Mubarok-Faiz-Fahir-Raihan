<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';
require_once '../config/database.php';

requireAuth();

$id = $_GET['id'] ?? 0;
$error = '';

// Get pesanan master data
$sql_master = "SELECT p.*, pl.nama_pelanggan, pl.users_id 
               FROM `pesanan` p 
               LEFT JOIN `pelanggan` pl ON p.pelanggan_id = pl.id
               WHERE p.id = ?";
$stmt_master = mysqli_prepare($connection, $sql_master);
mysqli_stmt_bind_param($stmt_master, 'i', $id);
mysqli_stmt_execute($stmt_master);
$result_master = mysqli_stmt_get_result($stmt_master);
$pesanan = mysqli_fetch_assoc($result_master);
mysqli_stmt_close($stmt_master);

// Validation
if (!$pesanan) {
    redirect('pesanan/index.php');
}

// Ensure the order belongs to the logged-in user (unless admin)
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

if ($user_role !== 'admin' && $pesanan['users_id'] != $user_id) {
    redirect('pesanan/index.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pengirim = sanitize($_POST['nama_pengirim'] ?? '');
    $bank_pengirim = sanitize($_POST['bank_pengirim'] ?? '');
    $nomor_rekening = sanitize($_POST['nomor_rekening'] ?? '');
    $tanggal_bayar = $_POST['tanggal_bayar'] ?? ''; // No default, must be provided by user

    // Validasi input
    if (empty($nama_pengirim) || empty($bank_pengirim) || empty($nomor_rekening) || empty($tanggal_bayar)) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => 'Semua field wajib diisi!'];
        redirect("pesanan/konfirmasi.php?id=$id");
    }

    try {
        // Generate kode pembayaran
        $kode_pembayaran = generateCode('BYR', 'pembayaran', 'kode_pembayaran');

        // Insert payment record with status 'menunggu_bukti' (waiting for proof upload)
        $sql = "INSERT INTO `pembayaran` 
                (`kode_pembayaran`, `pesanan_id`, `tanggal_bayar`, `jumlah_bayar`, `metode_bayar`, 
                 `nama_pengirim`, `bank_pengirim`, `nomor_rekening_pengirim`, `bukti_bayar`, `status`) 
                VALUES (?, ?, ?, ?, 'Transfer', ?, ?, ?, NULL, 'menunggu_bukti')";

        $stmt = mysqli_prepare($connection, $sql);
        if (!$stmt) {
            throw new Exception('Gagal menyiapkan query pembayaran: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param(
            $stmt,
            'ssidsss',
            $kode_pembayaran,
            $id, // Use $id from GET parameter
            $tanggal_bayar,
            $pesanan['total_harga'], // Use total_harga from pesanan
            $nama_pengirim,
            $bank_pengirim,
            $nomor_rekening
        );

        if (mysqli_stmt_execute($stmt)) {
            $payment_id = mysqli_insert_id($connection);
            mysqli_stmt_close($stmt);
            $_SESSION['toast'][] = [
                'type' => 'success',
                'message' => 'Konfirmasi pembayaran berhasil! Silakan lakukan transfer dan upload bukti pembayaran.'
            ];
            // Redirect to receipt generator instead of view page
            redirect("pesanan/generate_struk.php?id=$payment_id");
        } else {
            throw new Exception('Gagal menyimpan data pembayaran: ' . mysqli_error($connection));
        }
    } catch (Exception $e) {
        $_SESSION['toast'][] = ['type' => 'error', 'message' => $e->getMessage()];
        redirect("pesanan/konfirmasi.php?id=$id");
    }
}

// Determine View Theme
$use_theme = $THEME ?? 'default';
?>

<?php include '../views/' . $use_theme . '/header.php'; ?>
<?php include '../views/' . $use_theme . '/sidebar.php'; ?>
<?php include '../views/' . $use_theme . '/topnav.php'; ?>
<?php include '../views/' . $use_theme . '/upper_block.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Konfirmasi Pembayaran</h2>
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

        <form method="POST" action="">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-badge"></i> Informasi Pengirim</h6>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Pemilik Rekening <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_pengirim" required
                        placeholder="Contoh: Budi Santoso" value="<?= $_POST['nama_pengirim'] ?? '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bank Pengirim <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="bank_pengirim" required
                        placeholder="Contoh: BCA, BRI, Dana, GoPay" value="<?= $_POST['bank_pengirim'] ?? '' ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor Rekening / E-Wallet <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="nomor_rekening" required
                    placeholder="Contoh: 1234567890" value="<?= $_POST['nomor_rekening'] ?? '' ?>">
            </div>

            <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-wallet2"></i> Detail Pembayaran</h6>

            <div class="mb-3">
                <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="tanggal_bayar" required
                    value="<?= date('Y-m-d\TH:i') ?>">
            </div>

            <div class="alert alert-warning">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Penting:</strong> Setelah konfirmasi ini, silakan lakukan transfer ke rekening toko. Anda akan
                diminta upload bukti transfer setelah transfer selesai.
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle"></i> Konfirmasi
                    Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<?php include '../views/' . $use_theme . '/lower_block.php'; ?>
<?php include '../views/' . $use_theme . '/footer.php'; ?>