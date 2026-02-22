<?php
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
function redirect($url)
{
    // If URL is relative, prepend BASE_URL
    if (!preg_match('~^(https?://|//)~', $url) && !str_starts_with($url, '/')) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}
function showAlert($message, $type = 'danger')
{
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
$safeMessage
<button type='button' class='btn-close' data-bs-dismiss='alert'></button>
</div>";
}
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}
function getUserRole()
{
    return $_SESSION['role'] ?? null;
}
//=== NEW: CSRF Functions===
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function validateCSRFToken($token)
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
function validatePassword($password, $enabled = true)
{
    // If validation is disabled, always return valid
    if (!$enabled) {
        return []; // Always valid when disabled
    }
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    return $errors; // empty array = valid
}
function userCanAccess($allowedRoles = ['admin'])
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userRole = $_SESSION['role'] ?? '';
    return in_array($userRole, $allowedRoles);
}
/**
 * Show access denied page with error message
 * @param array $allowedRoles List of roles allowed to access the module
 */
function showAccessDenied($allowedRoles = ['admin'])
{
    $roleLabels = getRoleLabels(); // Now loaded from menu.json via config functions
    $allowedLabels = array_map(fn($r) => $roleLabels[$r] ?? $r, $allowedRoles);
    $allowedText = implode(' atau ', $allowedLabels);
    include __DIR__ . '/../views/default/header.php';
    include __DIR__ . '/../views/default/sidebar.php';
    include __DIR__ . '/../views/default/topnav.php';
    include __DIR__ . '/../views/default/upper_block.php';
    ?>
    <div class="alert alert-danger">
        <h4>🔒 Akses Ditolak</h4>
        <p>
            Halaman ini hanya dapat diakses oleh: <strong><?= htmlspecialchars($allowedText) ?></strong>.
        </p>
        <p>
            Anda login sebagai
            <strong><?= htmlspecialchars(getRoleLabel($_SESSION['role'] ?? 'user')) ?></strong>.
        </p>
        <a href="<?= base_url('index.php') ?>" class="btn btn-primary">
            Kembali ke Dashboard
        </a>
    </div>
    <?php
    include __DIR__ . '/../views/default/lower_block.php';
    include __DIR__ . '/../views/default/footer.php';
    exit();
}
function requireRoleAccess($allowedRoles = ['admin', 'dosen'], $redirectUrl = null)
{
    if (!userCanAccess($allowedRoles)) {
        if ($redirectUrl) {
            redirect($redirectUrl);
        } else {
            showAccessDenied($allowedRoles);
        }
    }
}
function loadMenuConfig()
{
    $configFile = __DIR__ . '/../config/menu.json';
    if (file_exists($configFile)) {
        $jsonContent = file_get_contents($configFile);
        return json_decode($jsonContent, true) ?: [];
    }
    return [];
}
function getRoleLabel($role)
{
    $menuConfig = loadMenuConfig();
    return $menuConfig['roles'][$role]['label'] ?? $role;
}
/**
 * Get all role labels
 */
function getRoleLabels()
{
    $menuConfig = loadMenuConfig();
    $labels = [];
    foreach ($menuConfig['roles'] as $role => $config) {
        $labels[$role] = $config['label'];
    }
    return $labels;
}
function getAllowedRolesForModule($moduleName)
{
    $menuConfig = loadMenuConfig();
    return $menuConfig['modules'][$moduleName]['allowed_roles'] ?? ['admin']; // default to admin if not found
}
/**
 * Check if current user can access a specific module
 */
function userCanAccessModule($moduleName)
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userRole = $_SESSION['role'] ?? '';
    $allowedRoles = getAllowedRolesForModule($moduleName);
    return in_array($userRole, $allowedRoles);
}
/**
 * Require role access for a specific module
 */
function requireModuleAccess($moduleName, $redirectUrl = null)
{
    $allowedRoles = getAllowedRolesForModule($moduleName);
    if (!userCanAccessModule($moduleName)) {
        if ($redirectUrl) {
            redirect($redirectUrl);
        } else {
            showAccessDenied($allowedRoles);
        }
    }
}
function base_url($path = '')
{
    $url = BASE_URL . '/' . $path;
    return $url;
}
// helpers/form_helper.php
function dropdownFromTable(
    $table,
    $value_field = 'id',
    $label_field = 'name',
    $selected = '',
    $name = '',
    $placeholder = '-- Pilih --',
    $order_by = '',
    $where = ''
) {
    // Use global connection from config/database.php
    global $connection;
    // Validate/sanitize identifiers (basic protection)
// In real apps, whitelist allowed tables/columns!
    $value_field = str_replace('`', '', $value_field);
    $label_field = str_replace('`', '', $label_field);
    $table = str_replace('`', '', $table);
    if ($order_by) {
        $order_by = str_replace('`', '', $order_by);
    }
    // Build query
    $sql = "SELECT `$value_field`, `$label_field` FROM `$table`";
    if ($where) {
        // âš ï¸ WARNING: $where must be trusted or pre-sanitized!
        $sql .= " WHERE $where";
    }
    $sql .= $order_by
        ? " ORDER BY `$order_by`"
        : " ORDER BY `$label_field` ASC";
    $result = mysqli_query($connection, $sql);
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="form-control">';
    $html .= '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $value = htmlspecialchars($row[$value_field], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($row[$label_field], ENT_QUOTES, 'UTF-8');
            $selected_attr = ($row[$value_field] == $selected) ? 'selected' : '';
            $html .= "<option value=\"$value\" $selected_attr>$label</option>";
        }
    } else {
        $html .= '<option value="">-- Tidak ada data --</option>';
    }
    $html .= '</select>';
    return $html;
}
// helpers/db_helper.php (or add to existing helper file)
function getFieldValue($table, $field, $where_field, $where_value)
{
    global $connection;
    // Basic sanitization: remove backticks to avoid injection in identifiers
    $table = str_replace('`', '', $table);
    $field = str_replace('`', '', $field);
    $where_field = str_replace('`', '', $where_field);
    // Use prepared statement via mysqli to prevent SQL injection
    $sql = "SELECT `$field` FROM `$table` WHERE `$where_field` = ? LIMIT 1";
    $stmt = mysqli_prepare($connection, $sql);
    if (!$stmt) {
        error_log("SQL prepare error: " . mysqli_error($connection));
        return null;
    }
    // Determine the type for bind_param (assume string unless numeric)
    $type = is_int($where_value) || is_float($where_value) ? 'd' : 's';
    mysqli_stmt_bind_param($stmt, $type, $where_value);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    return $row ? $row[0] : null;
}
// helpers/db_helper.php
/**
 * Sum a field from a detail table and update the master total automatically.
 *
 * @param mysqli $connection       Database connection
 * @param string $detail_table     Detail table name (e.g., 'penjualan_detail')
 * @param string $sum_field        Field to sum in detail table (e.g., 'subtotal')
 * @param string $detail_fk_field  Foreign key in detail table (e.g., 'penjualan_id')
 * @param string $master_table     Master table name (e.g., 'penjualan')
 * @param string $master_pk_field  Primary key in master table (e.g., 'id')
 * @param string $master_total_field Field in master to update (e.g., 'total_bayar')
 * @param mixed  $master_id        The master record ID
 * @return bool                    True on success, false on failure
 */
function updateMasterTotalFromDetail(
    $connection,
    $detail_table,
    $sum_field,
    $detail_fk_field,
    $master_table,
    $master_pk_field,
    $master_total_field,
    $master_id
) {
    // Sanitize identifiers (remove backticks to prevent injection in names)
    $detail_table = str_replace('`', '', $detail_table);
    $sum_field = str_replace('`', '', $sum_field);
    $detail_fk_field = str_replace('`', '', $detail_fk_field);
    $master_table = str_replace('`', '', $master_table);
    $master_pk_field = str_replace('`', '', $master_pk_field);
    $master_total_field = str_replace('`', '', $master_total_field);
    // Step 1: Calculate the sum from detail table
    $sql_sum = "SELECT COALESCE(SUM(`$sum_field`), 0) AS total
FROM `$detail_table`
WHERE `$detail_fk_field` = ?";
    $stmt_sum = mysqli_prepare($connection, $sql_sum);
    if (!$stmt_sum) {
        error_log("updateMasterTotalFromDetail (SUM) prepare error: " . mysqli_error($connection));
        return false;
    }
    $type = is_int($master_id) || is_float($master_id) ? 'd' : 's';
    mysqli_stmt_bind_param($stmt_sum, $type, $master_id);
    mysqli_stmt_execute($stmt_sum);
    $result = mysqli_stmt_get_result($stmt_sum);
    $row = mysqli_fetch_assoc($result);
    $total = (float) ($row['total'] ?? 0.0);
    mysqli_stmt_close($stmt_sum);
    // Step 2: Update master table
    $sql_update = "UPDATE `$master_table`
SET `$master_total_field` = ?
WHERE `$master_pk_field` = ?";
    $stmt_update = mysqli_prepare($connection, $sql_update);
    if (!$stmt_update) {
        error_log("updateMasterTotalFromDetail (UPDATE) prepare error: " . mysqli_error($connection));
        return false;
    }
    mysqli_stmt_bind_param($stmt_update, "d" . $type, $total, $master_id);
    $success = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    return $success;
}

function generateNumericBookId()
{
    return time() . mt_rand(100, 999);
}
function handle_file_upload($file)
{
    // Check if file was uploaded
    if (!isset($file['name']) || empty($file['name'])) {
        return ''; // No file uploaded
    }

    $target_dir = UPLOAD_DIR_BUKU;

    // Create upload directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'buku_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
    $target_file = $target_dir . $filename;

    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return false; // File too large
    }

    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_types)) {
        return false; // Invalid file type
    }

    // Check if file is actually an image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false; // Not an image
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    }

    return false;
}

// ===== TOKO ONLINE HELPER FUNCTIONS =====

/**
 * Generate unique code for categories, suppliers, products, customers, orders, payments
 */
function generateCode($prefix, $table, $code_field)
{
    global $connection;

    // Get the last code
    $sql = "SELECT `$code_field` FROM `$table` WHERE `$code_field` LIKE ? ORDER BY `id` DESC LIMIT 1";
    $stmt = mysqli_prepare($connection, $sql);
    $like_pattern = $prefix . '%';
    mysqli_stmt_bind_param($stmt, 's', $like_pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $last_code = $row[$code_field];
        // Extract number from code (e.g., KAT001 -> 001)
        $number = (int) substr($last_code, strlen($prefix));
        $new_number = $number + 1;
    } else {
        $new_number = 1;
    }

    mysqli_stmt_close($stmt);

    // Format with leading zeros (3 digits)
    return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
}

/**
 * Format price in Indonesian Rupiah
 */
function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Update product stock
 */
function updateProductStock($product_id, $quantity, $type = 'masuk')
{
    global $connection;

    // Update stock in produk table
    if ($type == 'masuk') {
        $sql = "UPDATE `produk` SET `stok` = `stok` + ? WHERE `id` = ?";
    } else {
        $sql = "UPDATE `produk` SET `stok` = `stok` - ? WHERE `id` = ?";
    }

    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $quantity, $product_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Record stock movement
    if ($result) {
        $sql_movement = "INSERT INTO `stok_keluar_masuk` (`produk_id`, `jenis`, `jumlah`, `tanggal`, `keterangan`) 
                        VALUES (?, ?, ?, NOW(), ?)";
        $stmt_movement = mysqli_prepare($connection, $sql_movement);
        $keterangan = $type == 'masuk' ? 'Stok masuk' : 'Stok keluar';
        mysqli_stmt_bind_param($stmt_movement, 'isis', $product_id, $type, $quantity, $keterangan);
        mysqli_stmt_execute($stmt_movement);
        mysqli_stmt_close($stmt_movement);
    }

    return $result;
}

/**
 * Check if product has enough stock
 */
function checkProductStock($product_id, $required_quantity)
{
    global $connection;

    $sql = "SELECT `stok` FROM `produk` WHERE `id` = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $current_stock = $row['stok'];
        mysqli_stmt_close($stmt);
        return $current_stock >= $required_quantity;
    }

    mysqli_stmt_close($stmt);
    return false;
}

/**
 * Handle product image upload
 */
function handle_product_image_upload($file)
{
    // Check if file was uploaded
    if (!isset($file['name']) || empty($file['name'])) {
        return ''; // No file uploaded
    }

    $target_dir = __DIR__ . '/../uploads/produk/';

    // Create upload directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'produk_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
    $target_file = $target_dir . $filename;

    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return false; // File too large
    }

    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_types)) {
        return false; // Invalid file type
    }

    // Check if file is actually an image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false; // Not an image
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    }

    return false;
}

/**
 * Handle payment proof image upload
 */
function handle_payment_proof_upload($file)
{
    // Check if file was uploaded
    if (!isset($file['name']) || empty($file['name'])) {
        return ''; // No file uploaded
    }

    $target_dir = __DIR__ . '/../uploads/pembayaran/';

    // Create upload directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'bayar_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
    $target_file = $target_dir . $filename;

    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return false; // File too large
    }

    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($file_extension, $allowed_types)) {
        return false; // Invalid file type
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $filename;
    }

    return false;
}

/**
 * Get notifications for the current user
 */
function get_notifications($user_id, $role)
{
    global $connection;
    $notifications = [];

    if ($role === 'admin') {
        // 1. Pending Orders
        $sql_pending = "SELECT COUNT(*) as total FROM `pesanan` WHERE `status` = 'pending'";
        $result_pending = mysqli_query($connection, $sql_pending);
        $row_pending = mysqli_fetch_assoc($result_pending);
        if ($row_pending['total'] > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'bi-cart',
                'message' => $row_pending['total'] . ' Pesanan Baru (Pending)',
                'link' => base_url('pesanan/index.php?status=pending'),
                'time' => 'Baru'
            ];
        }

        // 2. Low Stock Products (Threshold <= 5)
        $sql_stock = "SELECT COUNT(*) as total FROM `produk` WHERE `stok` <= 5";
        $result_stock = mysqli_query($connection, $sql_stock);
        $row_stock = mysqli_fetch_assoc($result_stock);
        if ($row_stock['total'] > 0) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'bi-exclamation-triangle',
                'message' => $row_stock['total'] . ' Produk Stok Menipis',
                'link' => base_url('produk/index.php?filter=low_stock'),
                'time' => 'Penting'
            ];
        }

        // 3. New Payment Proofs (Status 'pending')
        $sql_payment = "SELECT COUNT(*) as total FROM `pembayaran` WHERE `status` = 'pending'";
        $result_payment = mysqli_query($connection, $sql_payment);
        $row_payment = mysqli_fetch_assoc($result_payment);
        if ($row_payment['total'] > 0) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'bi-receipt',
                'message' => $row_payment['total'] . ' Konfirmasi Pembayaran Baru',
                'link' => base_url('pesanan/index.php?payment_status=pending'), // We might need to handle this filter in index
                'time' => 'Baru'
            ];
        }
    } else {
        // User/Pelanggan Notifications
        // Get customer ID first
        $sql_pel = "SELECT id FROM pelanggan WHERE users_id = ?";
        $stmt_pel = mysqli_prepare($connection, $sql_pel);
        mysqli_stmt_bind_param($stmt_pel, 'i', $user_id);
        mysqli_stmt_execute($stmt_pel);
        $res_pel = mysqli_stmt_get_result($stmt_pel);
        $pelanggan = mysqli_fetch_assoc($res_pel);

        if ($pelanggan) {
            // Get recent order updates
            $sql_orders = "SELECT kode_pesanan, status, tanggal_pesanan FROM pesanan 
                           WHERE pelanggan_id = ? 
                           ORDER BY tanggal_pesanan DESC LIMIT 3";
            $stmt_orders = mysqli_prepare($connection, $sql_orders);
            mysqli_stmt_bind_param($stmt_orders, 'i', $pelanggan['id']);
            mysqli_stmt_execute($stmt_orders);
            $res_orders = mysqli_stmt_get_result($stmt_orders);

            while ($row = mysqli_fetch_assoc($res_orders)) {
                $status_msg = ucfirst($row['status']);
                $icon = 'bi-bag';
                $type = 'primary';

                if ($row['status'] == 'pending') {
                    $type = 'warning';
                    $icon = 'bi-clock';
                } elseif ($row['status'] == 'selesai') {
                    $type = 'success';
                    $icon = 'bi-check-circle';
                } elseif ($row['status'] == 'dibatalkan') {
                    $type = 'danger';
                    $icon = 'bi-x-circle';
                }

                $notifications[] = [
                    'type' => $type,
                    'icon' => $icon,
                    'message' => "Pesanan #{$row['kode_pesanan']} status: $status_msg",
                    'link' => base_url('my_orders.php'),
                    'time' => date('d/m H:i', strtotime($row['tanggal_pesanan']))
                ];
            }
        }
    }

    return $notifications;
}
?>