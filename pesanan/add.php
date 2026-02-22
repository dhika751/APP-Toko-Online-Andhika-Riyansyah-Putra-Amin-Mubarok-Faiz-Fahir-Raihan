<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pesanan');

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pelanggan_id = $_POST['pelanggan_id'] ?? null;
    $tanggal_pesanan = $_POST['tanggal_pesanan'] ?? date('Y-m-d H:i');
    $metode_pembayaran = sanitize($_POST['metode_pembayaran'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $status = $_POST['status'] ?? 'pending';

    // Get detail items
    $produk_ids = $_POST['produk_id'] ?? [];
    $jumlahs = $_POST['jumlah'] ?? [];

    if (!$pelanggan_id) {
        $error = 'Pelanggan harus dipilih!';
    } elseif (empty($produk_ids) || count($produk_ids) == 0) {
        $error = 'Minimal harus ada 1 produk!';
    } else {
        // Begin transaction
        mysqli_begin_transaction($connection);

        try {
            // 1. Calculate total
            $total_harga = 0;
            $detail_items = [];

            foreach ($produk_ids as $index => $produk_id) {
                if (empty($produk_id))
                    continue;

                $jumlah = isset($jumlahs[$index]) ? (int) $jumlahs[$index] : 0;

                if ($jumlah <= 0) {
                    throw new Exception("Jumlah produk harus lebih dari 0!");
                }

                // Get product price and check stock
                $sql_produk = "SELECT `harga`, `stok`, `nama_produk` FROM `produk` WHERE `id` = ?";
                $stmt_produk = mysqli_prepare($connection, $sql_produk);
                mysqli_stmt_bind_param($stmt_produk, 'i', $produk_id);
                mysqli_stmt_execute($stmt_produk);
                $result_produk = mysqli_stmt_get_result($stmt_produk);
                $produk = mysqli_fetch_assoc($result_produk);
                mysqli_stmt_close($stmt_produk);

                if (!$produk) {
                    throw new Exception("Produk tidak ditemukan!");
                }

                if ($produk['stok'] < $jumlah) {
                    throw new Exception("Stok {$produk['nama_produk']} tidak mencukupi! Tersedia: {$produk['stok']}");
                }

                $harga_satuan = $produk['harga'];
                $subtotal = $harga_satuan * $jumlah;
                $total_harga += $subtotal;

                $detail_items[] = [
                    'produk_id' => $produk_id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $harga_satuan,
                    'subtotal' => $subtotal
                ];
            }

            if (empty($detail_items)) {
                throw new Exception("Tidak ada item pesanan yang valid!");
            }

            // 2. Insert master pesanan
            $kode_pesanan = generateCode('PES', 'pesanan', 'kode_pesanan');

            $sql_master = "INSERT INTO `pesanan` (`kode_pesanan`, `tanggal_pesanan`, `pelanggan_id`, `total_harga`, `status`, `metode_pembayaran`, `catatan`) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_master = mysqli_prepare($connection, $sql_master);
            mysqli_stmt_bind_param($stmt_master, 'ssidsss', $kode_pesanan, $tanggal_pesanan, $pelanggan_id, $total_harga, $status, $metode_pembayaran, $catatan);

            if (!mysqli_stmt_execute($stmt_master)) {
                throw new Exception("Gagal menyimpan pesanan: " . mysqli_error($connection));
            }

            $pesanan_id = mysqli_insert_id($connection);
            mysqli_stmt_close($stmt_master);

            // 3. Insert detail pesanan and reduce stock
            foreach ($detail_items as $item) {
                $sql_detail = "INSERT INTO `detail_pesanan` (`pesanan_id`, `produk_id`, `jumlah`, `harga_satuan`, `subtotal`) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt_detail = mysqli_prepare($connection, $sql_detail);
                mysqli_stmt_bind_param($stmt_detail, 'iiidd', $pesanan_id, $item['produk_id'], $item['jumlah'], $item['harga_satuan'], $item['subtotal']);

                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Gagal menyimpan detail pesanan: " . mysqli_error($connection));
                }
                mysqli_stmt_close($stmt_detail);

                // Reduce stock
                updateProductStock($item['produk_id'], $item['jumlah'], 'keluar');
            }

            // Commit transaction
            mysqli_commit($connection);

            redirect('index.php');

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($connection);
            $error = $e->getMessage();
        }
    }
}

// Get products for dropdown
$products_result = mysqli_query($connection, "SELECT `id`, `kode_produk`, `nama_produk`, `harga`, `stok`, `satuan` FROM `produk` WHERE `stok` > 0 ORDER BY `nama_produk`");
$products = [];
while ($row = mysqli_fetch_assoc($products_result)) {
    $products[] = $row;
}
?>

<?php include '../views/' . $THEME . '/header.php'; ?>
<?php include '../views/' . $THEME . '/sidebar.php'; ?>
<?php include '../views/' . $THEME . '/topnav.php'; ?>
<?php include '../views/' . $THEME . '/upper_block.php'; ?>

<style>
    .detail-row {
        margin-bottom: 10px;
    }
</style>

<div class="mb-3">
    <h2>Tambah Pesanan</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" id="formPesanan">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="pelanggan_id" class="form-label">Pelanggan <span
                                class="text-danger">*</span></label>
                        <?= dropdownFromTable('pelanggan', 'id', 'nama_pelanggan', '', 'pelanggan_id', '-- Pilih Pelanggan --') ?>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_pesanan" class="form-label">Tanggal Pesanan <span
                                class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="tanggal_pesanan" name="tanggal_pesanan"
                            value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                        <select class="form-control" id="metode_pembayaran" name="metode_pembayaran">
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="COD">COD (Cash on Delivery)</option>
                            <option value="E-Wallet">E-Wallet</option>
                            <option value="Tunai">Tunai</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="pending">Pending</option>
                            <option value="diproses">Diproses</option>
                            <option value="dikirim">Dikirim</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="catatan" class="form-label">Catatan</label>
                <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
            </div>

            <hr>
            <h5>Detail Produk</h5>

            <div id="productContainer">
                <div class="detail-row row" data-row="0">
                    <div class="col-md-5">
                        <label class="form-label">Produk</label>
                        <select class="form-control product-select" name="produk_id[]" data-row="0" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-harga="<?= $p['harga'] ?>"
                                    data-stok="<?= $p['stok'] ?>" data-satuan="<?= $p['satuan'] ?>">
                                    <?= htmlspecialchars($p['nama_produk']) ?> -
                                    <?= formatRupiah($p['harga']) ?> (Stok:
                                    <?= $p['stok'] ?>
                                    <?= $p['satuan'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jumlah</label>
                        <input type="number" class="form-control qty-input" name="jumlah[]" min="1" value="1"
                            data-row="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Subtotal</label>
                        <input type="text" class="form-control subtotal-display" readonly value="Rp 0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="button" class="btn btn-sm btn-danger remove-row" data-row="0"
                            style="display:none;">Hapus</button>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-success" id="addRowBtn">+ Tambah Produk</button>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <h5>Total: <span id="totalDisplay">Rp 0</span></h5>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    let rowCount = 1;
    const products = <?= json_encode($products) ?>;

    // Format Rupiah
    function formatRupiah(amount) {
        return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Calculate subtotal for a row
    function calculateSubtotal(rowIndex) {
        const select = document.querySelector(`.product-select[data-row="${rowIndex}"]`);
        const qtyInput = document.querySelector(`.qty-input[data-row="${rowIndex}"]`);
        const subtotalDisplay = select.closest('.detail-row').querySelector('.subtotal-display');

        if (select.value && qtyInput.value) {
            const selectedOption = select.options[select.selectedIndex];
            const harga = parseFloat(selectedOption.dataset.harga || 0);
            const qty = parseInt(qtyInput.value || 0);
            const subtotal = harga * qty;

            subtotalDisplay.value = formatRupiah(subtotal);
        } else {
            subtotalDisplay.value = 'Rp 0';
        }

        calculateTotal();
    }

    // Calculate total
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.product-select').forEach((select, index) => {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const harga = parseFloat(selectedOption.dataset.harga || 0);
                const qtyInput = document.querySelector(`.qty-input[data-row="${select.dataset.row}"]`);
                const qty = parseInt(qtyInput.value || 0);
                total += harga * qty;
            }
        });

        document.getElementById('totalDisplay').textContent = formatRupiah(total);
    }

    // Add new product row
    document.getElementById('addRowBtn').addEventListener('click', function () {
        const container = document.getElementById('productContainer');
        const newRow = document.createElement('div');
        newRow.className = 'detail-row row';
        newRow.dataset.row = rowCount;

        let productOptions = '<option value="">-- Pilih Produk --</option>';
        products.forEach(p => {
            productOptions += `<option value="${p.id}" data-harga="${p.harga}" data-stok="${p.stok}" data-satuan="${p.satuan}">
            ${p.nama_produk} - ${formatRupiah(p.harga)} (Stok: ${p.stok} ${p.satuan})
        </option>`;
        });

        newRow.innerHTML = `
        <div class="col-md-5">
            <label class="form-label">Produk</label>
            <select class="form-control product-select" name="produk_id[]" data-row="${rowCount}" required>
                ${productOptions}
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Jumlah</label>
            <input type="number" class="form-control qty-input" name="jumlah[]" min="1" value="1" data-row="${rowCount}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Subtotal</label>
            <input type="text" class="form-control subtotal-display" readonly value="Rp 0">
        </div>
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label><br>
            <button type="button" class="btn btn-sm btn-danger remove-row" data-row="${rowCount}">Hapus</button>
        </div>
    `;

        container.appendChild(newRow);

        // Add event listeners to new row
        const newSelect = newRow.querySelector('.product-select');
        const newQtyInput = newRow.querySelector('.qty-input');
        const newRemoveBtn = newRow.querySelector('.remove-row');

        newSelect.addEventListener('change', () => calculateSubtotal(newSelect.dataset.row));
        newQtyInput.addEventListener('input', () => calculateSubtotal(newQtyInput.dataset.row));
        newRemoveBtn.addEventListener('click', function () {
            newRow.remove();
            calculateTotal();
            updateRemoveButtons();
        });

        rowCount++;
        updateRemoveButtons();
    });

    // Update visibility of remove buttons
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.detail-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-row');
            if (rows.length > 1) {
                removeBtn.style.display = 'inline-block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }

    // Event listeners for first row
    document.addEventListener('DOMContentLoaded', function () {
        const firstSelect = document.querySelector('.product-select[data-row="0"]');
        const firstQtyInput = document.querySelector('.qty-input[data-row="0"]');
        const firstRemoveBtn = document.querySelector('.remove-row[data-row="0"]');

        firstSelect.addEventListener('change', () => calculateSubtotal(0));
        firstQtyInput.addEventListener('input', () => calculateSubtotal(0));
        firstRemoveBtn.addEventListener('click', function () {
            if (document.querySelectorAll('.detail-row').length > 1) {
                firstSelect.closest('.detail-row').remove();
                calculateTotal();
                updateRemoveButtons();
            }
        });
    });
</script>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>