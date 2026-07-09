<?php
require_once dirname(__DIR__) . '/config/config.php';
unset($_SESSION['kasir_id'], $_SESSION['kasir_nama']);
header('Location: login.php');
exit;
