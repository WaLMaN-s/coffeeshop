# ☕ Lorong Kopi — Sistem Pemesanan Coffee Shop

Web pemesanan untuk coffee shop **Lorong Kopi** (Jl. Margasatwa No.9, Cilandak
Timur, Jakarta Selatan). PHP murni + MariaDB/MySQL, tanpa framework — jalan di
XAMPP mana pun.

**Fitur pelanggan** (mobile-first): katalog menu + kategori + pencarian,
opsi pesanan ala Kopi Kenangan (Regular/Large, Dingin/Panas, kadar gula),
keranjang, checkout Cash/QRIS, lacak status pesanan, batalkan pesanan, akun.

**Fitur admin**: dashboard statistik + grafik penjualan, kelola menu/kategori/
pesanan/pembayaran/pelanggan, laporan (cetak PDF & export Excel), pengaturan
toko, notifikasi pesanan baru.

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
Supaya dashboard & laporan langsung hidup (8 pelanggan + ±400 pesanan
90 hari):
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
| Pelanggan | http://localhost/lorongkopi/ |
| Login (admin & pelanggan satu pintu) | http://localhost/lorongkopi/masuk.php |

**Akun bawaan:**
| Peran | Login | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Pelanggan contoh (dari seed) | `budi@gmail.com` | `pelanggan123` |

> Supaya bisa dibuka dari HP satu WiFi: cek IP laptop (`ipconfig` /
> `hostname -I`), lalu buka `http://IP-LAPTOP/lorongkopi/` dari HP.

---

## 🌐 Deploy ke Hosting Online (InfinityFree)
Langkah lengkap ada di **[DEPLOY.md](DEPLOY.md)** — daftar akun gratis, buat
database, import `database.sql` lewat phpMyAdmin, upload file, ganti 4 baris
di `config/config.php`.

## 🗂 Struktur Singkat
```
├── index.php, keranjang.php, checkout.php, ...   # halaman pelanggan
├── masuk.php / daftar.php                        # login satu pintu & register
├── admin/                                        # panel admin
├── includes/                                     # layout & init pelanggan
├── config/config.php                             # koneksi DB (edit di sini)
├── assets/css/                                   # tema situs & admin
├── uploads/menu/, uploads/toko/                  # foto menu & logo/banner
├── database.sql                                  # skema + seed dasar
├── seed.php                                      # data contoh (opsional, CLI)
└── DEPLOY.md                                     # panduan hosting online
```

## ⚠️ Catatan
- Ganti password admin sebelum online (tabel `admin`, pakai `password_hash`).
- `uploads/` harus bisa ditulis server (di Linux: `chmod -R 775 uploads`).
- `ambil_foto.php` hanya alat sekali pakai untuk mengunduh foto menu — tidak
  perlu dijalankan lagi.
