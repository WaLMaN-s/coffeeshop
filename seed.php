<?php
/**
 * Seed data contoh: pelanggan, pesanan 90 hari terakhir, pembayaran, notifikasi.
 * Jalankan sekali dari CLI: /opt/lampp/bin/php seed.php
 * Aman dijalankan ulang — berhenti jika sudah ada pesanan.
 */
if (PHP_SAPI !== 'cli') exit("Jalankan dari CLI.\n");
require __DIR__ . '/config/config.php';

if ((int) $db->query('SELECT COUNT(*) FROM pesanan')->fetchColumn() > 0) {
    exit("Sudah ada data pesanan — seed dilewati.\n");
}

$pelanggan = [
    ['Budi Santoso',    'budi@gmail.com',    '081234567801'],
    ['Siti Rahma',      'siti@gmail.com',    '081234567802'],
    ['Andi Wijaya',     'andi@gmail.com',    '081234567803'],
    ['Dewi Lestari',    'dewi@gmail.com',    '081234567804'],
    ['Rizky Pratama',   'rizky@gmail.com',   '081234567805'],
    ['Maya Anggraini',  'maya@gmail.com',    '081234567806'],
    ['Fajar Nugroho',   'fajar@gmail.com',   '081234567807'],
    ['Lina Marlina',    'lina@gmail.com',    '081234567808'],
];
$hashPelanggan = password_hash('pelanggan123', PASSWORD_DEFAULT);
$stmtPl = $db->prepare('INSERT INTO pelanggan (nama, email, no_hp, password, created_at) VALUES (?,?,?,?,?)');
foreach ($pelanggan as $i => [$nama, $email, $hp]) {
    $stmtPl->execute([$nama, $email, $hp, $hashPelanggan, date('Y-m-d H:i:s', strtotime('-' . (95 - $i * 3) . ' days'))]);
}
$idPelanggan = $db->query('SELECT id FROM pelanggan')->fetchAll(PDO::FETCH_COLUMN);
$menu = $db->query('SELECT id, harga FROM menu')->fetchAll();

$stmtP  = $db->prepare('INSERT INTO pesanan (nomor_pesanan, pelanggan_id, total, status, created_at) VALUES (?,?,?,?,?)');
$stmtI  = $db->prepare('INSERT INTO pesanan_item (pesanan_id, menu_id, jumlah, harga) VALUES (?,?,?,?)');
$stmtB  = $db->prepare('INSERT INTO pembayaran (pesanan_id, metode, jumlah, status, tanggal_bayar, created_at) VALUES (?,?,?,?,?,?)');
$stmtN  = $db->prepare('INSERT INTO notifikasi (tipe, pesan, pesanan_id, dibaca, created_at) VALUES (?,?,?,?,?)');

$totalPesanan = 0;
for ($h = 90; $h >= 0; $h--) {
    $tanggal = date('Y-m-d', strtotime("-$h days"));
    $jumlahHariIni = rand(2, 7);
    $urut = 0;

    for ($n = 0; $n < $jumlahHariIni; $n++) {
        $urut++;
        $jam   = str_pad((string) rand(8, 21), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00';
        $waktu = "$tanggal $jam";
        $nomor = 'ORD-' . date('Ymd', strtotime($tanggal)) . '-' . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
        $plId  = $idPelanggan[array_rand($idPelanggan)];

        // 1-4 item per pesanan
        $total = 0;
        $items = [];
        $ambil = (array) array_rand($menu, rand(1, 4));
        foreach ($ambil as $idx) {
            $qty = rand(1, 3);
            $items[] = [$menu[$idx]['id'], $qty, (float) $menu[$idx]['harga']];
            $total  += $qty * (float) $menu[$idx]['harga'];
        }

        // Status: hari lampau kebanyakan selesai; hari ini campur
        if ($h === 0) {
            $status = ['menunggu', 'menunggu', 'diproses', 'diproses', 'siap', 'selesai'][array_rand([0,1,2,3,4,5])];
        } else {
            $status = rand(1, 100) <= 90 ? 'selesai' : (rand(0, 1) ? 'dibatalkan' : 'selesai');
        }

        $stmtP->execute([$nomor, $plId, $total, $status, $waktu]);
        $pesananId = (int) $db->lastInsertId();
        foreach ($items as [$mid, $qty, $harga]) $stmtI->execute([$pesananId, $mid, $qty, $harga]);

        // Pembayaran
        $metode = rand(0, 1) ? 'cash' : 'qris';
        if ($status === 'dibatalkan') {
            $stmtB->execute([$pesananId, $metode, $total, 'gagal', null, $waktu]);
        } elseif ($status === 'selesai' || $status === 'siap' || ($status === 'diproses' && rand(0, 1))) {
            $stmtB->execute([$pesananId, $metode, $total, 'sudah_dibayar', $waktu, $waktu]);
        } else {
            $stmtB->execute([$pesananId, $metode, $total, 'belum_dibayar', null, $waktu]);
        }

        // Notifikasi untuk aktivitas hari ini
        if ($h === 0) {
            $stmtN->execute(['pesanan_baru', "Pesanan baru $nomor masuk.", $pesananId, 0, $waktu]);
        }
        $totalPesanan++;
    }
}

echo "Seed selesai: " . count($pelanggan) . " pelanggan, $totalPesanan pesanan.\n";
