# ☕ Lorong Kopi — Sistem Pemesanan Coffee Shop

Web pemesanan untuk coffee shop **Lorong Kopi** (Jl. Margasatwa No.9, Cilandak
Timur, Jakarta Selatan). PHP murni + MariaDB/MySQL, tanpa framework — jalan di
XAMPP mana pun.

**Cara pesan**: pelanggan **scan QR code yang ada di meja**, isi nama, lalu
langsung pesan dari HP-nya — tanpa akun/password. Pesanan otomatis tertaut
ke nomor meja & nama tamu, jadi staf tahu persis pesanan itu untuk meja mana.

**Fitur pelanggan** (mobile-first): katalog menu + kategori + pencarian,
opsi pesanan ala Kopi Kenangan (Regular/Large, Dingin/Panas, kadar gula),
keranjang, checkout Cash/QRIS, lacak status pesanan, batalkan pesanan.

**Fitur admin**: dashboard statistik + grafik penjualan, kelola menu/kategori/
**meja & QR code** (generate, unduh PNG, cetak semua sekaligus)/pesanan/
pembayaran/pelanggan, laporan (cetak PDF & export Excel), pengaturan toko,
notifikasi pesanan baru.

---

## 🚀 Cara Menjalankan di Laptop Lain (clone)

### 1. Yang perlu di-install
- **XAMPP** (PHP 8.x + MySQL/MariaDB) — https://www.apachefriends.org
- **Git**

### 2. Clone ke folder web XAMPP
```bash
# Windows (XAMPP default):
cd C:\xampp\htdocs
git clone https://github.com/WaLMaN-s/coffeeshop.git lorongkopi

# Linux:
cd /opt/lampp/htdocs
sudo git clone https://github.com/WaLMaN-s/coffeeshop.git lorongkopi
```

### 3. Buat database
1. Nyalakan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Buka http://localhost/phpmyadmin → tab **Import** → pilih file
   `database.sql` dari folder proyek → **Go**.
   (File ini otomatis membuat database `lorongkopi_db` beserta menu,
   kategori, dan akun admin.)

### 4. Sesuaikan koneksi database
Edit `config/config.php` bagian atas. Untuk XAMPP standar:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default: kosong
define('DB_NAME', 'lorongkopi_db');
```

### 5. (Opsional) Isi data contoh
Supaya dashboard & laporan langsung hidup (±400 pesanan riwayat 90 hari):
```bash
# Windows:
C:\xampp\php\php.exe seed.php
# Linux:
/opt/lampp/bin/php seed.php
```
Foto menu sudah ikut ter-clone di `uploads/menu/`, tidak perlu diunduh ulang.

### 6. Buka webnya
| Halaman | URL |
|---|---|
| Login admin | http://localhost/lorongkopi/admin/login.php |
| Pesan (simulasi scan meja) | http://localhost/lorongkopi/meja.php?kode=... |

**Akun admin bawaan:** `admin` / `admin123` — **ganti passwordnya sebelum online.**

Database sudah terisi **10 meja siap pakai** (lihat tabel `meja`). Setelah
login admin, buka menu **Meja & QR** — QR code tiap meja otomatis dibuat &
disimpan ke `uploads/qrcode/`, tinggal klik **Unduh** per meja atau
**Cetak Semua QR** untuk ditempel di meja fisik. Setiap QR mengarah ke
`meja.php?kode=...` sesuai domain tempat web di-hosting (otomatis
menyesuaikan, tidak perlu diedit manual).

> Supaya bisa dites dari HP satu WiFi: cek IP laptop (`ipconfig` /
> `hostname -I`), buka **Meja & QR** dari HP itu (ganti `localhost` dengan
> IP laptop), lalu scan QR-nya langsung dari layar HP lain / kertas cetak.

---

## 🌐 Deploy ke Hosting Online (InfinityFree)
Langkah lengkap ada di **[DEPLOY.md](DEPLOY.md)** — daftar akun gratis, buat
database, import `database.sql` lewat phpMyAdmin, upload file, ganti 4 baris
di `config/config.php`.

## 🗂 Struktur Singkat
```
├── meja.php                                      # scan QR → isi nama → sesi meja
├── index.php, keranjang.php, checkout.php, ...   # halaman pelanggan (butuh sesi meja)
├── admin/meja.php, admin/meja_cetak.php          # kelola meja + generate/cetak QR
├── admin/login.php                               # login admin (terpisah dari pelanggan)
├── admin/                                        # panel admin lainnya
├── includes/                                     # layout & init pelanggan
├── config/config.php                             # koneksi DB (edit di sini)
├── assets/css/, assets/js/qrcode.min.js          # tema situs/admin & generator QR
├── uploads/menu/, uploads/toko/, uploads/qrcode/ # foto menu, logo/banner, QR meja
├── database.sql                                  # skema + seed dasar + 10 meja
├── seed.php                                      # data contoh (opsional, CLI)
└── DEPLOY.md                                     # panduan hosting online
```

## ⚠️ Catatan
- Ganti password admin sebelum online (tabel `admin`, pakai `password_hash`).
- `uploads/` (termasuk `uploads/qrcode/`) harus bisa ditulis server
  (di Linux: `chmod -R 775 uploads`).
- Pelanggan **wajib** scan QR meja dulu untuk bisa memesan — tidak ada lagi
  akun email/password untuk pelanggan. Kalau meja dinonaktifkan atau QR
  diperbarui (tombol ↻ di **Meja & QR**), QR lama otomatis tidak berlaku.
- `ambil_foto.php` hanya alat sekali pakai untuk mengunduh foto menu — tidak
  perlu dijalankan lagi.
