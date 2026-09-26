<?php
require_once __DIR__ . '/../../controllers/AttachmentController.php';
$controller = new AttachmentController();
$controller->serve((int) ($_GET['id'] ?? 0), ($_GET['mode'] ?? 'view') === 'download' ? 'download' : 'view');
