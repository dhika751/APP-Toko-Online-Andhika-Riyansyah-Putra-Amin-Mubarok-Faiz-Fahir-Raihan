<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'] ?? null;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d H:i');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $status = $_POST['status'] ?? 'selesai'; // Default selesai means stock added immediately

    // Get detail items
    $produk_ids = $_POST['produk_id'] ?? [];
    $jumlahs = $_POST['jumlah'] ?? [];
    $harga_belis = $_POST['harga_beli'] ?? [];

    if (!$supplier_id) {
        $error = 'Supplier harus dipilih!';
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
                $harga_beli = isset($harga_belis[$index]) ? (float) $harga_belis[$index] : 0;

                if ($jumlah <= 0) {
                    throw new Exception("Jumlah produk harus lebih dari 0!");
                }

                $subtotal = $harga_beli * $jumlah;
                $total_harga += $subtotal;

                $detail_items[] = [
                    'produk_id' => $produk_id,
                    'jumlah' => $jumlah,
                    'harga_beli' => $harga_beli,
                    'subtotal' => $subtotal
                ];
            }

            if (empty($detail_items)) {
                throw new Exception("Tidak ada item pembelian yang valid!");
            }

            // 2. Insert master pembelian
            $kode_pembelian = generateCode('BELI', 'pembelian', 'kode_pembelian');

            $sql_master = "INSERT INTO `pembelian` (`kode_pembelian`, `tanggal`, `supplier_id`, `total_harga`, `status`, `catatan`) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_master = mysqli_prepare($connection, $sql_master);
            if (!$stmt_master) {
                throw new Exception("Prepare statement failed (master): " . mysqli_error($connection));
            }
            mysqli_stmt_bind_param($stmt_master, 'ssidss', $kode_pembelian, $tanggal, $supplier_id, $total_harga, $status, $catatan);

            if (!mysqli_stmt_execute($stmt_master)) {
                throw new Exception("Gagal menyimpan pembelian: " . mysqli_error($connection));
            }

            $pembelian_id = mysqli_insert_id($connection);
            mysqli_stmt_close($stmt_master);

            // 3. Insert detail pembelian and ADD stock
            foreach ($detail_items as $item) {
                $sql_detail = "INSERT INTO `detail_pembelian` (`pembelian_id`, `produk_id`, `jumlah`, `harga_beli`, `subtotal`) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt_detail = mysqli_prepare($connection, $sql_detail);
                if (!$stmt_detail) {
                    throw new Exception("Prepare statement failed (detail): " . mysqli_error($connection));
                }
                mysqli_stmt_bind_param($stmt_detail, 'iiidd', $pembelian_id, $item['produk_id'], $item['jumlah'], $item['harga_beli'], $item['subtotal']);

                if (!mysqli_stmt_execute($stmt_detail)) {
                    throw new Exception("Gagal menyimpan detail: " . mysqli_error($connection));
                }
                mysqli_stmt_close($stmt_detail);

                // Add stock if status is 'selesai'
                if ($status === 'selesai') {
                    updateProductStock($item['produk_id'], $item['jumlah'], 'masuk');
                }
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
$products_result = mysqli_query($connection, "SELECT `id`, `kode_produk`, `nama_produk`, `satuan` FROM `produk` ORDER BY `nama_produk`");
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
    <h2>Tambah Pembelian (Restock)</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" id="formPembelian">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                        <?= dropdownFromTable('supplier', 'id', 'nama_supplier', '', 'supplier_id', '-- Pilih Supplier --', '', 'users_id IS NULL') ?>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal" class="form-label">Tanggal Pembelian <span
                                class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="tanggal" name="tanggal"
                            value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="selesai">Selesai (Langsung Tambah Stok)</option>
                            <option value="pending">Pending (Belum Tambah Stok)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="catatan" class="form-label">Catatan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <hr>
            <h5>Detail Produk</h5>

            <div id="productContainer">
                <div class="detail-row row" data-row="0">
                    <div class="col-md-4">
                        <label class="form-label">Produk</label>
                        <select class="form-control product-select" name="produk_id[]" data-row="0" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-satuan="<?= $p['satuan'] ?>">
                                    <?= htmlspecialchars($p['nama_produk']) ?> (
                                    <?= $p['satuan'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Harga Beli (@Satuan)</label>
                        <input type="number" class="form-control price-input" name="harga_beli[]" min="0" value="0"
                            data-row="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jumlah</label>
                        <input type="number" class="form-control qty-input" name="jumlah[]" min="1" value="1"
                            data-row="0" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Subtotal</label>
                        <input type="text" class="form-control subtotal-display" readonly value="Rp 0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="button" class="btn btn-sm btn-danger remove-row" data-row="0"
                            style="display:none;">Hapus</button>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-success" id="addRowBtn">+ Tambah Item</button>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <h5>Total: <span id="totalDisplay">Rp 0</span></h5>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Simpan Pembelian</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    let rowCount = 1;
    const products = <?= json_encode($products) ?>;

    function formatRupiah(amount) {
        return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function calculateSubtotal(rowIndex) {
        const priceInput = document.querySelector(`.price-input[data-row="${rowIndex}"]`);
        const qtyInput = document.querySelector(`.qty-input[data-row="${rowIndex}"]`);
        const subtotalDisplay = priceInput.closest('.detail-row').querySelector('.subtotal-display');

        const harga = parseFloat(priceInput.value || 0);
        const qty = parseInt(qtyInput.value || 0);
        const subtotal = harga * qty;

        subtotalDisplay.value = formatRupiah(subtotal);
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.detail-row').forEach((row, index) => {
            const priceInput = row.querySelector('.price-input');
            const qtyInput = row.querySelector('.qty-input');

            if (priceInput && qtyInput) {
                const harga = parseFloat(priceInput.value || 0);
                const qty = parseInt(qtyInput.value || 0);
                total += harga * qty;
            }
        });

        document.getElementById('totalDisplay').textContent = formatRupiah(total);
    }

    document.getElementById('addRowBtn').addEventListener('click', function () {
        const container = document.getElementById('productContainer');
        const newRow = document.createElement('div');
        newRow.className = 'detail-row row';
        newRow.dataset.row = rowCount;

        let productOptions = '<option value="">-- Pilih Produk --</option>';
        products.forEach(p => {
            productOptions += `<option value="${p.id}" data-satuan="${p.satuan}">${p.nama_produk} (${p.satuan})</option>`;
        });

        newRow.innerHTML = `
        <div class="col-md-4">
            <label class="form-label">Produk</label>
            <select class="form-control product-select" name="produk_id[]" data-row="${rowCount}" required>
                ${productOptions}
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Harga Beli (@Satuan)</label>
            <input type="number" class="form-control price-input" name="harga_beli[]" min="0" value="0" data-row="${rowCount}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Jumlah</label>
            <input type="number" class="form-control qty-input" name="jumlah[]" min="1" value="1" data-row="${rowCount}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Subtotal</label>
            <input type="text" class="form-control subtotal-display" readonly value="Rp 0">
        </div>
        <div class="col-md-1">
             <label class="form-label">&nbsp;</label><br>
            <button type="button" class="btn btn-sm btn-danger remove-row" data-row="${rowCount}">Hapus</button>
        </div>
    `;

        container.appendChild(newRow);

        const newPriceInput = newRow.querySelector('.price-input');
        const newQtyInput = newRow.querySelector('.qty-input');
        const newRemoveBtn = newRow.querySelector('.remove-row');

        newPriceInput.addEventListener('input', () => calculateSubtotal(newPriceInput.dataset.row));
        newQtyInput.addEventListener('input', () => calculateSubtotal(newQtyInput.dataset.row));
        newRemoveBtn.addEventListener('click', function () {
            newRow.remove();
            calculateTotal();
            updateRemoveButtons();
        });

        rowCount++;
        updateRemoveButtons();
    });

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

    // Init first row
    document.addEventListener('DOMContentLoaded', function () {
        const firstPriceInput = document.querySelector('.price-input[data-row="0"]');
        const firstQtyInput = document.querySelector('.qty-input[data-row="0"]');
        const firstRemoveBtn = document.querySelector('.remove-row[data-row="0"]');

        firstPriceInput.addEventListener('input', () => calculateSubtotal(0));
        firstQtyInput.addEventListener('input', () => calculateSubtotal(0));

        firstRemoveBtn.addEventListener('click', function () {
            if (document.querySelectorAll('.detail-row').length > 1) {
                firstRemoveBtn.closest('.detail-row').remove();
                calculateTotal();
                updateRemoveButtons();
            }
        });
    });
</script>

<?php include '../views/' . $THEME . '/lower_block.php'; ?>
<?php include '../views/' . $THEME . '/footer.php'; ?>