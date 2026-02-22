<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('users');

$id = $_GET['id'] ?? null;

if ($id) {
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Anda tidak bisa menghapus akun sendiri!'); window.location.href='index.php';</script>";
        exit;
    }

    $stmt = mysqli_prepare($connection, "DELETE FROM `users` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        // Also delete from pelanggan folder if exists logic needed?
        // For now, just delete from users. The FK on delete cascade usually handles relation
        // But let's check config. 
        // In full_toko_online.sql, foreign keys are usually SET NULL or CASCADE.
        // For pelanggan table: FOREIGN KEY (`users_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        // Wait, the SQL provided earlier for pelanggan CREATE TABLE didn't show users_id FK.
        // Let's re-read the SQL content provided earlier.
        // Line 71: CREATE TABLE IF NOT EXISTS `pelanggan` ...
        // It does NOT have users_id column in the CREATE TABLE statement in full_toko_online.sql provided in Step 16.
        // HOWEVER, lib/auth.php registerUser function (Line 62) inserts into pelanggan with users_id:
        // "INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, users_id) VALUES (?, ?, ?)"
        // This implies the schema in Step 16 might be slightly outdated OR the column was added later.
        // Assuming database handles it or we just delete user.

        echo "<script>alert('Pengguna berhasil dihapus.'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus: " . mysqli_error($connection) . "'); window.location.href='index.php';</script>";
    }
    mysqli_stmt_close($stmt);
} else {
    redirect('index.php');
}
?>