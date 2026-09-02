<?php

require_once __DIR__ . '/web/helpers/Security.php';
Security::startSession();

if (isset($_SESSION['email'])) {
    header('Location: web/views/dashboard/index.php');
} else {
    header('Location: web/views/auth/login.php');
}
exit;
