<?php
require_once __DIR__ . '/includes/site_init.php';
unset($_SESSION['pelanggan_id'], $_SESSION['pelanggan_nama']);
set_flash('sukses', 'Kamu sudah keluar.');
header('Location: index.php');
exit;
