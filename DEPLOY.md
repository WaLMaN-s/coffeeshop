# Cara Deploy ke InfinityFree (Hosting Gratis)

Web ini sudah siap online. Ikuti langkah berikut:

## 1. Daftar akun
1. Buka https://www.infinityfree.com dan daftar (gratis).
2. Buat akun hosting baru — pilih subdomain gratis (misal `kedaikopikita.rf.gd`)
   atau pakai domain sendiri.

## 2. Buat database MySQL
1. Masuk **Control Panel** akun hosting → **MySQL Databases**.
2. Buat database baru (misal `lorongkopi`). Catat yang muncul:
   - **MySQL Host** (contoh: `sql123.infinityfree.com`)
   - **Nama database** (contoh: `if0_12345678_lorongkopi`)
   - **Username** (contoh: `if0_12345678`)
   - **Password** = password vPanel akun kamu.

## 3. Import database
1. Di Control Panel buka **phpMyAdmin** untuk database tadi.
2. Tab **Import** → pilih file `database.sql`.
   **PENTING:** hapus dulu 2 baris paling atas file
   (`CREATE DATABASE ...` dan `USE lorongkopi_db;`) karena InfinityFree
   hanya mengizinkan database yang dibuat lewat panel.
3. Klik **Go** sampai selesai.

## 4. Upload file
1. Buka **File Manager** (atau pakai FTP/FileZilla, data FTP ada di Control Panel).
2. Masuk folder `htdocs`, hapus file bawaan.
3. Upload SEMUA isi folder proyek ini (config, admin, assets, uploads,
   index.php, dst) ke dalam `htdocs`.

## 5. Sesuaikan config
Edit `config/config.php` (bisa langsung dari File Manager), ganti:

```php
define('DB_HOST', 'sql123.infinityfree.com'); // MySQL Host dari panel
define('DB_USER', 'if0_12345678');            // username
define('DB_PASS', 'passwordvpanelkamu');      // password vPanel
define('DB_NAME', 'if0_12345678_lorongkopi');      // nama database
```

## 6. Selesai
- Buka `https://namadomainkamu.rf.gd/admin/` → login **admin / admin123**.
- Segera ganti password admin di database (tabel `admin`) atau minta
  dibuatkan halaman ganti password.
- Data contoh (seed) bisa dipakai untuk demo; kalau mau mulai bersih,
  kosongkan tabel `pesanan`, `pesanan_item`, `pembayaran`, `pelanggan`,
  `notifikasi` lewat phpMyAdmin.

## Catatan lokal (perangkat ini)
- Source code: `/home/MaN/WEB/lorongkopi` (master).
- Salinan yang di-serve Apache lampp: `/opt/lampp/htdocs/lorongkopi`.
  Setelah mengubah source, sinkronkan dengan:
  `pkexec cp -r /home/MaN/WEB/lorongkopi/. /opt/lampp/htdocs/lorongkopi/`
- Database lokal: MariaDB sistem (bukan MySQL lampp), user `lorongkopi` /
  `lorongkopi123`, database `lorongkopi_db`, host `127.0.0.1`.
