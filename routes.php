<?php

date_default_timezone_set('Asia/Manila');

$routes = [
    'dashboard' => 'dashboard/',
    'accounts.index' => 'accounts/',
    'complaints.create' => 'web/views/complaints/create.php',
    'complaints.my_cases' => 'web/views/complaints/my_cases.php',
    'complaints.revise' => 'web/views/complaints/revise.php',
    'complaints.attachment' => 'web/views/complaints/attachment.php',
    'cases.index' => 'cases/',
    'archived_cases.index' => 'archived-cases/',
    'legacy_cases.create' => 'web/views/legacy_cases/create.php',
    'legacy_cases.edit' => 'web/views/legacy_cases/edit.php',
    'legacy_cases.show' => 'web/views/legacy_cases/show.php',
    'hearings.index' => 'hearings/',
    'reports.index' => 'reports/',
    'audit_logs.index' => 'audit-logs/',
    'notifications.index' => 'notifications/',
    'messages.index' => 'messages/',
    'messages.send' => 'web/views/messages/send.php',
    'respondent.cases' => 'web/views/respondent/cases.php',
    'respondent.case_show' => 'web/views/respondent/case_show.php',
    'respondent.activate' => 'web/views/auth/activate.php',
    'settings.index' => 'settings/',
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

function app_request_path() {
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $path = str_replace('\\', '/', (string) $requestPath);
    $base = app_base_url();

    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base));
    }

    $path = trim(rawurldecode($path), '/');
    if ($path !== '') {
        return $path;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($base !== '' && ($script === $base || str_starts_with($script, $base . '/'))) {
        $script = substr($script, strlen($base));
    }

    return trim($script, '/');
}

function app_current_route() {
    global $routes;
    $path = app_request_path();
    $pathLower = strtolower($path);

    $cleanRoutes = [
        '' => 'dashboard',
        'index.php' => 'dashboard',
        'dashboard/' => 'dashboard',
        'dashboard' => 'dashboard',
        'accounts/' => 'accounts.index',
        'accounts' => 'accounts.index',
        'cases/' => 'cases.index',
        'cases' => 'cases.index',
        'archived-cases/' => 'archived_cases.index',
        'archived-cases' => 'archived_cases.index',
        'hearings/' => 'hearings.index',
        'hearings' => 'hearings.index',
        'reports/' => 'reports.index',
        'reports' => 'reports.index',
        'audit-logs/' => 'audit_logs.index',
        'audit-logs' => 'audit_logs.index',
        'notifications/' => 'notifications.index',
        'notifications' => 'notifications.index',
        'messages/' => 'messages.index',
        'messages' => 'messages.index',
        'settings/' => 'settings.index',
        'settings' => 'settings.index',
    ];

    if (isset($cleanRoutes[$pathLower])) {
        return $cleanRoutes[$pathLower];
    }

    foreach ($routes as $name => $routePath) {
        if ($pathLower === strtolower(trim($routePath, '/'))) {
            return $name;
        }
    }

    if (str_starts_with($pathLower, 'cases/')) return 'cases.index';
    if (str_starts_with($pathLower, 'web/views/cases/')) return 'cases.index';
    if (str_starts_with($pathLower, 'web/views/archived_cases/')) return 'archived_cases.index';
    if (str_starts_with($pathLower, 'web/views/legacy_cases/')) {
        if (str_ends_with($pathLower, '/create.php')) return 'legacy_cases.create';
        if (str_ends_with($pathLower, '/edit.php')) return 'legacy_cases.edit';
        if (str_contains($pathLower, '/show.php')) return 'legacy_cases.show';
        return 'cases.index';
    }
    if (str_starts_with($pathLower, 'web/views/accounts/')) return 'accounts.index';
    if (str_starts_with($pathLower, 'web/views/hearings/')) return 'hearings.index';
    if ($pathLower === 'web/views/complaints/create.php') return 'complaints.create';
    if (str_starts_with($pathLower, 'web/views/complaints/')) return 'complaints.my_cases';
    if (str_starts_with($pathLower, 'web/views/reports/')) return 'reports.index';
    if (str_starts_with($pathLower, 'web/views/audit_logs/')) return 'audit_logs.index';
    if (str_starts_with($pathLower, 'web/views/messages/')) return 'messages.index';
    if (str_starts_with($pathLower, 'web/views/settings/')) return 'settings.index';
    if (str_starts_with($pathLower, 'web/views/respondent/')) return 'respondent.cases';
    if (str_starts_with($pathLower, 'web/views/notifications/')) return 'notifications.index';
    if (str_starts_with($pathLower, 'web/views/dashboard/')) return 'dashboard';

    return null;
}

function app_current_sidebar_route() {
    $route = app_current_route();
    $parentRoutes = [
        'archived_cases.index' => 'cases.index',
        'legacy_cases.create' => 'cases.index',
        'legacy_cases.edit' => 'cases.index',
        'legacy_cases.show' => 'cases.index',
        'complaints.revise' => 'complaints.my_cases',
        'complaints.attachment' => 'complaints.my_cases',
        'respondent.case_show' => 'respondent.cases',
        'messages.send' => 'messages.index',
        // Notifications are a global dashboard utility and have no sidebar entry.
        'notifications.index' => 'dashboard',
    ];

    return $parentRoutes[$route] ?? $route;
}

function profile_pic_url($account, $basePath = 'web/views/settings/profile_pic.php') {
    $accountId = (int) ($account['account_id'] ?? 0);
    if ($accountId <= 0 || empty($account['profile_pic'])) {
        return null;
    }

    $url = app_url($basePath . '?id=' . $accountId);
    $relative = str_replace('\\', '/', (string) $account['profile_pic']);

    if (str_starts_with($relative, 'storage/profile_pics/')) {
        $fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $version = is_file($fullPath) ? (string) filemtime($fullPath) : '0';
        $url .= '&v=' . $version;
    }

    return $url;
}
