<?php
/**
 * Helper global — port 1:1 dari config/config.php, config/fungsi.php, dan
 * includes/site_init.php versi non-framework, di atas session & koneksi DB
 * milik Laravel. Nama fungsi dipertahankan supaya view lama bisa dipakai apa
 * adanya. e() tidak didefinisikan di sini karena sudah ada di Laravel dengan
 * perilaku sama (htmlspecialchars ENT_QUOTES).
 */

use Illuminate\Support\Facades\DB;

/* ---------- Opsi pesanan (ala Kopi Kenangan) ---------- */
define('UKURAN_OPSI', ['Regular' => 0, 'Large' => 5000]); // nama => tambahan harga
define('SAJI_OPSI', ['Dingin', 'Panas']);
define('GULA_OPSI', ['Normal Sugar', 'Less Sugar', 'No Sugar']);
define('KATEGORI_MINUMAN', ['Coffee', 'Non Coffee', 'Tea']); // kategori yang punya opsi

/** PDO dari koneksi Laravel — query lama (prepare/execute) tetap jalan persis. */
function db(): PDO
{
    $pdo = DB::connection()->getPdo();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
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
    session(['flash' => ['tipe' => $tipe, 'pesan' => $pesan]]);
}

function get_flash(): ?array
{
    $f = session('flash');
    session()->forget('flash');
    return $f ?: null;
}

function tambah_notifikasi(PDO $db, string $tipe, string $pesan, ?int $pesananId = null): void
{
    $stmt = $db->prepare('INSERT INTO notifikasi (tipe, pesan, pesanan_id) VALUES (?, ?, ?)');
    $stmt->execute([$tipe, $pesan, $pesananId]);
}

/**
 * Upload gambar dari $_FILES[$field] ke public/uploads/<folder>/.
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
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $izin = ['jpg', 'jpeg', 'png', 'webp'];
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
    $tujuan   = public_path('uploads/' . $folder . '/' . $namaBaru);
    if (!is_dir(dirname($tujuan))) @mkdir(dirname($tujuan), 0775, true);
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        $error = 'Gagal menyimpan file.';
        return null;
    }
    return $namaBaru;
}

function hapus_gambar(?string $file, string $folder): void
{
    if ($file) {
        $path = public_path('uploads/' . $folder . '/' . $file);
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
    $stmt = $db->prepare('SELECT COUNT(*) FROM pesanan WHERE nomor_pesanan LIKE ?');
    $stmt->execute([$prefix . '%']);
    return $prefix . str_pad((string) ($stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
}

/** URL lengkap ke meja.php?kode=... (dipakai untuk isi QR meja). */
function url_meja(string $kode): string
{
    return url('meja.php') . '?kode=' . $kode;
}

/**
 * Cari data pelanggan berdasarkan no. HP, atau buat baris baru kalau belum
 * ada; nama disamakan dengan input terakhir.
 */
function cari_atau_buat_pelanggan(PDO $db, string $nama, string $noHp): int
{
    $stmt = $db->prepare('SELECT id FROM pelanggan WHERE no_hp = ? LIMIT 1');
    $stmt->execute([$noHp]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $db->prepare('UPDATE pelanggan SET nama = ? WHERE id = ?')->execute([$nama, (int) $id]);
        return (int) $id;
    }

    $db->prepare('INSERT INTO pelanggan (nama, no_hp) VALUES (?, ?)')->execute([$nama, $noHp]);
    return (int) $db->lastInsertId();
}

/**
 * Nomor antrian harian, diambil dari urutan di nomor pesanan
 * (ORD-YYYYMMDD-0007 -> "7"). Reset otomatis tiap hari.
 */
function no_antrian(string $nomorPesanan): string
{
    $urut = substr($nomorPesanan, strrpos($nomorPesanan, '-') + 1);
    return ltrim($urut, '0') ?: '0';
}

/* ---------- Keranjang (session) ---------- */

function kunci_keranjang(int $menuId, ?string $ukuran, ?string $saji, ?string $gula): string
{
    return substr(md5($menuId . '|' . $ukuran . '|' . $saji . '|' . $gula), 0, 12);
}

function keranjang(): array
{
    $krj = session('keranjang', []);
    if (!is_array($krj)) return [];
    foreach ($krj as $v) {
        if (!is_array($v)) return []; // buang format keranjang lama
    }
    return $krj;
}

function simpan_keranjang(array $krj): void
{
    session(['keranjang' => $krj]);
}

function jumlah_item_keranjang(): int
{
    return array_sum(array_column(keranjang(), 'jumlah'));
}

/** Ambil detail baris keranjang dari DB. Item nonaktif/terhapus otomatis dibuang. */
function isi_keranjang(PDO $db): array
{
    $krj = keranjang();
    if (!$krj) return [];
    $ids   = array_unique(array_column($krj, 'menu_id'));
    $tanda = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $db->prepare("SELECT id, nama, harga, foto, status FROM menu WHERE id IN ($tanda)");
    $stmt->execute(array_values($ids));
    $menu = [];
    foreach ($stmt->fetchAll() as $m) $menu[(int) $m['id']] = $m;

    $hasil  = [];
    $berubah = false;
    foreach ($krj as $key => $baris) {
        $m = $menu[(int) $baris['menu_id']] ?? null;
        if (!$m || $m['status'] !== 'aktif') { unset($krj[$key]); $berubah = true; continue; }
        $tambah = UKURAN_OPSI[$baris['ukuran'] ?? ''] ?? 0;
        $opsi   = array_filter([$baris['ukuran'] ?? null, $baris['saji'] ?? null, $baris['gula'] ?? null]);
        $hasil[] = [
            'key'          => $key,
            'menu_id'      => (int) $m['id'],
            'nama'         => $m['nama'],
            'foto'         => $m['foto'],
            'jumlah'       => (int) $baris['jumlah'],
            'harga_satuan' => (float) $m['harga'] + $tambah,
            'opsi_label'   => $opsi ? implode(' · ', $opsi) : '',
            'subtotal'     => ((float) $m['harga'] + $tambah) * (int) $baris['jumlah'],
        ];
    }
    if ($berubah) simpan_keranjang($krj);
    return $hasil;
}

/** Sesi tamu aktif = sudah scan QR meja & isi nama + no HP (pelanggan_id wajib). */
function meja_aktif(): bool
{
    $meja = session('meja');
    return !empty($meja['meja_id']) && !empty($meja['pelanggan_id']);
}

function waktu_relatif(string $dt): string
{
    $selisih = time() - strtotime($dt);
    if ($selisih < 60)    return 'Baru saja';
    if ($selisih < 3600)  return floor($selisih / 60) . ' menit lalu';
    if ($selisih < 86400) return floor($selisih / 3600) . ' jam lalu';
    return tanggal_id($dt, true);
}
