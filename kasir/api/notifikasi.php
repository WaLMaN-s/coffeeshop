<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['kasir_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'baca_semua') {
    $db->exec('UPDATE notifikasi SET dibaca = 1 WHERE dibaca = 0');
    echo json_encode(['ok' => true]);
    exit;
}

$jumlah = (int) $db->query('SELECT COUNT(*) FROM notifikasi WHERE dibaca = 0')->fetchColumn();
$rows   = $db->query('SELECT * FROM notifikasi ORDER BY created_at DESC LIMIT 10')->fetchAll();

function waktu_relatif(string $dt): string
{
    $selisih = time() - strtotime($dt);
    if ($selisih < 60)    return 'Baru saja';
    if ($selisih < 3600)  return floor($selisih / 60) . ' menit lalu';
    if ($selisih < 86400) return floor($selisih / 3600) . ' jam lalu';
    return tanggal_id($dt, true);
}

$item = array_map(fn($n) => [
    'id'         => (int) $n['id'],
    'tipe'       => $n['tipe'],
    'pesan'      => $n['pesan'],
    'pesanan_id' => $n['pesanan_id'],
    'dibaca'     => (int) $n['dibaca'],
    'waktu'      => waktu_relatif($n['created_at']),
], $rows);

$antrean = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'")->fetchColumn();

echo json_encode(['jumlah' => $jumlah, 'antrean' => $antrean, 'item' => $item]);
