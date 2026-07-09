# Lorong Kopi — Sistem Informasi Pemesanan Menu Berbasis Web

Sistem pemesanan menu kafe berbasis web menggunakan **framework Laravel** dengan
**QR Code meja**: pelanggan memindai QR di meja, memesan dari HP masing-masing,
dan kasir/admin memproses pesanan secara real-time. Live di
**https://lorongkopi.my.id**.

## Fitur Utama

### Sisi Pelanggan (scan QR meja, tanpa install aplikasi)
- Check-in meja lewat QR Code (nama + no. HP; riwayat pelanggan tersimpan lintas kunjungan)
- Katalog menu per kategori dengan pencarian, foto, dan opsi minuman
  (ukuran Regular/Large, penyajian Dingin/Panas, kadar gula — menu seperti
  Espresso/Americano otomatis tanpa opsi gula)
- Keranjang & checkout (Cash / QRIS), catatan pesanan
- Lacak status pesanan real-time dengan **nomor antrian harian** + batalkan pesanan
- Info WiFi kedai (bisa disalin) muncul otomatis setelah pembayaran terverifikasi

### Sisi Kasir
- Login terpisah dari admin; dashboard antrean pesanan aktif + item terjual hari ini
- **POS (Pesanan Baru)**: input pesanan walk-in di kasir, pilih meja/bawa pulang,
  tandai lunas, tombol besar ramah layar sentuh
- Proses pesanan (ubah status), verifikasi pembayaran, **cetak struk** (dengan
  nomor antrian & info WiFi)
- Notifikasi dering real-time saat pesanan masuk + badge merah jumlah antrean
- Menu Pendapatan (rekap harian), data Pelanggan

### Sisi Admin
- Dashboard statistik + grafik penjualan harian/bulanan (Chart.js)
- CRUD Menu (foto, kategori, opsi tanpa gula), Kategori, **Meja + generate QR Code**
  (cetak semua QR, uji scan kamera)
- Manajemen Pesanan, Pembayaran, Pelanggan, **Akun Kasir**
- Laporan penjualan (harian/mingguan/bulanan/rentang) + export Excel + cetak PDF
- Pengaturan toko (identitas, logo/banner, WiFi kedai)

## Teknologi
| Komponen | Teknologi |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL/MariaDB |
| Frontend | Blade template, Bootstrap 5 (kasir/admin), CSS kustom (pelanggan), Bootstrap Icons |
| Grafik & QR | Chart.js, qrcode.js (generate), jsQR (uji scan) |
| Notifikasi | Polling AJAX + Web Audio API (dering sintesis) |
| Server produksi | Ubuntu + nginx + PHP-FPM, Cloudflare Tunnel |

## Struktur Project
```
lorongkopi-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Site/      # pelanggan: meja (QR check-in), beranda, keranjang,
│   │   │   │              # checkout, pesanan, akun
│   │   │   ├── Kasir/     # auth, dashboard, POS, pesanan, pembayaran,
│   │   │   │              # pendapatan, pelanggan, notifikasi
│   │   │   └── Admin/     # auth, dashboard, menu, kategori, meja+QR, pesanan,
│   │   │                  # pembayaran, pelanggan, akun kasir, laporan, pengaturan
│   │   └── Middleware/    # MejaAktif, KasirAuth, AdminAuth (3 role terpisah)
│   └── Support/helpers.php  # helper global (rupiah, tanggal, badge status,
│                            # nomor pesanan/antrian, keranjang session, dll)
├── resources/views/
│   ├── site/              # halaman pelanggan
│   ├── kasir/             # halaman kasir
│   ├── admin/             # halaman admin
│   └── partials/          # header/footer tiap area
├── routes/web.php         # seluruh route (URL kompatibel dgn QR yang sudah dicetak)
├── public/
│   ├── assets/            # CSS & JS
│   └── uploads/           # foto menu, logo/banner toko, PNG QR meja
└── database.sql           # skema & seed awal
```

## Menjalankan Secara Lokal
```bash
composer install
cp .env.example .env && php artisan key:generate
# atur DB_DATABASE / DB_USERNAME / DB_PASSWORD di .env,
# impor database.sql ke MySQL, lalu:
php artisan serve
```
Akun bawaan: admin `admin/admin123`, kasir `kasir/kasir123` — **wajib diganti**
sebelum dipakai sungguhan (Admin → Akun Kasir / tabel admin).

## Deploy Produksi (ringkas)
1. Upload project (tanpa `.env`), `composer install --no-dev` di server.
2. Buat `.env` produksi (APP_ENV=production, APP_DEBUG=false, kredensial DB server).
3. Arahkan document root nginx ke `public/`, lalu `php artisan config:cache route:cache view:cache`.
4. Pastikan `storage/` & `bootstrap/cache/` writable oleh PHP-FPM.

---

# Kerangka Penulisan Ilmiah (Daftar Isi)

**Judul:** *Pengembangan Sistem Informasi Pemesanan Menu pada Cafe Lorong Kopi
Berbasis Web Menggunakan Framework Laravel dengan QR Code Meja*

## HALAMAN AWAL
- Halaman Judul
- Lembar Pengesahan
- Pernyataan Keaslian
- Abstrak (Indonesia & Inggris) — kata kunci: sistem informasi, pemesanan menu, Laravel, QR Code, web
- Kata Pengantar
- Daftar Isi · Daftar Gambar · Daftar Tabel · Daftar Lampiran

## BAB I — PENDAHULUAN
- 1.1 Latar Belakang
  (antrian & pencatatan manual di kafe, potensi salah pesan, kebutuhan
  pemesanan mandiri dari meja tanpa install aplikasi)
- 1.2 Rumusan Masalah
- 1.3 Batasan Masalah
  (web-based; 3 peran: pelanggan–kasir–admin; pembayaran cash/QRIS
  diverifikasi manual di kasir; satu cabang)
- 1.4 Tujuan Penelitian
- 1.5 Manfaat Penelitian (bagi kafe, pelanggan, akademik)
- 1.6 Sistematika Penulisan

## BAB II — LANDASAN TEORI
- 2.1 Sistem Informasi
- 2.2 Sistem Informasi Pemesanan (e-ordering)
- 2.3 Aplikasi Berbasis Web (arsitektur client–server, HTTP)
- 2.4 Framework Laravel
  - 2.4.1 Arsitektur MVC (Model–View–Controller)
  - 2.4.2 Routing, Middleware, dan Session
  - 2.4.3 Blade Template Engine
- 2.5 QR Code (struktur, cara kerja, pemanfaatan untuk identifikasi meja)
- 2.6 Basis Data MySQL/MariaDB
- 2.7 Bahasa & Teknologi Pendukung (PHP 8, HTML/CSS/JavaScript, Bootstrap 5, AJAX)
- 2.8 Metode Pengembangan Sistem (mis. Waterfall/Prototyping — sesuaikan)
- 2.9 UML (Use Case, Activity, Sequence, Class Diagram) dan ERD
- 2.10 Pengujian Perangkat Lunak (Black-box Testing)
- 2.11 Penelitian Terdahulu yang Relevan

## BAB III — METODOLOGI PENELITIAN
- 3.1 Objek dan Lokasi Penelitian (Cafe Lorong Kopi)
- 3.2 Metode Pengumpulan Data (observasi, wawancara, studi pustaka)
- 3.3 Metode Pengembangan Sistem (tahapan yang dipakai)
- 3.4 Analisis Sistem
  - 3.4.1 Analisis Sistem Berjalan (pemesanan manual)
  - 3.4.2 Analisis Permasalahan
  - 3.4.3 Analisis Kebutuhan Fungsional & Non-Fungsional
- 3.5 Perancangan Sistem
  - 3.5.1 Use Case Diagram (aktor: Pelanggan, Kasir, Admin)
  - 3.5.2 Activity Diagram (scan QR → pesan → proses → bayar → selesai)
  - 3.5.3 Sequence Diagram (check-in QR, checkout, verifikasi bayar, notifikasi)
  - 3.5.4 Class Diagram
  - 3.5.5 ERD / skema basis data
    (tabel: meja, pelanggan, kategori, menu, pesanan, pesanan_item,
    pembayaran, kasir, admin, notifikasi, pengaturan)
  - 3.5.6 Perancangan Antarmuka (mockup pelanggan, kasir, admin)
- 3.6 Perangkat Penelitian (spesifikasi hardware & software)

## BAB IV — HASIL DAN PEMBAHASAN
- 4.1 Implementasi Sistem
  - 4.1.1 Implementasi Basis Data
  - 4.1.2 Struktur Aplikasi Laravel (route, controller, middleware, view)
  - 4.1.3 Implementasi QR Code Meja (generate, cetak, check-in)
  - 4.1.4 Antarmuka Pelanggan (beranda, opsi menu, keranjang, checkout,
    lacak pesanan & antrian, WiFi setelah lunas)
  - 4.1.5 Antarmuka Kasir (dashboard, POS, proses pesanan, verifikasi
    pembayaran, cetak struk, notifikasi dering)
  - 4.1.6 Antarmuka Admin (dashboard & grafik, CRUD menu/kategori/meja,
    akun kasir, laporan & export)
- 4.2 Pengujian Sistem
  - 4.2.1 Pengujian Black-box (tabel skenario per fitur)
  - 4.2.2 Pengujian Kompatibilitas (HP pelanggan, desktop kasir)
  - 4.2.3 (Opsional) Pengujian Penerimaan Pengguna / UAT
- 4.3 Pembahasan (kelebihan dan keterbatasan sistem)

## BAB V — PENUTUP
- 5.1 Kesimpulan
- 5.2 Saran (integrasi payment gateway QRIS otomatis, aplikasi dapur/KDS,
  multi-cabang, laporan lanjutan)

## DAFTAR PUSTAKA

## LAMPIRAN
- Kode program inti (route, controller utama)
- Tampilan aplikasi (screenshot)
- Hasil pengujian black-box
- Dokumentasi wawancara/observasi
