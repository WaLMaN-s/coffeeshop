<?php
/**
 * Konfigurasi utama aplikasi.
 *
 * LOKAL (XAMPP/lampp): host=localhost, user=root, pass kosong.
 * INFINITYFREE: ganti 4 konstanta DB_* sesuai akun (contoh:
 *   host=sqlXXX.infinityfree.com, user=ifX_XXXX, db=ifX_XXXX_lorongkopi).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'lorongkopi');
define('DB_PASS', 'lorongkopi123');
define('DB_NAME', 'lorongkopi_db');

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}

/* ---------- Helper umum ---------- */

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function tanggal_id(?string $dt, bool $jam = false): string
{
    if (!$dt) return '-';
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($dt);
    $s = date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $jam ? $s . ', ' . date('H:i', $t) : $s;
}

function get_pengaturan(PDO $db): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = $db->query('SELECT * FROM pengaturan WHERE id = 1')->fetch() ?: [];
    }
    return $cache;
}

function set_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function tambah_notifikasi(PDO $db, string $tipe, string $pesan, ?int $pesananId = null): void
{
    $stmt = $db->prepare('INSERT INTO notifikasi (tipe, pesan, pesanan_id) VALUES (?, ?, ?)');
    $stmt->execute([$tipe, $pesan, $pesananId]);
}

/**
 * Upload gambar dari $_FILES[$field] ke uploads/<folder>/.
 * Mengembalikan nama file baru, atau null jika tidak ada file / gagal validasi.
 */
function upload_gambar(string $field, string $folder, ?string &$error = null): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload gagal (kode ' . $file['error'] . ').';
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Ukuran gambar maksimal 2 MB.';
        return null;
    }
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $izin    = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $izin, true)) {
        $error = 'Format harus JPG, PNG, atau WEBP.';
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        $error = 'File bukan gambar yang valid.';
        return null;
    }
    $namaBaru = $folder . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $tujuan   = dirname(__DIR__) . '/uploads/' . $folder . '/' . $namaBaru;
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        $error = 'Gagal menyimpan file.';
        return null;
    }
    return $namaBaru;
}

function hapus_gambar(?string $file, string $folder): void
{
    if ($file) {
        $path = dirname(__DIR__) . '/uploads/' . $folder . '/' . $file;
        if (is_file($path)) @unlink($path);
    }
}

/* ---------- Label & badge status ---------- */

function label_status_pesanan(string $s): string
{
    $map = [
        'menunggu'   => 'Menunggu',
        'diproses'   => 'Diproses',
        'siap'       => 'Siap Diambil',
        'selesai'    => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];
    return $map[$s] ?? $s;
}

function badge_status_pesanan(string $s): string
{
    $map = [
        'menunggu'   => 'badge-menunggu',
        'diproses'   => 'badge-diproses',
        'siap'       => 'badge-siap',
        'selesai'    => 'badge-selesai',
        'dibatalkan' => 'badge-batal',
    ];
    $cls = $map[$s] ?? 'badge-menunggu';
    return '<span class="badge-status ' . $cls . '">' . label_status_pesanan($s) . '</span>';
}

function label_status_bayar(string $s): string
{
    $map = [
        'belum_dibayar' => 'Belum Dibayar',
        'sudah_dibayar' => 'Sudah Dibayar',
        'gagal'         => 'Gagal',
    ];
    return $map[$s] ?? $s;
}

function badge_status_bayar(string $s): string
{
    $map = [
        'belum_dibayar' => 'badge-menunggu',
        'sudah_dibayar' => 'badge-selesai',
        'gagal'         => 'badge-batal',
    ];
    $cls = $map[$s] ?? 'badge-menunggu';
    return '<span class="badge-status ' . $cls . '">' . label_status_bayar($s) . '</span>';
}

function buat_nomor_pesanan(PDO $db): string
{
    $prefix = 'ORD-' . date('Ymd') . '-';
    $stmt = $db->prepare("SELECT COUNT(*) FROM pesanan WHERE nomor_pesanan LIKE ?");
    $stmt->execute([$prefix . '%']);
    return $prefix . str_pad((string) ($stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
}
