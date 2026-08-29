<?php

date_default_timezone_set('Asia/Manila');

$routes = [
    'dashboard' => 'index.php',
    'accounts.index' => 'web/views/accounts/index.php',
    'complaints.create' => 'web/views/complaints/create.php',
    'complaints.my_cases' => 'web/views/complaints/my_cases.php',
    'complaints.revise' => 'web/views/complaints/revise.php',
    'complaints.attachment' => 'web/views/complaints/attachment.php',
    'cases.index' => 'web/views/cases/index.php',
    'hearings.index' => 'web/views/hearings/index.php',
    'reports.index' => 'web/views/reports/index.php',
    'audit_logs.index' => 'web/views/audit_logs/index.php',
    'notifications.index' => 'web/views/notifications/index.php',
    'legacy.index' => 'web/views/legacy/index.php',
    'messages.index' => 'web/views/messages/index.php',
    'messages.send' => 'web/views/messages/send.php',
    'settings.index' => 'web/views/settings/index.php',
    'logout' => 'web/views/auth/logout.php',
];

function app_base_url() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $viewsPosition = strpos($script, '/web/views/');

    if ($viewsPosition !== false) {
        return rtrim(substr($script, 0, $viewsPosition), '/');
    }

    return rtrim(dirname($script), '/.');
}

function app_url($path = '') {
    return app_base_url() . '/' . ltrim($path, '/');
}

function app_route($name) {
    global $routes;
    return isset($routes[$name]) ? app_url($routes[$name]) : '#';
}

function app_current_route() {
    global $routes;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = app_base_url();
    $path = ltrim(substr($script, strlen($base)), '/');

    foreach ($routes as $name => $routePath) {
        if ($path === ltrim($routePath, '/')) {
            return $name;
        }
    }

    if (str_starts_with($path, 'web/views/cases/')) return 'cases.index';
    if (str_starts_with($path, 'web/views/hearings/')) return 'hearings.index';
    if ($path === 'web/views/complaints/create.php') return 'complaints.create';
    if (str_starts_with($path, 'web/views/complaints/')) return 'complaints.my_cases';
    if (str_starts_with($path, 'web/views/messages/')) return 'messages.index';
    if (str_starts_with($path, 'web/views/settings/')) return 'settings.index';

    return null;
}
