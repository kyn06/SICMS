<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

if (isset($_SESSION['email'])) {
    header('Location: ../../../index.php');
    exit;
}

$_SESSION['error'] = 'New account registration is closed. Please contact the SDRU office if you need an account.';
header('Location: login.php');
exit;