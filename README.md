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

