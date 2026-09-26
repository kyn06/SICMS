<?php

require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

if (isset($_SESSION['email'])) {
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../models/AuditLog.php';

    $database = new Database();
    $db = $database->getConnection();

    User::setConnection($db);
    AuditLog::setConnection($db);

    $user = User::findByEmail($_SESSION['email']);

    if ($user) {
        AuditLog::record($user, 'User Logout', 'User logged out.');
    }
}

session_unset();

session_destroy();

header('Location: login.php');
exit;
