<?php

require_once __DIR__ . '/../../../routes.php';

if (!function_exists('layout_role_key')) {
    function layout_role_key($role) {
        return strtolower(str_replace(['_', ' '], '-', (string) $role));
    }
}

$roleKey = layout_role_key($user['role'] ?? '');
$roleLabel = ucwords(str_replace(['-', '_'], ' ', (string) ($user['role'] ?? '')));
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? 'User');
$initials = strtoupper(substr($user['first_name'] ?? ($user['email'] ?? 'U'), 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$initials = trim($initials) ?: 'U';
$currentRoute = app_current_route();

$navGroups = [
    'MAIN' => [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'roles' => ['*']],
        ['route' => 'cases.index', 'label' => 'Case Management', 'icon' => 'bi-folder2-open', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head']],
        ['route' => 'reports.index', 'label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head']],
        ['route' => 'complaints.create', 'label' => 'Submit Complaint', 'icon' => 'bi-send-plus', 'roles' => ['student']],
        ['route' => 'complaints.my_cases', 'label' => 'Track My Cases', 'icon' => 'bi-folder-check', 'roles' => ['student']],
    ],
    'MANAGEMENT' => [
        ['route' => 'accounts.index', 'label' => 'Users', 'icon' => 'bi-people', 'roles' => ['super-admin', 'head-of-sdru', 'sdru-head']],
        ['route' => 'messages.index', 'label' => 'Chat & Messaging', 'icon' => 'bi-chat-dots', 'roles' => ['student', 'super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head']],
        ['route' => 'audit_logs.index', 'label' => 'Audit Logs', 'icon' => 'bi-shield-check', 'roles' => ['super-admin', 'head-of-sdru', 'sdru-head']],
        ['route' => 'hearings.index', 'label' => 'Hearings', 'icon' => 'bi-calendar-event', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head']],
    ],
    'SYSTEM' => [
        ['route' => 'legacy.index', 'label' => 'Legacy of SDRU In-Charge', 'icon' => 'bi-award', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head']],
        ['route' => 'settings.index', 'label' => 'Settings', 'icon' => 'bi-gear', 'roles' => ['*']],
    ],
];

foreach ($navGroups as &$items) {
    foreach ($items as &$item) {
        $item['href'] = $item['route'] ? app_route($item['route']) : $item['href'];
    }
}
unset($items, $item);

if (!function_exists('allowed_for_role')) {
    function allowed_for_role(array $item, $roleKey) {
        return in_array('*', $item['roles'], true) || in_array($roleKey, $item['roles'], true);
    }
}
