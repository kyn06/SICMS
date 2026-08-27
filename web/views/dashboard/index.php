<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Hearing.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../models/Report.php';
require_once __DIR__ . '/../../models/AuditLog.php';
require_once __DIR__ . '/../../models/Complaint.php';
require_once __DIR__ . '/../../models/Message.php';
require_once __DIR__ . '/../../../routes.php';

if (!isset($_SESSION['email'])) {
    header('Location: web/views/auth/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

User::setConnection($db);
Hearing::setConnection($db);
Notification::setConnection($db);
Report::setConnection($db);
AuditLog::setConnection($db);
Complaint::setConnection($db);
Message::setConnection($db);

$user = User::findByEmail($_SESSION['email']);

if (!$user || $user['status'] !== 'active') {
    session_unset();
    session_destroy();
    header('Location: web/views/auth/login.php');
    exit;
}

$_SESSION['role'] = $user['role'];

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function selected($left, $right) {
    return (string) $left === (string) $right ? 'selected' : '';
}

function role_key($role) {
    return strtolower(str_replace(['_', ' '], '-', (string) $role));
}

function chart_payload(array $rows) {
    return [
        'labels' => array_map(fn($row) => $row['label'], $rows),
        'values' => array_map(fn($row) => (int) $row['total'], $rows),
    ];
}

function format_time_ago($dateTime) {
    $timestamp = strtotime((string) $dateTime);

    if (!$timestamp) {
        return '';
    }

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    }

    if ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . ' hr ago';
    }

    return date('M d, Y', $timestamp);
}

$fullName = trim($user['first_name'] . ' ' . $user['last_name']);
$displayName = $fullName ?: $user['email'];
$role = $user['role'];
$roleKey = role_key($role);
$isCoordinator = $roleKey === 'coordinator';
$roleLabel = ucwords(str_replace(['-', '_'], ' ', $role));
$initials = strtoupper(substr($user['first_name'] ?? $user['email'], 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$initials = trim($initials) ?: 'U';

$analyticsRoles = ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head-of-sdru'];
$staffHearingRoles = ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head'];
$canViewAnalytics = in_array($roleKey, $analyticsRoles, true);
$canViewHearings = in_array($roleKey, $staffHearingRoles, true);
$studentCases = $roleKey === 'student' ? Complaint::forStudent((int) $user['account_id'], 3) : [];

$filters = Report::normalizeFilters($_GET);
if ($isCoordinator) $filters['coordinator'] = (int) $user['account_id'];
$reportData = $canViewAnalytics ? Report::getDashboardData($filters) : [
    'summary' => [],
    'casesByMonth' => [],
    'casesByClassification' => [],
    'casesByStatus' => [],
    'casesByCollege' => [],
    'hearingsByMonth' => [],
    'rows' => [],
    'options' => [
        'statuses' => [],
        'classifications' => [],
        'colleges' => [],
        'coordinators' => [],
    ],
];

$summary = array_merge([
    'total_cases' => 0,
    'pending_cases' => 0,
    'ongoing_cases' => 0,
    'resolved_cases' => 0,
    'archived_cases' => 0,
    'scheduled_hearings' => 0,
    'total_students' => 0,
], $reportData['summary']);

$chartData = [
    'casesByMonth' => chart_payload($reportData['casesByMonth']),
    'casesByStatus' => chart_payload($reportData['casesByStatus']),
    'casesByClassification' => chart_payload($reportData['casesByClassification']),
    'casesByCollege' => chart_payload($reportData['casesByCollege']),
    'hearingsByMonth' => chart_payload($reportData['hearingsByMonth']),
    'sexDistribution' => chart_payload($reportData['casesBySex'] ?? []),
];

$rows = $reportData['rows'];
$options = $reportData['options'];
$today = date('Y-m-d');
$newComplaintsToday = 0;

foreach ($rows as $row) {
    if (substr((string) ($row['submitted_at'] ?? ''), 0, 10) === $today) {
        $newComplaintsToday++;
    }
}

$upcomingHearings = [];
$todaysHearings = 0;
if ($canViewHearings) {
    if ($isCoordinator) {
        $coordinatorHearings = Hearing::listHearings($user);
        $todaysHearings = count(array_filter($coordinatorHearings, fn($hearing) => $hearing['status'] === 'Scheduled' && substr($hearing['hearing_datetime'], 0, 10) === $today));
        $upcomingHearings = array_values(array_filter($coordinatorHearings, fn($hearing) => $hearing['status'] === 'Scheduled' && strtotime($hearing['hearing_datetime']) >= time()));
        usort($upcomingHearings, fn($left, $right) => strcmp($left['hearing_datetime'], $right['hearing_datetime']));
        $upcomingHearings = array_slice($upcomingHearings, 0, 3);
    } else {
        $upcomingHearings = Hearing::getUpcoming(6);
    }
}
if (!$isCoordinator) foreach ($upcomingHearings as $hearing) if (substr((string) ($hearing['hearing_datetime'] ?? ''), 0, 10) === $today) $todaysHearings++;

$unreadNotificationCount = Notification::unreadCount((int) $user['account_id']);
$recentNotifications = Notification::recentForUser((int) $user['account_id'], 5);
$recentActivities = $canViewAnalytics ? AuditLog::listLogs(['page' => 1, 'account_id' => $isCoordinator ? (int) $user['account_id'] : 0], $isCoordinator ? 8 : 6) : [];

$quickActions = [
    ['label' => 'Submit Complaint', 'href' => app_route('complaints.create'), 'icon' => 'bi-send-plus', 'roles' => ['student']],
    ['label' => 'My Cases', 'href' => app_route('complaints.my_cases'), 'icon' => 'bi-folder-check', 'roles' => ['student']],
    ['label' => 'Create User', 'href' => app_route('accounts.index'), 'icon' => 'bi-person-plus', 'roles' => ['super-admin', 'head-of-sdru', 'sdru-head']],
    ['label' => 'Assign Coordinator', 'href' => app_route('cases.index'), 'icon' => 'bi-person-check', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head']],
    ['label' => 'Generate Report', 'href' => app_route('reports.index'), 'icon' => 'bi-file-earmark-bar-graph', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head']],
    ['label' => 'Schedule Hearing', 'href' => app_route('hearings.index'), 'icon' => 'bi-calendar-plus', 'roles' => ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head']],
    ['label' => 'View Assigned Cases', 'href' => app_route('cases.index'), 'icon' => 'bi-folder-check', 'roles' => ['coordinator']],
    ['label' => 'Chat', 'href' => app_route('messages.index'), 'icon' => 'bi-chat-dots', 'roles' => ['coordinator']],
    ['label' => 'View Calendar', 'href' => app_route('hearings.index'), 'icon' => 'bi-calendar3', 'roles' => ['coordinator']],
    ['label' => 'Notifications', 'href' => app_route('notifications.index'), 'icon' => 'bi-bell', 'roles' => ['coordinator']],
];

function allowed_for_role(array $item, $roleKey) {
    return in_array('*', $item['roles'], true) || in_array($roleKey, $item['roles'], true);
}

$statCards = $isCoordinator ? [
    ['key' => 'total_cases', 'label' => 'Total Assigned Cases', 'value' => $summary['total_cases'], 'icon' => 'bi-folder-check', 'accent' => 'green', 'trend' => 'Assigned to you'],
    ['key' => 'submitted_cases', 'label' => 'Submitted Cases', 'value' => $summary['submitted_cases'] ?? 0, 'icon' => 'bi-send', 'accent' => 'blue', 'trend' => 'Awaiting action'],
    ['key' => 'verified_cases', 'label' => 'Verified Cases', 'value' => $summary['verified_cases'] ?? 0, 'icon' => 'bi-patch-check', 'accent' => 'teal', 'trend' => 'Verified assignments'],
    ['key' => 'returned_for_revision_cases', 'label' => 'Returned for Revision', 'value' => $summary['returned_for_revision_cases'] ?? 0, 'icon' => 'bi-pencil-square', 'accent' => 'amber', 'trend' => 'Awaiting student revision'],
    ['key' => 'resolved_cases', 'label' => 'Resolved Cases', 'value' => $summary['resolved_cases'], 'icon' => 'bi-check2-circle', 'accent' => 'teal', 'trend' => 'Completed cases'],
    ['key' => 'scheduled_hearings', 'label' => 'Scheduled Hearings', 'value' => $summary['scheduled_hearings'], 'icon' => 'bi-calendar-week', 'accent' => 'indigo', 'trend' => $todaysHearings . ' scheduled today'],
] : [
    ['key' => 'total_cases', 'label' => 'Total Cases', 'value' => $summary['total_cases'], 'icon' => 'bi-briefcase', 'accent' => 'green', 'trend' => 'All recorded cases'],
    ['key' => 'ongoing_cases', 'label' => 'Active Cases', 'value' => $summary['ongoing_cases'], 'icon' => 'bi-activity', 'accent' => 'blue', 'trend' => 'Verified or assigned'],
    ['key' => 'pending_cases', 'label' => 'Pending Reviews', 'value' => $summary['pending_cases'], 'icon' => 'bi-hourglass-split', 'accent' => 'amber', 'trend' => 'Needs SDRU action'],
    ['key' => 'resolved_cases', 'label' => 'Resolved Cases', 'value' => $summary['resolved_cases'], 'icon' => 'bi-check2-circle', 'accent' => 'teal', 'trend' => 'Completed case work'],
    ['key' => 'scheduled_hearings', 'label' => 'Scheduled Hearings', 'value' => $summary['scheduled_hearings'], 'icon' => 'bi-calendar-week', 'accent' => 'indigo', 'trend' => 'Upcoming schedule'],
    ['key' => 'todays_hearings', 'label' => "Today's Hearings", 'value' => $todaysHearings, 'icon' => 'bi-calendar-day', 'accent' => 'violet', 'trend' => 'Remaining today'],
    ['key' => 'new_complaints_today', 'label' => 'New Complaints Today', 'value' => $newComplaintsToday, 'icon' => 'bi-inbox', 'accent' => 'rose', 'trend' => 'Based on report results'],
    ['key' => 'total_students', 'label' => 'Registered Students', 'value' => $summary['total_students'], 'icon' => 'bi-mortarboard', 'accent' => 'green', 'trend' => 'Student accounts'],
];

if (($_GET['ajax'] ?? '') === 'dashboard') {
    if (!$canViewAnalytics) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Dashboard analytics access denied.']);
        exit;
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'stats' => array_column($statCards, 'value', 'key'),
        'charts' => $chartData,
        'rows' => $rows,
        'filters' => $filters,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SICMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="web/views/layout/style.css">
    <link rel="stylesheet" href="web/views/layout/sidebar.css">
    <link rel="stylesheet" href="web/views/layout/accounts.css?v=2">
    <link rel="stylesheet" href="web/views/layout/system.css?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    #dashboardFilters {
        background: none;
        border: none;
        box-shadow: none;
        display: flex;
        justify-content: flex-end;
        margin-bottom: 14px;
        padding: 0;
        position: relative;
        width: 100%;
    }

    .filters-toggle-btn {
        align-items: center;
        background: #fff;
        border: 1px solid #bfd0bc;
        border-radius: 6px;
        color: var(--text);
        cursor: pointer;
        display: inline-flex;
        font: inherit;
        font-size: 13.5px;
        font-weight: 700;
        gap: 8px;
        padding: 9px 16px;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .filters-toggle-btn:hover {
        border-color: var(--sicms-green-600);
        color: var(--sicms-green-700);
    }

    .filters-count {
        align-items: center;
        background: #e6f3ea;
        border-radius: 999px;
        color: #1a8c2b;
        display: inline-flex;
        font-size: 11px;
        justify-content: center;
        min-width: 20px;
        padding: 0 6px;
        height: 20px;
    }

    #dashboardFilters.is-active .filters-toggle-btn,
    #dashboardFilters.is-active .filters-toggle-btn:hover {
        background: var(--sicms-green-600);
        border-color: var(--sicms-green-600);
        color: #fff;
    }

    #dashboardFilters.is-active .filters-count {
        background: #fff;
    }

    .filters-popover {
        background: #fff;
        border: 1px solid rgba(191, 208, 188, .75);
        border-radius: 14px;
        box-shadow: 0 18px 45px rgba(15, 40, 21, .22);
        display: none;
        left: auto;
        padding: 16px 18px;
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        width: min(680px, calc(100vw - 56px));
        z-index: 500;
    }

    .filters-popover.open {
        animation: sicmsFilterPop .18s ease-out;
        display: block;
    }

    @keyframes sicmsFilterPop {
        from {
            opacity: 0;
            transform: translateY(-6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #dashboardFilters .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #dashboardFilters .filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 14px;
    }

    @media (max-width: 640px) {
        .filters-popover {
            width: calc(100vw - 32px);
        }

        #dashboardFilters .filter-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ===== Modernized analytics (Bootstrap-based) ===== */
    .main-panel .panel {
        background: #fff;
        border: 1px solid rgba(219, 231, 216, .9);
        border-radius: 14px !important;
        box-shadow: 0 4px 16px rgba(18, 60, 27, .06);
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .main-panel .panel:hover {
        box-shadow: 0 12px 28px rgba(18, 60, 27, .13) !important;
        transform: translateY(-2px);
    }

    .main-panel .section-title {
        align-items: center;
        display: flex;
        font-size: 15.5px;
        gap: 9px;
    }

    .main-panel .section-title i {
        color: #167a22;
    }

    .main-panel .dashboard-grid {
        margin-top: 18px;
    }

    .main-panel .empty-state {
        color: #7c8b78;
        font-size: 13px;
    }

    .main-panel .activity-item {
        align-items: flex-start;
        background: none;
        border-bottom: 1px solid rgba(219, 231, 216, .65);
        border-radius: 0;
        box-shadow: none;
        display: flex;
        gap: 11px;
        padding: 11px 0;
    }

    .main-panel .activity-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .main-panel .activity-check {
        align-items: center;
        background: #eaf7e8;
        border: none;
        border-radius: 10px;
        color: #167a22;
        display: inline-flex;
        flex: none;
        font-size: 15px;
        height: 34px;
        justify-content: center;
        width: 34px;
    }

    .main-panel .activity-title {
        font-size: 13.5px;
    }

    .main-panel .activity-description,
    .main-panel .activity-time {
        color: #6b7a67;
        font-size: 12.5px;
    }

    .main-panel .activity-time {
        color: #8a9a86;
    }

    .main-panel table th {
        color: #40513d;
        font-size: 11.5px;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .main-panel .hearing-table th,
    .main-panel table[data-page-size] th {
        background: #f4f8f3;
    }

    .main-panel .sicms-card {
        background: #fff;
        border: 1px solid rgba(219, 231, 216, .9);
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(18, 60, 27, .06);
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .main-panel .sicms-card:hover {
        box-shadow: 0 12px 28px rgba(18, 60, 27, .13);
        transform: translateY(-2px);
    }

    .main-panel .sicms-card.stat-hero,
    .main-panel .sicms-card.stat-hero:hover {
        background: linear-gradient(135deg, #1a9d00 0%, #123c1b 78%) !important;
        border-color: transparent;
    }

    .sicms-stat {
        color: var(--text);
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 16px 18px;
        position: relative;
        overflow: hidden;
    }

    .sicms-stat::before {
        background: radial-gradient(circle, var(--chip-bg, #eaf7e8), transparent 68%);
        content: '';
        height: 170px;
        opacity: .6;
        pointer-events: none;
        position: absolute;
        right: -46px;
        top: -66px;
        width: 170px;
    }

    .sicms-stat::after {
        background: linear-gradient(90deg, var(--chip-fg, #167a22), transparent);
        content: '';
        height: 4px;
        left: 0;
        opacity: .85;
        position: absolute;
        right: 0;
        top: 0;
    }

    .sicms-stat-top {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        justify-content: space-between;
    }

    .sicms-stat-label {
        color: #5f6f5c;
        font-size: 12.5px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .sicms-stat-value {
        font-size: 30px;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: -.5px;
        line-height: 1.15;
    }

    .sicms-stat-chip {
        align-items: center;
        background: var(--chip-bg, #eaf7e8);
        border-radius: 12px;
        box-shadow: inset 0 -6px 12px rgba(0, 0, 0, .03);
        color: var(--chip-fg, #167a22);
        display: inline-flex;
        flex: none;
        font-size: 20px;
        height: 44px;
        justify-content: center;
        width: 44px;
    }

    .sicms-stat-trend {
        align-items: center;
        color: #5f6f5c;
        display: flex;
        font-size: 12px;
        font-weight: 600;
        gap: 6px;
    }

    .sicms-stat-trend i {
        color: var(--chip-fg, #167a22);
        font-size: 11px;
    }

    .sicms-card.stat-hero .sicms-stat::before {
        display: none;
    }

    .sicms-card.stat-hero .sicms-stat::after {
        background: rgba(255, 255, 255, .5);
    }

    .main-panel .section-subtitle {
        color: #7c8b78;
        font-size: 11.5px;
        font-weight: 600;
        margin-top: 2px;
        margin-bottom: 10px;
    }

    .main-panel .panel .chart-box {
        margin-top: 8px;
    }

    .accent-green {
        --chip-bg: #eaf7e8;
        --chip-fg: #167a22;
    }

    .accent-blue {
        --chip-bg: #e7f0fb;
        --chip-fg: #175cd3;
    }

    .accent-amber {
        --chip-bg: #fdf3e0;
        --chip-fg: #9a6700;
    }

    .accent-teal {
        --chip-bg: #e3f5f3;
        --chip-fg: #0f766e;
    }

    .accent-indigo {
        --chip-bg: #ecebfc;
        --chip-fg: #4338ca;
    }

    .accent-violet {
        --chip-bg: #f5ebfd;
        --chip-fg: #7e22ce;
    }

    .accent-rose {
        --chip-bg: #fdecef;
        --chip-fg: #be123c;
    }

    .main-panel .sicms-card.stat-hero:hover {
        box-shadow: 0 16px 34px rgba(18, 60, 27, .38);
    }

    .stat-hero .sicms-stat-label,
    .stat-hero .sicms-stat-trend {
        color: rgba(255, 255, 255, .88);
    }

    .stat-hero .sicms-stat-value {
        color: #fff;
    }

    .stat-hero .sicms-stat-chip {
        background: rgba(255, 255, 255, .2);
        color: #fff;
    }

    .stats-actions-row {
        display: flex;
        gap: 18px;
        margin-bottom: 18px;
    }

    .stats-actions-row>.stats-col {
        flex: 1 1 0;
        min-width: 0;
    }

    .stats-actions-row>.actions-col {
        flex: 0 0 240px;
    }

    .stats-actions-row .quick-actions-panel {
        background: #fff;
        border: 1px solid rgba(219, 231, 216, .9);
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(18, 60, 27, .06);
        height: 100%;
        padding: 18px;
    }

    .stats-actions-row .quick-actions-panel .section-title {
        color: var(--sicms-green-900);
        font-size: 14px;
        margin-bottom: 14px;
    }

    .stats-actions-row .quick-actions-panel .section-title i {
        color: var(--sicms-green-700);
    }

    .stats-actions-row .quick-actions-panel .quick-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .stats-actions-row .quick-actions-panel .quick-grid .btn {
        background: linear-gradient(135deg, #1a9d00 0%, #123c1b 100%);
        border: none;
        border-radius: 8px;
        color: #fff;
        font-size: 12.5px;
        font-weight: 700;
        justify-content: flex-start;
        min-height: 38px;
        padding: 8px 12px;
        transition: background .16s ease, transform .16s ease, box-shadow .16s ease;
        text-decoration: none;
    }

    .stats-actions-row .quick-actions-panel .quick-grid .btn:hover {
        box-shadow: 0 6px 18px rgba(18, 60, 27, .25);
        transform: translateY(-1px);
    }

    @media (max-width: 1200px) {
        .stats-actions-row {
            flex-direction: column;
        }

        .stats-actions-row>.actions-col {
            flex: 1 1 0;
        }
    }
    </style>
</head>

<body>
    <?php require __DIR__ . '/../layout/protection.php'; ?>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>

        <main class="main-panel <?= !$canViewAnalytics ? 'student-main-panel' : '' ?>">
            <header class="topbar">
                <div>
                    <div class="welcome-label">Welcome back, <?= h($displayName) ?></div>
                    <h1 class="page-title">
                        <?= $isCoordinator ? 'Coordinator Workspace' : 'SDRU Case Management Dashboard' ?></h1>
                </div>

                <div class="topbar-actions">
                    <div class="datetime-pill" id="currentDateTime"><?= h(date('F d, Y h:i A')) ?></div>

                    <details class="dropdown">
                        <summary class="icon-button" aria-label="Notifications">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            <?php if ($unreadNotificationCount > 0): ?>
                            <span class="badge"><?= (int) $unreadNotificationCount ?></span>
                            <?php endif; ?>
                        </summary>
                        <div class="dropdown-menu">
                            <?php if (empty($recentNotifications)): ?>
                            <div class="notification-item">
                                <span class="notification-icon"><i class="bi bi-bell-slash"></i></span>
                                <div>
                                    <div class="notification-title">No notifications yet</div>
                                    <div class="notification-message">Unread notices will appear here.</div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php foreach ($recentNotifications as $notification): ?>
                            <a class="notification-item<?= (int) $notification['is_read'] === 0 ? ' unread' : '' ?>"
                                href="<?= h(app_route('notifications.index')) ?>">
                                <span class="notification-icon"><i class="bi bi-info-circle"></i></span>
                                <span>
                                    <span class="notification-title"><?= h($notification['title']) ?></span>
                                    <span class="notification-message"><?= h($notification['message']) ?></span>
                                    <span
                                        class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span>
                                </span>
                            </a>
                            <?php endforeach; ?>

                            <div class="dropdown-footer">
                                <form method="POST" action="<?= h(app_route('notifications.index')) ?>"
                                    data-mark-all-url="<?= h(app_url('web/api/notifications.php')) ?>">
                                    <?= Security::csrfField() ?>
                                    <button class="text-button" type="submit" name="notification_action"
                                        value="mark_all">Mark all as read</button>
                                </form>
                                <a class="text-button" href="<?= h(app_route('notifications.index')) ?>">View all</a>
                            </div>
                        </div>
                    </details>

                    <!-- <details class="dropdown">
                        <summary class="profile-button">
                            <span class="avatar" style="height: 30px; width: 30px; font-size: 12px;"><?= h($initials) ?></span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="dropdown-menu profile-menu">
                            <a href="#profile"><i class="bi bi-person"></i> My Profile</a>
                            <a href="#change-password"><i class="bi bi-key"></i> Change Password</a>
                            <a href="<?= h(app_route('logout')) ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </div>
                    </details> -->
                </div>
            </header>

            <?php if (!$canViewAnalytics): ?>
            <section class="student-panel student-hero">
                <div>
                    <div class="welcome-label">Complainant Portal</div>
                    <div class="section-title"> Submit a complaint or check the latest updates from SDRU.</div>
                    <!-- <p class="activity-description" style="margin-bottom: 0px">Submit a complaint or check the latest updates from SDRU.</p> -->
                </div>
            </section>

            <section class="student-actions student-actions-compact" aria-label="Student quick actions">
                <?php foreach ($quickActions as $action): ?>
                <?php if (allowed_for_role($action, $roleKey)): ?>
                <a class="student-action" href="<?= h($action['href']) ?>">
                    <i class="bi <?= h($action['icon']) ?>"></i>
                    <?= h($action['label']) ?>
                </a>
                <?php endif; ?>
                <?php endforeach; ?>
            </section>

            <section class="student-dashboard-grid student-dashboard-focused">
                <section class="student-panel">
                    <div class="section-title"><i class="bi bi-folder-check"></i> Recent Case Status</div>
                    <div class="student-case-list">
                        <?php if (empty($studentCases)): ?>
                        <div class="empty-state"><strong>You have no complaints yet.</strong><br><span>Submit a
                                complaint when you need assistance from SDRU.</span></div>
                        <?php endif; ?>
                        <?php foreach ($studentCases as $studentCase): ?>
                        <article class="student-case-card">
                            <div>
                                <a class="student-case-number"
                                    href="web/views/complaints/case_details.php?id=<?= (int) $studentCase['complaint_id'] ?>"><?= h($studentCase['case_classification']) ?></a>
                                <div class="student-case-meta"><?= h($studentCase['case_number']) ?> · Submitted
                                    <?= h(date('M d, Y', strtotime($studentCase['submitted_at']))) ?></div>
                            </div>
                            <span class="status-pill"><?= h($studentCase['status']) ?></span>
                            <div class="student-case-action"><a class="btn btn-secondary"
                                    href="web/views/complaints/case_details.php?id=<?= (int) $studentCase['complaint_id'] ?>" style="background: linear-gradient(135deg, #1A9D00 0%, #128000 100%) !important; color: #fff !important;">
                                    <i class="bi bi-eye"></i> View Details</a></div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="student-panel">
                    <div class="section-title"><i class="bi bi-bell"></i> Recent Notifications</div>
                    <div class="activity-list">
                        <?php if (empty($recentNotifications)): ?><div class="empty-state">You have no notifications
                            yet.</div><?php endif; ?>
                        <?php foreach ($recentNotifications as $notification): ?>
                        <a class="notification-item" href="<?= h(app_route('notifications.index')) ?>">
                            <span class="notification-icon"><i class="bi bi-info-circle"></i></span>
                            <span><span class="notification-title"><?= h($notification['title']) ?></span><span
                                    class="notification-message"><?= h($notification['message']) ?></span><span
                                    class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </section>
            <?php endif; ?>

            <?php if ($canViewAnalytics): ?>

            <form class="filters-card" id="dashboardFilters" method="GET" action="<?= h(app_route('dashboard')) ?>">
                <button type="button" class="filters-toggle-btn" id="dashboardFiltersToggle" aria-expanded="false"
                    aria-controls="dashboardFiltersPanel">
                    <i class="bi bi-funnel"></i> Filters
                    <span class="filters-count" id="dashboardFiltersCount" hidden></span>
                </button>
                <div class="filters-popover" id="dashboardFiltersPanel">
                    <div class="section-title"><i class="bi bi-sliders"></i> Dashboard Filters</div>
                    <div class="filter-grid">
                        <?php if ($isCoordinator): ?>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Statuses</option>
                                <?php foreach ($options['statuses'] as $status): ?><option value="<?= h($status) ?>"
                                    <?= selected($filters['status'], $status) ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="classification">Classification</label>
                            <select id="classification" name="classification">
                                <option value="">All Classifications</option>
                                <?php foreach ($options['classifications'] as $classification): ?><option
                                    value="<?= h($classification) ?>"
                                    <?= selected($filters['classification'], $classification) ?>>
                                    <?= h($classification) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field"><label for="date_from">Date From</label><input id="date_from" type="date"
                                name="date_from" value="<?= h($filters['date_from']) ?>"></div>
                        <div class="field"><label for="date_to">Date To</label><input id="date_to" type="date"
                                name="date_to" value="<?= h($filters['date_to']) ?>"></div>
                        <?php else: ?>
                        <div class="field">
                            <label for="academic_year">Academic Year</label>
                            <input id="academic_year" type="number" name="year" min="2000" max="2100"
                                value="<?= h($filters['year']) ?>" placeholder="<?= h(date('Y')) ?>">
                        </div>
                        <div class="field">
                            <label for="college">College</label>
                            <select id="college" name="college">
                                <option value="">All Colleges</option>
                                <?php foreach ($options['colleges'] as $college): ?>
                                <option value="<?= h($college) ?>" <?= selected($filters['college'], $college) ?>>
                                    <?= h($college) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="department">Department</label>
                            <select id="department" disabled>
                                <option>Not yet tracked</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="year_level">Year Level</label>
                            <select id="year_level" disabled>
                                <option>Not yet tracked</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="sex">Sex</label>
                            <select id="sex" disabled>
                                <option>Not yet tracked</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="date_from">Date From</label>
                            <input id="date_from" type="date" name="date_from" value="<?= h($filters['date_from']) ?>">
                        </div>
                        <div class="field">
                            <label for="date_to">Date To</label>
                            <input id="date_to" type="date" name="date_to" value="<?= h($filters['date_to']) ?>">
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Statuses</option>
                                <?php foreach ($options['statuses'] as $status): ?>
                                <option value="<?= h($status) ?>" <?= selected($filters['status'], $status) ?>>
                                    <?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="classification">Classification</label>
                            <select id="classification" name="classification">
                                <option value="">All Classifications</option>
                                <?php foreach ($options['classifications'] as $classification): ?>
                                <option value="<?= h($classification) ?>"
                                    <?= selected($filters['classification'], $classification) ?>>
                                    <?= h($classification) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="coordinator">Coordinator</label>
                            <select id="coordinator" name="coordinator">
                                <option value="">All Coordinators</option>
                                <?php foreach ($options['coordinators'] as $coordinator): ?>
                                <option value="<?= (int) $coordinator['account_id'] ?>"
                                    <?= selected($filters['coordinator'], $coordinator['account_id']) ?>>
                                    <?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="filter-actions">
                        <span id="dashboardFilterStatus" class="muted" role="status" aria-live="polite"></span>
                        <a class="btn btn-secondary" id="resetDashboardFilters"
                            href="<?= h(app_route('dashboard')) ?>"><i class="bi bi-arrow-counterclockwise"></i> Reset
                            Filters</a>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Apply
                            Filters</button>
                    </div>
                </div>
            </form>

            <div class="stats-actions-row">
                <div class="stats-col">
                    <section class="row g-3" aria-label="Dashboard statistics">
                        <?php foreach ($statCards as $card): ?>
                        <?php $isHero = $card['key'] === 'total_cases'; ?>
                        <div class="col-12 col-sm-6 <?= $isCoordinator ? 'col-xl-4' : 'col-xl-4 col-xxl-3' ?>">
                            <article
                                class="sicms-card sicms-stat h-100 accent-<?= h($card['accent']) ?><?= $isHero ? ' stat-hero' : '' ?>"
                                data-stat-card="<?= h($card['key']) ?>">
                                <div class="sicms-stat-top">
                                    <div>
                                        <div class="sicms-stat-label"><?= h($card['label']) ?></div>
                                        <div class="sicms-stat-value" data-stat-value="<?= h($card['key']) ?>">
                                            <?= (int) $card['value'] ?></div>
                                    </div>
                                    <span class="sicms-stat-chip"><i class="bi <?= h($card['icon']) ?>"
                                            aria-hidden="true"></i></span>
                                </div>
                                <div class="sicms-stat-trend"><i class="bi bi-arrow-up-right"></i>
                                    <?= h($card['trend']) ?></div>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </section>
                </div>
                <div class="actions-col">
                    <div class="quick-actions-panel">
                        <div class="section-title"><i class="bi bi-lightning-charge"></i> Quick Actions</div>
                        <div class="quick-grid">
                            <?php foreach ($quickActions as $action): ?>
                            <?php if (allowed_for_role($action, $roleKey)): ?>
                            <a class="btn" href="<?= h($action['href']) ?>">
                                <i class="bi <?= h($action['icon']) ?>"></i>
                                <?= h($action['label']) ?>
                            </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>

            <?php if ($canViewAnalytics): ?>
            <?php if ($isCoordinator): ?>
            <section class="panel" style="margin-top:18px;margin-bottom:18px">
                <div class="section-title"><i class="bi bi-folder-check"></i> My Assigned Cases</div>
                <div class="hearing-table-wrap">
                    <table data-page-size="10">
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant</th>
                                <th>Respondent</th>
                                <th>Classification</th>
                                <th>Status</th>
                                <th>Date Assigned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="coordinatorCaseRows">
                            <?php if (empty($rows)): ?><tr>
                                <td colspan="7">No assigned cases match the selected filters.</td>
                            </tr><?php endif; ?>
                            <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <?= h($row['case_number']) ?>
                                </td>

                                <td>
                                    <?= h($row['complainant_name']) ?>
                                </td>

                                <td>
                                    <?= h($row['respondent_names'] ?: 'Not recorded') ?>
                                </td>

                                <td>
                                    <?= h($row['case_classification']) ?>
                                </td>

                                <td>
                                    <span class="status-pill">
                                        <?= h($row['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= h(date('M d, Y', strtotime($row['assigned_at']))) ?>
                                </td>

                                <td>
                                    <div class="quick-grid" style="width: 160px;">
                                        <a class=" btn btn-primary"
                                            href="web/views/cases/show.php?id=<?= (int) $row['complaint_id'] ?>">
                                            <i class="bi bi-eye"></i>
                                            View Case
                                        </a>

                                        <a class="btn btn-primary"
                                            href="web/views/cases/show.php?id=<?= (int) $row['complaint_id'] ?>#status-actions">
                                            <i class="bi bi-arrow-repeat"></i>
                                            Update Status
                                        </a>

                                        <a class="btn btn-primary"
                                            href="web/views/messages/index.php?conversation_id=<?= (int) $row['complaint_id'] ?>">
                                            <i class="bi bi-chat-dots"></i>
                                            Message
                                        </a>

                                        <a class="btn btn-primary"
                                            href="web/views/hearings/create.php?complaint_id=<?= (int) $row['complaint_id'] ?>">
                                            <i class="bi bi-calendar-plus"></i>
                                            Hearing
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" style="margin-top:18px;margin-bottom:18px">
                <div class="section-title"><i class="bi bi-list-check"></i> Pending Tasks</div>
                <div class="activity-list">
                    <article class="activity-item">
                        <div class="activity-check"><i class="bi bi-send"></i></div>
                        <div>
                            <div class="activity-title"><span
                                    data-task-count="submitted_cases"><?= (int) ($summary['submitted_cases'] ?? 0) ?></span>
                                submitted complaint(s)</div>
                            <div class="activity-description">Awaiting coordinator review or action.</div>
                        </div>
                    </article>
                    <article class="activity-item">
                        <div class="activity-check"><i class="bi bi-pencil"></i></div>
                        <div>
                            <div class="activity-title"><span
                                    data-task-count="returned_for_revision_cases"><?= (int) ($summary['returned_for_revision_cases'] ?? 0) ?></span>
                                returned for revision</div>
                            <div class="activity-description">Awaiting revised submissions from students.</div>
                        </div>
                    </article>
                    <article class="activity-item">
                        <div class="activity-check"><i class="bi bi-calendar-day"></i></div>
                        <div>
                            <div class="activity-title"><?= (int) $todaysHearings ?> hearing(s) today</div>
                            <div class="activity-description">Review today's scheduled hearing workload.</div>
                        </div>
                    </article>
            </section>
            <?php endif; ?>
            <section class="dashboard-grid">
                <div class="charts-grid">
                    <?php if ($canViewAnalytics): ?>
                    <article class="panel chart-wide">
                        <div class="section-title"><i class="bi bi-bar-chart"></i> Cases Filed per Month</div>
                        <div class="section-subtitle">Filings for the last 12 months</div>
                        <div class="chart-box"><canvas id="casesByMonth"></canvas></div>
                    </article>
                    <article class="panel">
                        <div class="section-title"><i class="bi bi-pie-chart"></i> Cases by Status</div>
                        <div class="chart-box"><canvas id="casesByStatus"></canvas></div>
                    </article>
                    <article class="panel">
                        <div class="section-title"><i class="bi bi-bar-chart"></i> Cases by Classification</div>
                        <div class="chart-box"><canvas id="casesByClassification"></canvas></div>
                    </article>
                    <?php if (!$isCoordinator): ?>
                    <article class="panel">
                        <div class="section-title"><i class="bi bi-building"></i> Cases per College</div>
                        <div class="chart-box"><canvas id="casesByCollege"></canvas></div>
                    </article>
                    <article class="panel">
                        <div class="section-title"><i class="bi bi-gender-ambiguous"></i> Sex Distribution</div>
                        <div class="chart-box"><canvas id="casesBySex"></canvas></div>
                        <div class="section-subtitle">Based on the linked student account records.</div>
                    </article>
                    <article class="panel chart-wide">
                        <div class="section-title"><i class="bi bi-calendar-range"></i> Hearings per Month</div>
                        <div class="section-subtitle">Scheduled hearings, last 12 months</div>
                        <div class="chart-box"><canvas id="hearingsByMonth"></canvas></div>
                    </article>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <aside class="side-stack">
                    <?php if ($canViewAnalytics): ?>
                    <section class="panel">
                        <div class="section-title"><i class="bi bi-clock-history"></i> Recent Activity</div>
                        <div class="activity-list">
                            <?php if (empty($recentActivities)): ?>
                            <div class="empty-state">No recent activity found.</div>
                            <?php endif; ?>

                            <?php foreach ($recentActivities as $activity): ?>
                            <article class="activity-item">
                                <div class="activity-check"><i class="bi bi-check2"></i></div>
                                <div>
                                    <div class="activity-title"><?= h($activity['action']) ?></div>
                                    <div class="activity-description"><?= h($activity['description']) ?></div>
                                    <div class="activity-time"><?= h(format_time_ago($activity['created_at'])) ?></div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($canViewHearings): ?>
                    <section class="panel">
                        <div class="section-title"><i class="bi bi-calendar-event"></i> Upcoming Hearings</div>
                        <div class="hearing-table-wrap">
                            <table class="hearing-table">
                                <thead>
                                    <tr>
                                        <th>Case Number</th>
                                        <?php if ($isCoordinator): ?><th>Complainant</th><?php endif; ?>
                                        <th>Schedule</th>
                                        <th>Venue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($upcomingHearings)): ?>
                                    <tr>
                                        <td colspan="<?= $isCoordinator ? 4 : 3 ?>">No upcoming hearings.</td>
                                    </tr>
                                    <?php endif; ?>

                                    <?php foreach ($upcomingHearings as $hearing): ?>
                                    <tr>
                                        <td><?= h($hearing['case_number']) ?></td>
                                        <?php if ($isCoordinator): ?><td><?= h($hearing['complainant_name']) ?></td>
                                        <?php endif; ?>
                                        <td><?= h(date('M d, h:i A', strtotime($hearing['hearing_datetime']))) ?></td>
                                        <td><?= h($hearing['venue']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:12px"><a class="btn btn-secondary"
                                href="<?= h(app_route('hearings.index')) ?>">View All Hearings</a></div>
                    </section>
                    <?php endif; ?>
                    <?php if ($isCoordinator): ?>
                    <section class="panel">
                        <div class="section-title"><i class="bi bi-bell"></i> Recent Notifications</div>
                        <div class="activity-list">
                            <?php if (empty($recentNotifications)): ?><div class="empty-state">No recent notifications.
                            </div><?php endif; ?>
                            <?php foreach ($recentNotifications as $notification): ?>
                            <a class="notification-item" href="<?= h(app_route('notifications.index')) ?>"><span
                                    class="notification-icon"><i class="bi bi-info-circle"></i></span><span><span
                                        class="notification-title"><?= h($notification['title']) ?></span><span
                                        class="notification-message"><?= h($notification['message']) ?></span><span
                                        class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span></span></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </aside>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const dateTarget = document.getElementById('currentDateTime');

    function updateDateTime() {
        if (!dateTarget) {
            return;
        }

        dateTarget.textContent = new Intl.DateTimeFormat('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short'
        }).format(new Date());
    }

    updateDateTime();
    setInterval(updateDateTime, 30000);

    const animatedStatTargets = new Set();

    function animateStatValue(el) {
        if (animatedStatTargets.has(el)) return;
        animatedStatTargets.add(el);
        const target = parseInt(el.textContent, 10) || 0;
        const duration = 700;
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased);
            if (progress < 1) requestAnimationFrame(tick);
        }

        requestAnimationFrame(tick);
    }

    document.querySelectorAll('[data-stat-value]').forEach(animateStatValue);

    let dashboardChartData =
        <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const chartPalette = ['#1A9D00', '#0d7b66', '#4338ca', '#e0a800', '#be123c', '#557a95', '#7e22ce'];
    const dashboardCharts = {};

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6b7a67';

    const chartTooltipStyle = {
        backgroundColor: '#123c1b',
        titleColor: '#ffffff',
        bodyColor: 'rgba(255, 255, 255, .86)',
        cornerRadius: 10,
        padding: 10,
        displayColors: false
    };

    const doughnutCenterLabel = {
        id: 'doughnutCenterLabel',
        afterDraw(chart) {
            if (chart.config.type !== 'doughnut') return;
            const area = chart.chartArea;
            if (!area) return;
            const total = (chart.data.datasets[0]?.data || []).reduce((sum, value) => sum + (+value || 0), 0);
            const centerLabel = chart.config.options.centerLabel || 'TOTAL CASES';
            const centerX = (area.left + area.right) / 2;
            const centerY = (area.top + area.bottom) / 2;
            const ctx = chart.ctx;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#123c1b';
            ctx.font = '800 26px ' + Chart.defaults.font.family;
            ctx.fillText(String(total), centerX, centerY - 8);
            ctx.fillStyle = '#6b7a67';
            ctx.font = '600 11px ' + Chart.defaults.font.family;
            ctx.fillText(centerLabel, centerX, centerY + 14);
            ctx.restore();
        }
    };

    const barValueLabels = {
        id: 'barValueLabels',
        afterDatasetsDraw(chart) {
            if (chart.config.type !== 'bar' || !chart.options.showValues) return;
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data.length) return;
            const ctx = chart.ctx;
            const horizontal = chart.config.options.indexAxis === 'y';
            ctx.save();
            ctx.font = '700 11px ' + Chart.defaults.font.family;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';
            meta.data.forEach((bar, index) => {
                const value = +chart.data.datasets[0].data[index];
                if (!value) return;
                ctx.fillStyle = '#40513d';
                const x = horizontal ? (bar.x + 10) : bar.x;
                const y = horizontal ? bar.y : (bar.y - 7);
                if (horizontal) ctx.textAlign = 'left';
                ctx.fillText(String(value), x, y);
            });
            ctx.restore();
        }
    };

    function lineGradient(context) {
        const {
            ctx,
            chartArea
        } = context.chart;
        if (!chartArea) return 'rgba(26, 157, 0, .16)';
        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        gradient.addColorStop(0, 'rgba(26, 157, 0, .30)');
        gradient.addColorStop(1, 'rgba(26, 157, 0, .01)');
        return gradient;
    }

    function makeChart(id, type, options = {}) {
        let canvas = document.getElementById(id);
        const data = dashboardChartData[id] || {
            labels: [],
            values: []
        };

        if (!canvas) return;
        const box = canvas.parentElement;
        dashboardCharts[id]?.destroy();
        delete dashboardCharts[id];
        box.querySelector('.dashboard-chart-empty')?.remove();

        if (data.labels.length === 0) {
            canvas.hidden = true;
            const empty = document.createElement('div');
            empty.className = 'empty-state dashboard-chart-empty';
            empty.textContent = 'No data available for the selected filters.';
            box.appendChild(empty);
            return;
        }
        canvas.hidden = false;

        const dataset = {
            label: 'Total',
            data: data.values,
            borderWidth: type === 'line' ? 3 : 0
        };

        if (type === 'line') {
            Object.assign(dataset, {
                borderColor: '#1A9D00',
                backgroundColor: lineGradient,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointBackgroundColor: '#1A9D00',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            });
        } else if (type === 'bar') {
            Object.assign(dataset, {
                backgroundColor: chartPalette,
                borderRadius: 8,
                maxBarThickness: 36
            });
        } else if (type === 'doughnut') {
            Object.assign(dataset, {
                backgroundColor: chartPalette,
                borderWidth: 0,
                hoverOffset: 8,
                spacing: 3
            });
        }

        const valueAxisKey = options.indexAxis === 'y' ? 'x' : 'y';
        const categoryAxisKey = options.indexAxis === 'y' ? 'y' : 'x';
        const config = {
            type,
            data: {
                labels: data.labels,
                datasets: [dataset]
            },
            options: {
                indexAxis: options.indexAxis || 'x',
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type === 'doughnut',
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 16
                        }
                    },
                    tooltip: chartTooltipStyle
                },
                scales: type === 'doughnut' ? {} : {
                    [categoryAxisKey]: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                    [valueAxisKey]: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: 'rgba(219, 231, 216, .55)',
                            drawTicks: false
                        },
                        ticks: {
                            precision: 0,
                            padding: 8
                        }
                    }
                }
            },
            plugins: type === 'doughnut' ? [doughnutCenterLabel] : (type === 'bar' && options.showValues ? [barValueLabels] : [])
        };

        if (type === 'doughnut') config.options.cutout = '68%';
        else if (type === 'line') config.options.interaction = {
            mode: 'index',
            intersect: false
        };

        config.options.centerLabel = options.centerLabel || 'TOTAL CASES';
        if (options.showValues) config.options.showValues = true;

        dashboardCharts[id] = new Chart(canvas, config);
    }

    makeChart('casesByMonth', 'bar', {
        showValues: true
    });
    makeChart('casesByStatus', 'doughnut');
    makeChart('casesByClassification', 'bar');
    makeChart('casesByCollege', 'bar', {
        indexAxis: 'y',
        showValues: true
    });
    makeChart('hearingsByMonth', 'line');
    makeChart('casesBySex', 'doughnut', {
        centerLabel: 'COMPLAINANTS'
    });

    const dashboardFilters = document.getElementById('dashboardFilters');
    const dashboardFilterStatus = document.getElementById('dashboardFilterStatus');
    const resetDashboardFilters = document.getElementById('resetDashboardFilters');
    const coordinatorCaseRows = document.getElementById('coordinatorCaseRows');
    const dashboardFiltersToggle = document.getElementById('dashboardFiltersToggle');
    const dashboardFiltersPanel = document.getElementById('dashboardFiltersPanel');
    const dashboardFiltersCount = document.getElementById('dashboardFiltersCount');
    let dashboardRequest;

    function closeDashboardFilters() {
        if (!dashboardFiltersPanel || !dashboardFiltersToggle) return;
        dashboardFiltersPanel.classList.remove('open');
        dashboardFiltersToggle.setAttribute('aria-expanded', 'false');
    }

    function updateDashboardFilterButton() {
        if (!dashboardFilters || !dashboardFiltersToggle) return;
        const activeCount = [...new FormData(dashboardFilters).entries()].filter(([, value]) => String(value).trim() !==
            '').length;
        dashboardFilters.classList.toggle('is-active', activeCount > 0);
        if (dashboardFiltersCount) {
            dashboardFiltersCount.hidden = activeCount === 0;
            dashboardFiltersCount.textContent = activeCount;
        }
    }

    dashboardFiltersToggle?.addEventListener('click', event => {
        event.stopPropagation();
        const isOpen = dashboardFiltersPanel.classList.toggle('open');
        dashboardFiltersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', event => {
        if (!dashboardFiltersPanel?.classList.contains('open')) return;
        if (!dashboardFiltersPanel.contains(event.target)) closeDashboardFilters();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeDashboardFilters();
    });
    window.addEventListener('pageshow', () => updateDashboardFilterButton());
    updateDashboardFilterButton();

    const escapeDashboardHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    } [character]));

    function renderCoordinatorCases(rows) {
        if (!coordinatorCaseRows) return;

        coordinatorCaseRows.innerHTML = rows.length ?
            rows.map(item => `
            <tr>
                <td>
                    ${escapeDashboardHtml(item.case_number)}
                </td>

                <td>
                    ${escapeDashboardHtml(item.complainant_name)}
                </td>

                <td>
                    ${escapeDashboardHtml(item.respondent_names || 'Not recorded')}
                </td>

                <td>
                    ${escapeDashboardHtml(item.case_classification)}
                </td>

                <td>
                    <span class="status-pill">
                        ${escapeDashboardHtml(item.status)}
                    </span>
                </td>

                <td>
                    ${escapeDashboardHtml(
                        new Date(
                            item.assigned_at.replace(' ', 'T')
                        ).toLocaleDateString('en-US', {
                            month: 'short',
                            day: '2-digit',
                            year: 'numeric'
                        })
                    )}
                </td>

                <td>
                    <div class="quick-grid">
                        <a
                            class="btn btn-primary"
                            href="web/views/cases/show.php?id=${+item.complaint_id}"
                        >
                            <i class="bi bi-eye"></i>
                            View Case
                        </a>

                        <a
                            class="btn btn-primary"
                            href="web/views/cases/show.php?id=${+item.complaint_id}#status-actions"
                        >
                            <i class="bi bi-arrow-repeat"></i>
                            Update Status
                        </a>

                        <a
                            class="btn btn-primary"
                            href="web/views/messages/index.php?conversation_id=${+item.complaint_id}"
                        >
                            <i class="bi bi-chat-dots"></i>
                            Message
                        </a>

                        <a
                            class="btn btn-primary"
                            href="web/views/hearings/create.php?complaint_id=${+item.complaint_id}"
                        >
                            <i class="bi bi-calendar-plus"></i>
                            Hearing
                        </a>
                    </div>
                </td>
            </tr>
        `).join('') :
            `
            <tr>
                <td colspan="7">
                    No assigned cases match the selected filters.
                </td>
            </tr>
        `;
    }

    async function applyDashboardFilters(reset = false) {
        if (!dashboardFilters) return;
        if (reset) dashboardFilters.reset();
        dashboardRequest?.abort();
        dashboardRequest = new AbortController();
        const params = new URLSearchParams(new FormData(dashboardFilters));
        params.set('ajax', 'dashboard');
        dashboardFilters.classList.add('sicms-ajax-loading');
        dashboardFilterStatus.textContent = 'Updating dashboard...';

        try {
            const response = await fetch(`${dashboardFilters.action}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: dashboardRequest.signal,
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message ||
                'Unable to update the dashboard.');
            Object.entries(payload.stats).forEach(([key, value]) => {
                const target = document.querySelector(`[data-stat-value="${key}"]`);
                if (target) target.textContent = value;
                const task = document.querySelector(`[data-task-count="${key}"]`);
                if (task) task.textContent = value;
            });
            renderCoordinatorCases(payload.rows || []);
            dashboardChartData = payload.charts;
            makeChart('casesByMonth', 'bar', {
                showValues: true
            });
            makeChart('casesByStatus', 'doughnut');
            makeChart('casesByClassification', 'bar');
            makeChart('casesByCollege', 'bar', {
                indexAxis: 'y',
                showValues: true
            });
            makeChart('hearingsByMonth', 'line');
            makeChart('casesBySex', 'doughnut', {
                centerLabel: 'COMPLAINANTS'
            });
            params.delete('ajax');
            const query = params.toString();
            history.replaceState({}, '', query ? `${dashboardFilters.action}?${query}` : dashboardFilters.action);
            dashboardFilterStatus.textContent = 'Dashboard updated.';
        } catch (error) {
            if (error.name !== 'AbortError') dashboardFilterStatus.textContent = error.message;
        } finally {
            dashboardFilters.classList.remove('sicms-ajax-loading');
            updateDashboardFilterButton();
        }
    }

    dashboardFilters?.addEventListener('submit', event => {
        event.preventDefault();
        applyDashboardFilters();
    });
    resetDashboardFilters?.addEventListener('click', event => {
        event.preventDefault();
        applyDashboardFilters(true);
    });
    </script>
    <script src="<?= h(app_url('web/views/layout/system.js')) ?>" defer></script>
</body>

</html>