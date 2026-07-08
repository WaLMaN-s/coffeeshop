<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$id      = (int) ($_POST['id'] ?? 0);
$dataUrl = $_POST['data'] ?? '';

$stmt = $db->prepare('SELECT id FROM meja WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch() || !preg_match('#^data:image/png;base64,([a-zA-Z0-9+/=]+)$#', $dataUrl, $m)) {
    echo json_encode(['ok' => false]);
    exit;
}

$isi   = base64_decode($m[1]);
$folder = dirname(__DIR__, 2) . '/uploads/qrcode';
if (!is_dir($folder)) mkdir($folder, 0775, true);
file_put_contents($folder . '/meja-' . $id . '.png', $isi);

echo json_encode(['ok' => true, 'url' => 'uploads/qrcode/meja-' . $id . '.png']);
