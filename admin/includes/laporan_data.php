<?php
/**
 * Menghitung data laporan untuk rentang tanggal.
 * Input : $_GET['periode'|'mulai'|'sampai'], $db
 * Output: $periode, $mulai, $sampai, $ringkasan, $perHari, $terlarisLaporan
 */
$periode = $_GET['periode'] ?? 'harian';
$mulai   = $_GET['mulai'] ?? '';
$sampai  = $_GET['sampai'] ?? '';

switch ($periode) {
    case 'mingguan':
        $mulai  = date('Y-m-d', strtotime('monday this week'));
        $sampai = date('Y-m-d');
        break;
    case 'bulanan':
        $mulai  = date('Y-m-01');
        $sampai = date('Y-m-d');
        break;
    case 'rentang':
        $mulai  = $mulai ?: date('Y-m-01');
        $sampai = $sampai ?: date('Y-m-d');
        if ($mulai > $sampai) { [$mulai, $sampai] = [$sampai, $mulai]; }
        break;
    default: // harian
        $periode = 'harian';
        $mulai   = $sampai = date('Y-m-d');
}

/* Ringkasan */
$stmt = $db->prepare("
    SELECT COUNT(*) jumlah_pesanan, COALESCE(SUM(CASE WHEN status='selesai' THEN total END),0) pendapatan_pesanan
    FROM pesanan WHERE DATE(created_at) BETWEEN ? AND ? AND status <> 'dibatalkan'");
$stmt->execute([$mulai, $sampai]);
$rp = $stmt->fetch();

$stmt = $db->prepare("
    SELECT COUNT(*) transaksi, COALESCE(SUM(jumlah),0) pendapatan
    FROM pembayaran WHERE status = 'sudah_dibayar' AND DATE(tanggal_bayar) BETWEEN ? AND ?");
$stmt->execute([$mulai, $sampai]);
$rb = $stmt->fetch();

$ringkasan = [
    'transaksi'      => (int) $rb['transaksi'],
    'pendapatan'     => (float) $rb['pendapatan'],
    'jumlah_pesanan' => (int) $rp['jumlah_pesanan'],
];

/* Rincian per hari */
$stmt = $db->prepare("
    SELECT DATE(tanggal_bayar) tgl, COUNT(*) transaksi, SUM(jumlah) pendapatan
    FROM pembayaran WHERE status = 'sudah_dibayar' AND DATE(tanggal_bayar) BETWEEN ? AND ?
    GROUP BY DATE(tanggal_bayar) ORDER BY tgl");
$stmt->execute([$mulai, $sampai]);
$perHari = $stmt->fetchAll();

/* Menu terlaris pada rentang */
$stmt = $db->prepare("
    SELECT m.nama, k.nama kategori, SUM(pi.jumlah) terjual, SUM(pi.jumlah * pi.harga) omzet
    FROM pesanan_item pi
    JOIN pesanan p ON p.id = pi.pesanan_id AND p.status <> 'dibatalkan'
    JOIN menu m ON m.id = pi.menu_id
    JOIN kategori k ON k.id = m.kategori_id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY pi.menu_id ORDER BY terjual DESC LIMIT 10");
$stmt->execute([$mulai, $sampai]);
$terlarisLaporan = $stmt->fetchAll();
