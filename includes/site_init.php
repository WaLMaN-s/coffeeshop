<?php
require_once dirname(__DIR__) . '/config/config.php';

$pengaturan = get_pengaturan($db);
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';

/* ---------- Keranjang (session) ---------- */
if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = []; // [menu_id => jumlah]
}

function jumlah_item_keranjang(): int
{
    return array_sum($_SESSION['keranjang']);
}

/** Ambil detail item keranjang dari DB. Item nonaktif/terhapus otomatis dibuang. */
function isi_keranjang(PDO $db): array
{
    if (!$_SESSION['keranjang']) return [];
    $ids   = array_keys($_SESSION['keranjang']);
    $tanda = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $db->prepare("SELECT id, nama, harga, foto, status FROM menu WHERE id IN ($tanda)");
    $stmt->execute($ids);
    $hasil = [];
    $ada   = [];
    foreach ($stmt->fetchAll() as $m) {
        $ada[] = (int) $m['id'];
        if ($m['status'] !== 'aktif') continue;
        $m['jumlah']   = (int) $_SESSION['keranjang'][$m['id']];
        $m['subtotal'] = $m['jumlah'] * (float) $m['harga'];
        $hasil[]       = $m;
    }
    foreach (array_keys($_SESSION['keranjang']) as $id) {
        if (!in_array((int) $id, $ada, true)) unset($_SESSION['keranjang'][$id]);
    }
    return $hasil;
}

function pelanggan_masuk(): bool
{
    return !empty($_SESSION['pelanggan_id']);
}
