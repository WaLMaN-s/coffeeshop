<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/fungsi.php';

if (empty($_SESSION['kasir_id'])) {
    header('Location: login.php');
    exit;
}

$pengaturan = get_pengaturan($db);
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';
$namaKasir  = $_SESSION['kasir_nama'] ?? 'Kasir';
