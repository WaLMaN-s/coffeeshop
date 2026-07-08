<?php
require_once dirname(__DIR__) . '/config/config.php';
// Hanya akhiri sesi admin — sesi meja & keranjang di browser yang sama tetap utuh.
unset($_SESSION['admin_id'], $_SESSION['admin_nama']);
header('Location: login.php');
exit;
