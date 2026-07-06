<?php
require_once dirname(__DIR__) . '/config/config.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
