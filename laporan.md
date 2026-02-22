# Laporan Proyek Aplikasi Toko Online

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang
Perkembangan teknologi informasi yang pesat telah mengubah cara masyarakat berbelanja. *E-commerce* atau toko online menjadi salah satu solusi utama bagi pelaku bisnis untuk menjangkau pasar yang lebih luas tanpa batasan geografis. Sistem manajemen toko konvensional seringkali mengalami kendala dalam pencatatan stok, pengelolaan pesanan, dan rekapitulasi penjualan yang masih manual.

Aplikasi **Toko Online** ini dikembangkan untuk menjawab kebutuhan tersebut. Aplikasi ini dirancang untuk mempermudah pengelolaan data produk, supplier, pelanggan, dan transaksi penjualan secara terintegrasi. Dengan adanya sistem ini, diharapkan efisiensi operasional toko dapat meningkat dan risiko kesalahan data dapat diminimalisir.

### 1.2 Rumusan Masalah
Berdasarkan latar belakang di atas, rumusan masalah dalam pengembangan aplikasi ini adalah:
1.  Bagaimana merancang basis data yang mampu menangani relasi antara produk, supplier, dan transaksi?
2.  Bagaimana memastikan keamanan dan kebenaran logika sistem melalui pengujian *Whitebox*?
3.  Bagaimana memastikan fungsionalitas sistem berjalan sesuai kebutuhan pengguna melalui pengujian *Blackbox*?

### 1.3 Tujuan
Tujuan dari pembuatan laporan dan aplikasi ini adalah:
1.  Menyediakan sistem informasi toko online yang handal.
2.  Mendokumentasikan struktur database yang digunakan.
3.  Melakukan pengujian sistem menggunakan metode *Whitebox* dan *Blackbox* Testing untuk menjamin kualitas perangkat lunak.

---

## BAB II: LANDASAN TEORI

### 2.1 E-Commerce
Elektronik Commerce (*E-Commerce*) adalah penyebaran, pembelian, penjualan, pemasaran barang dan jasa melalui sistem elektronik seperti internet atau televisi, www, atau jaringan komputer lainnya.

### 2.2 Whitebox Testing
*Whitebox Testing* adalah metode pengujian perangkat lunak di mana struktur internal, desain, dan implementasi item yang sedang diuji diketahui oleh penguji. Tujuannya adalah untuk memverifikasi aliran input-output melalui aplikasi, meningkatkan desain dan kegunaan, serta memperkuat keamanan.
*   **Basis Path Testing**: Teknik analisis yang menjamin setiap jalur independen dalam modul program telah dieksekusi setidaknya satu kali.
*   **Cyclomatic Complexity**: Metrik perangkat lunak yang digunakan untuk menunjukkan kompleksitas program.

### 2.3 Blackbox Testing
*Blackbox Testing* adalah metode pengujian fungsionalitas perangkat lunak tanpa mengetahui struktur kode internalnya. Penguji hanya berfokus pada apa yang dilakukan oleh sistem, bukan bagaimana sistem melakukannya. Teknik umum meliputi *Equivalence Partitioning* dan *Boundary Value Analysis*.

---

## BAB III: PERANCANGAN DATABASE

Aplikasi ini menggunakan database MySQL dengan nama `toko_online1`. Database ini terdiri dari tabel-tabel yang saling berelasi untuk mendukung integritas data.

### 3.1 Struktur Tabel Utama

Berikut adalah deskripsi tabel-tabel utama dalam sistem:

1.  **Tabel `users`**
    *   Tabel ini menyimpan data pengguna untuk autentikasi sistem.
    *   Kolom: `id` (PK), `username`, `password`, `role` (admin, mahasiswa, supplier, pelanggan).

2.  **Tabel `kategori_produk`**
    *   Menyimpan pengelompokan produk.
    *   Kolom: `id` (PK), `kode_kategori` (Unique), `nama_kategori`.

3.  **Tabel `produk`**
    *   Menyimpan informasi barang yang dijual.
    *   Kolom: `id` (PK), `kode_produk` (Unique), `nama_produk`, `harga`, `stok`, `kategori_id` (FK), `supplier_id` (FK).
    *   Relasi: Berelasi dengan `kategori_produk` dan `supplier`.

4.  **Tabel `pesanan`**
    *   Menyimpan data transaksi pemesanan (header).
    *   Kolom: `id` (PK), `kode_pesanan`, `tanggal_pesanan`, `pelanggan_id` (FK), `total_harga`, `status`.

5.  **Tabel `detail_pesanan`**
    *   Menyimpan rincian item barang dalam setiap pesanan.
    *   Kolom: `id` (PK), `pesanan_id` (FK), `produk_id` (FK), `jumlah`, `subtotal`.

---

## BAB IV: HASIL DAN PEMBAHASAN

Bab ini membahas implementasi pengujian sistem menggunakan metode *Whitebox* dan *Blackbox*.

### 4.1 Whitebox Testing
Pengujian *Whitebox* difokuskan pada logika autentikasi pada file `login.php`.

#### 4.1.1 Flowgraph Logika Login
Berikut adalah representasi alur logika (Control Flow) dari proses login:

1.  **Start**
2.  **Cek Session**: Apakah user sudah login?
    *   *True (Yes)* -> **Node 3**: Redirect ke dashboard sesuai role.
    *   *False (No)* -> **Node 4**: Lanjut ke form.
3.  **Cek Request Method**: Apakah method == POST?
    *   *True (Yes)* -> **Node 5**: Validasi CSRF Token.
    *   *False (No)* -> **Node 10**: Tampilkan Form Login (Selesai).
4.  **Validasi CSRF**: Apakah Token Valid?
    *   *False (No)* -> **Node 6**: Die ("Invalid request").
    *   *True (Yes)* -> **Node 7**: Ambil Username & Password.
5.  **Cek Input**: Apakah Username/Password kosong?
    *   *True (Yes)* -> **Node 8**: Set Error "Semua kolom wajib diisi". -> Lanjut ke Node 10.
    *   *False (No)* -> **Node 9**: Cek kredensial ke Database.
6.  **Verifikasi Login**: Apakah User Valid?
    *   *True (Yes)* -> **Node 11**: Login Sukses -> Set Session -> Redirect.
    *   *False (No)* -> **Node 12**: Set Error "Username atau password salah". -> Lanjut ke Node 10.

#### 4.1.2 Perhitungan Cyclomatic Complexity
Rumus: $V(G) = E - N + 2$ atau $V(G) = P + 1$ (dimana P adalah jumlah predikat/kondisi).

Dari kode `login.php`, kita memiliki kondisi utama (Predikat):
1.  `if (isset($_SESSION['user_id']))`
2.  `if ($_SERVER['REQUEST_METHOD'] === 'POST')`
3.  `if (!validateCSRFToken(...))`
4.  `if (empty($username) || empty($password))`
5.  `if ($role)` (Hasil fungsi login)

Kompeksitas Siklomatik = 5 + 1 = **6**.
Ini menunjukkan bahwa logika login memiliki kompleksitas rendah dan mudah dipelihara, namun membutuhkan minimal 6 skenario uji/path untuk mencakup semua kemungkinan eksekusi.

### 4.2 Blackbox Testing
Pengujian *Blackbox* dilakukan untuk memastikan fitur berjalan sesuai input yang diberikan tanpa melihat kode.

#### 4.2.1 Skenario Pengujian Login

| No | Skenario Uji | Input Data | Yang Diharapkan | Hasil Pengujian | Kesimpulan |
|----|--------------|------------|-----------------|-----------------|------------|
| 1  | Login tanpa input | Username: [Kosong]<br>Password: [Kosong] | Sistem menolak dan menampilkan pesan "Semua kolom wajib diisi". | Muncul pesan error sesuai harapan. | **Valid** |
| 2  | Login dengan password salah | Username: admin<br>Password: salah123 | Sistem menolak dan menampilkan pesan "Username atau password salah". | Muncul pesan error sesuai harapan. | **Valid** |
| 3  | Login Sukses (Admin) | Username: admin<br>Password: admin | Sistem menerima dan mengarahkan ke Dashboard Admin. | Berhasil masuk ke Dashboard. | **Valid** |
| 4  | Login SQL Injection | Username: `' OR '1'='1`<br>Password: acak | Sistem menolak input (karena menggunakan Prepared Statement). | Gagal login, pesan "Username atau password salah". | **Valid** |

#### 4.2.2 Skenario Pengujian Manajemen Produk (CRUD)

| No | Skenario Uji | Input Data | Yang Diharapkan | Hasil Pengujian | Kesimpulan |
|----|--------------|------------|-----------------|-----------------|------------|
| 1  | Tambah Produk Valid | Kode: PRD999, Nama: Tes Produk, Harga: 10000 | Data tersimpan di database dan muncul di tabel produk. | Data tersimpan. | **Valid** |
| 2  | Tambah Produk Duplikat | Kode: PRD999 (yang sudah ada) | Sistem menolak penyimpanan karena Kode Produk harus Unik. | Muncul error duplicate entry. | **Valid** |
| 3  | Edit Harga Produk | Mengubah harga PRD999 menjadi 15000 | Data harga terupdate di database. | Harga berubah. | **Valid** |
| 4  | Hapus Produk | Menghapus PRD999 | Data hilang dari tabel produk. | Data terhapus. | **Valid** |

---

## BAB V: KESIMPULAN

Berdasarkan hasil perancangan dan pengujian yang telah dilakukan, dapat disimpulkan bahwa:

1.  Aplikasi Toko Online telah berhasil dibangun dengan struktur database yang relasional dan ternormalisasi, mendukung integritas data transaksi.
2.  Pengujian *Whitebox* pada modul Login menunjukkan alur logika yang efisien dengan *Cyclomatic Complexity* sebesar 6, yang berarti kode cukup sederhana dan mudah diuji.
3.  Pengujian *Blackbox* menunjukkan bahwa fitur-fitur utama seperti Login dan CRUD Produk berjalan sesuai dengan spesifikasi kebutuhan fungsional dan mampu menangani kesalahan input pengguna.

Secara keseluruhan, sistem ini siap digunakan untuk membantu operasional Toko Online dalam mengelola inventaris dan transaksi penjualan.
