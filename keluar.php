<?php
require_once __DIR__ . '/includes/site_init.php';
unset($_SESSION['meja'], $_SESSION['keranjang']);
set_flash('sukses', 'Sesi meja diakhiri. Sampai jumpa lagi!');
header('Location: meja.php');
exit;
