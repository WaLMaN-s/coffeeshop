<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../masuk.php');
    exit;
}

$pengaturan = get_pengaturan($db);
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';
$namaAdmin  = $_SESSION['admin_nama'] ?? 'Admin';
