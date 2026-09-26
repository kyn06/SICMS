<?php
require_once __DIR__ . '/../../controllers/LegacyCaseController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new LegacyCaseController();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    Security::requireCsrfToken();
    $controller->index();
}

header('Location: ' . app_route('cases.index'));
exit;
