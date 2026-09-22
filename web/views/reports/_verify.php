<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();
$_SESSION['email'] = 'ricardo.santos@sicms.local';
$_SESSION['role'] = 'head-of-sdru';
include __DIR__ . '/index.php';
?>