-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Feb 2026 pada 20.13
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_online1`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `produk_id`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 2, 1, 12000000.00, 12000000.00),
(2, 2, 2, 1, 12000000.00, 12000000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `id` int(11) NOT NULL,
  `kode_kategori` varchar(20) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori_produk`
--

INSERT INTO `kategori_produk` (`id`, `kode_kategori`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
(1, 'KAT001', 'Elektronik', 'Peralatan elektronik dan gadget', '2026-02-14 03:53:11'),
(2, 'KAT002', 'Pakaian', 'Pakaian pria, wanita, dan anak-anak', '2026-02-14 03:53:11'),
(3, 'KAT003', 'Makanan & Minuman', 'Produk makanan dan minuman', '2026-02-14 03:53:11'),
(4, 'KAT004', 'Kosmetik', 'Produk kecantikan dan perawatan', '2026-02-14 03:53:11'),
(5, 'KAT005', 'Alat Tulis', 'Perlengkapan kantor dan sekolah', '2026-02-14 03:53:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `users_id` int(11) DEFAULT NULL,
  `kode_pelanggan` varchar(20) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `users_id`, `kode_pelanggan`, `nama_pelanggan`, `alamat`, `telepon`, `email`, `created_at`) VALUES
(1, NULL, 'PLG001', 'Budi Santoso', 'Jl. Merdeka No. 10, Jakarta', '08123456789', 'budi@email.com', '2026-02-14 04:01:24'),
(2, NULL, 'PLG002', 'Siti Nurhaliza', 'Jl. Sudirman No. 25, Bandung', '08567891234', 'siti@email.com', '2026-02-14 04:01:24'),
(3, 9, 'PEL-20260221140725-8', 'Riyan', NULL, NULL, NULL, '2026-02-21 07:07:25'),
(4, 10, 'PEL-20260221140742-4', 'Andhika', 'dsfgkjhgdasdfgh', '1234567', NULL, '2026-02-21 07:07:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `kode_pembayaran` varchar(20) NOT NULL,
  `pesanan_id` int(11) DEFAULT NULL,
  `tanggal_bayar` datetime NOT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metode_bayar` varchar(50) DEFAULT NULL,
  `nama_pengirim` varchar(100) DEFAULT NULL,
  `bank_pengirim` varchar(50) DEFAULT NULL,
  `nomor_rekening_pengirim` varchar(50) DEFAULT NULL,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `status` enum('pending','dikonfirmasi','ditolak','menunggu_bukti') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `kode_pembayaran`, `pesanan_id`, `tanggal_bayar`, `jumlah_bayar`, `metode_bayar`, `nama_pengirim`, `bank_pengirim`, `nomor_rekening_pengirim`, `bukti_bayar`, `status`, `created_at`) VALUES
(1, 'BYR001', 1, '0000-00-00 00:00:00', 12000000.00, 'Transfer', 'MIDOO', 'BCA', '1234567890', NULL, 'menunggu_bukti', '2026-02-22 19:01:03'),
(2, 'BYR002', 2, '0000-00-00 00:00:00', 12000000.00, 'Transfer', 'Andhika', 'BCA', '67357869054736458', NULL, 'dikonfirmasi', '2026-02-22 19:05:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `kode_pesanan` varchar(20) NOT NULL,
  `tanggal_pesanan` datetime NOT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `kode_pesanan`, `tanggal_pesanan`, `pelanggan_id`, `total_harga`, `status`, `metode_pembayaran`, `catatan`, `created_at`) VALUES
(1, 'ORD-20260221143557-5', '2026-02-21 14:35:57', 4, 12000000.00, 'pending', 'transfer', 'dsafgfjfhdsfdasdsdsgd', '2026-02-21 07:35:57'),
(2, 'ORD-20260223020538-3', '2026-02-23 02:05:38', 4, 12000000.00, 'diproses', 'transfer', '', '2026-02-22 19:05:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `kode_produk` varchar(20) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `satuan` varchar(20) DEFAULT 'pcs',
  `deskripsi` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `kode_produk`, `nama_produk`, `kategori_id`, `harga`, `stok`, `satuan`, `deskripsi`, `photo`, `supplier_id`, `created_at`) VALUES
(1, 'PRD001', 'Smartphone Android X1', 1, 3500000.00, 50, 'pcs', 'Smartphone dengan RAM 8GB dan ROM 128GB', NULL, 1, '2026-02-14 03:55:15'),
(2, 'PRD002', 'Laptop Gaming Y2', 1, 12000000.00, 18, 'pcs', 'Laptop untuk gaming dengan VGA dedicated', NULL, 1, '2026-02-14 03:55:15'),
(3, 'PRD003', 'Kaos Polos Hitam', 2, 50000.00, 200, 'pcs', 'Kaos katun combed 30s', NULL, 2, '2026-02-14 03:55:15'),
(4, 'PRD004', 'Celana Jeans Pria', 2, 150000.00, 100, 'pcs', 'Celana jeans premium', NULL, 2, '2026-02-14 03:55:15'),
(5, 'PRD005', 'Kopi Arabica 100g', 3, 35000.00, 500, 'pcs', 'Kopi arabica pilihan', NULL, 3, '2026-02-14 03:55:15'),
(7, 'PRD006', 'Meja Kayu Jati', 5, 1234567.00, 12, 'meter', '', '', NULL, '2026-02-22 18:50:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_keluar_masuk`
--

CREATE TABLE `stok_keluar_masuk` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` datetime NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stok_keluar_masuk`
--

INSERT INTO `stok_keluar_masuk` (`id`, `produk_id`, `jenis`, `jumlah`, `tanggal`, `keterangan`, `created_at`) VALUES
(1, 2, 'keluar', 1, '2026-02-21 14:35:57', 'Stok keluar', '2026-02-21 07:35:57'),
(2, 2, 'keluar', 1, '2026-02-23 02:05:38', 'Stok keluar', '2026-02-22 19:05:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier`
--

CREATE TABLE `supplier` (
  `id` int(11) NOT NULL,
  `users_id` int(11) DEFAULT NULL,
  `kode_supplier` varchar(20) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `supplier`
--

INSERT INTO `supplier` (`id`, `users_id`, `kode_supplier`, `nama_supplier`, `alamat`, `telepon`, `email`, `created_at`) VALUES
(1, NULL, 'SUP001', 'PT Elektronik Indonesia', 'Jakarta Pusat', '021-12345678', 'info@elektronik.co.id', '2026-02-14 03:53:11'),
(2, NULL, 'SUP002', 'CV Fashion Jogja', 'Yogyakarta', '0274-987654', 'fashion@jogja.com', '2026-02-14 03:53:11'),
(3, NULL, 'SUP003', 'Toko Grosir Makanan', 'Surabaya', '031-555666', 'grosir@makanan.com', '2026-02-14 03:53:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_supplier`
--

CREATE TABLE `transaksi_supplier` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `tanggal` datetime NOT NULL,
  `total_pendapatan` double NOT NULL DEFAULT 0,
  `status` enum('pending','paid_to_supplier') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi_supplier`
--

INSERT INTO `transaksi_supplier` (`id`, `supplier_id`, `pesanan_id`, `tanggal`, `total_pendapatan`, `status`) VALUES
(1, 1, 2, '2026-02-23 02:08:04', 12000000, 'pending');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pelanggan','supplier','mahasiswa') NOT NULL DEFAULT 'pelanggan',
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `photo`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL),
(4, 'admin1', '$2a$12$ArP/1Z5kJ5TSXwNMXaz..eWXuXzlLhg7bocU.PJX/keGJZrC4ykK.', 'admin', NULL),
(6, 'admin2', '$2a$12$ArP/1Z5kJ5TSXwNMXaz..eWXuXzlLhg7bocU.PJX/keGJZrC4ykK.', 'admin', NULL),
(9, 'Riyan', '$2y$10$71wf9dgI6UdARj61rLIyJumCGHhdzrhgZl.qMuo5Ddr8qhf/jvgam', 'pelanggan', NULL),
(10, 'Andhika', '$2y$10$PpwkE/u142Uym.FCO2cZnuzdr2JOJ0cj3zAjOjuvR.fwDExtOb48C', 'pelanggan', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pesanan` (`pesanan_id`),
  ADD KEY `idx_produk` (`produk_id`);

--
-- Indeks untuk tabel `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kategori` (`kode_kategori`),
  ADD KEY `idx_kode_kategori` (`kode_kategori`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pelanggan` (`kode_pelanggan`),
  ADD KEY `idx_kode_pelanggan` (`kode_pelanggan`),
  ADD KEY `idx_users_id` (`users_id`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pembayaran` (`kode_pembayaran`),
  ADD KEY `idx_kode_pembayaran` (`kode_pembayaran`),
  ADD KEY `idx_pesanan` (`pesanan_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `idx_kode_pesanan` (`kode_pesanan`),
  ADD KEY `idx_pelanggan` (`pelanggan_id`),
  ADD KEY `idx_tanggal` (`tanggal_pesanan`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`),
  ADD KEY `idx_kode_produk` (`kode_produk`),
  ADD KEY `idx_kategori` (`kategori_id`),
  ADD KEY `idx_supplier` (`supplier_id`);

--
-- Indeks untuk tabel `stok_keluar_masuk`
--
ALTER TABLE `stok_keluar_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produk` (`produk_id`),
  ADD KEY `idx_jenis` (`jenis`),
  ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indeks untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_supplier` (`kode_supplier`),
  ADD KEY `idx_kode_supplier` (`kode_supplier`),
  ADD KEY `fk_supplier_users` (`users_id`);

--
-- Indeks untuk tabel `transaksi_supplier`
--
ALTER TABLE `transaksi_supplier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `pesanan_id` (`pesanan_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `stok_keluar_masuk`
--
ALTER TABLE `stok_keluar_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `transaksi_supplier`
--
ALTER TABLE `transaksi_supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD CONSTRAINT `pelanggan_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_produk` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `stok_keluar_masuk`
--
ALTER TABLE `stok_keluar_masuk`
  ADD CONSTRAINT `stok_keluar_masuk_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD CONSTRAINT `fk_supplier_users` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
