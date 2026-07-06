<?php
require_once dirname(__DIR__) . '/config/config.php';

$pengaturan = get_pengaturan($db);
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';

/* ---------- Opsi pesanan (ala Kopi Kenangan) ---------- */
const UKURAN_OPSI      = ['Regular' => 0, 'Large' => 5000]; // nama => tambahan harga
const SAJI_OPSI        = ['Dingin', 'Panas'];
const GULA_OPSI        = ['Normal Sugar', 'Less Sugar', 'No Sugar'];
const KATEGORI_MINUMAN = ['Coffee', 'Non Coffee', 'Tea']; // kategori yang punya opsi

/* ---------- Keranjang (session) ----------
 * Format: [key => ['menu_id', 'jumlah', 'ukuran', 'saji', 'gula']]
 * key = hash kombinasi menu+opsi, jadi "Latte Large No Sugar" dan
 * "Latte Regular" jadi dua baris terpisah seperti di aplikasi kopi.
 */
if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}
// Buang format keranjang lama (sebelum ada opsi)
foreach ($_SESSION['keranjang'] as $k => $v) {
    if (!is_array($v)) { $_SESSION['keranjang'] = []; break; }
}

function kunci_keranjang(int $menuId, ?string $ukuran, ?string $saji, ?string $gula): string
{
    return substr(md5($menuId . '|' . $ukuran . '|' . $saji . '|' . $gula), 0, 12);
}

function jumlah_item_keranjang(): int
{
    return array_sum(array_column($_SESSION['keranjang'], 'jumlah'));
}

/** Ambil detail baris keranjang dari DB. Item nonaktif/terhapus otomatis dibuang. */
function isi_keranjang(PDO $db): array
{
    if (!$_SESSION['keranjang']) return [];
    $ids   = array_unique(array_column($_SESSION['keranjang'], 'menu_id'));
    $tanda = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $db->prepare("SELECT id, nama, harga, foto, status FROM menu WHERE id IN ($tanda)");
    $stmt->execute(array_values($ids));
    $menu = [];
    foreach ($stmt->fetchAll() as $m) $menu[(int) $m['id']] = $m;

    $hasil = [];
    foreach ($_SESSION['keranjang'] as $key => $baris) {
        $m = $menu[(int) $baris['menu_id']] ?? null;
        if (!$m || $m['status'] !== 'aktif') { unset($_SESSION['keranjang'][$key]); continue; }
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
    return $hasil;
}

function pelanggan_masuk(): bool
{
    return !empty($_SESSION['pelanggan_id']);
}
