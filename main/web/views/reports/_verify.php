<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();
$_SESSION['email'] = 'admin@sicms.local';
$_SESSION['role'] = 'super-admin';
include __DIR__ . '/index.php';
?>
