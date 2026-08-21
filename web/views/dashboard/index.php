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
    'sexDistribution' => ['labels' => [], 'values' => []],
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
    <link rel="stylesheet" href="web/views/layout/style.css">
    <link rel="stylesheet" href="web/views/layout/sidebar.css">
    <link rel="stylesheet" href="web/views/layout/accounts.css">
    <link rel="stylesheet" href="web/views/layout/system.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>

        <main class="main-panel <?= !$canViewAnalytics ? 'student-main-panel' : '' ?>">
            <header class="topbar">
                <div>
                    <div class="welcome-label">Welcome back, <?= h($displayName) ?></div>
                    <h1 class="page-title"><?= $isCoordinator ? 'Coordinator Workspace' : 'SDRU Case Management Dashboard' ?></h1>
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
                                <a class="notification-item<?= (int) $notification['is_read'] === 0 ? ' unread' : '' ?>" href="<?= h(app_route('notifications.index')) ?>">
                                    <span class="notification-icon"><i class="bi bi-info-circle"></i></span>
                                    <span>
                                        <span class="notification-title"><?= h($notification['title']) ?></span>
                                        <span class="notification-message"><?= h($notification['message']) ?></span>
                                        <span class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>

                            <div class="dropdown-footer">
                                <form method="POST" action="<?= h(app_route('notifications.index')) ?>">
                                    <?= Security::csrfField() ?>
                                    <button class="text-button" type="submit" name="notification_action" value="mark_all">Mark all as read</button>
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
                        <!-- <div class="section-title"><i class="bi bi-person-circle"></i> Welcome, <?= h($displayName) ?></div> -->
                        <p class="activity-description">Submit a complaint or check the latest updates from SDRU.</p>
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
                                <div class="empty-state"><strong>You have no complaints yet.</strong><br><span>Submit a complaint when you need assistance from SDRU.</span></div>
                            <?php endif; ?>
                            <?php foreach ($studentCases as $studentCase): ?>
                                <article class="student-case-card">
                                    <div>
                                        <a class="student-case-number" href="web/views/complaints/case_details.php?id=<?= (int) $studentCase['complaint_id'] ?>"><?= h($studentCase['case_classification']) ?></a>
                                        <div class="student-case-meta"><?= h($studentCase['case_number']) ?> · Submitted <?= h(date('M d, Y', strtotime($studentCase['submitted_at']))) ?></div>
                                    </div>
                                    <span class="status-pill"><?= h($studentCase['status']) ?></span>
                                    <div class="student-case-action"><a class="btn btn-secondary" href="web/views/complaints/case_details.php?id=<?= (int) $studentCase['complaint_id'] ?>"><i class="bi bi-eye"></i> View Details</a></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="student-panel">
                        <div class="section-title"><i class="bi bi-bell"></i> Recent Notifications</div>
                        <div class="activity-list">
                            <?php if (empty($recentNotifications)): ?><div class="empty-state">You have no notifications yet.</div><?php endif; ?>
                            <?php foreach ($recentNotifications as $notification): ?>
                                <a class="notification-item" href="<?= h(app_route('notifications.index')) ?>">
                                    <span class="notification-icon"><i class="bi bi-info-circle"></i></span>
                                    <span><span class="notification-title"><?= h($notification['title']) ?></span><span class="notification-message"><?= h($notification['message']) ?></span><span class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </section>
            <?php endif; ?>

            <?php if ($canViewAnalytics): ?>
                <section class="stats-grid" aria-label="Dashboard statistics">
                    <?php foreach ($statCards as $card): ?>
                        <article class="stat-card accent-<?= h($card['accent']) ?>" data-stat-card="<?= h($card['key']) ?>">
                            <div class="stat-top">
                                <div>
                                    <div class="stat-label"><?= h($card['label']) ?></div>
                                    <div class="stat-value" data-stat-value="<?= h($card['key']) ?>"><?= (int) $card['value'] ?></div>
                                </div>
                                <div class="stat-icon"><i class="bi <?= h($card['icon']) ?>" aria-hidden="true"></i></div>
                            </div>
                            <div class="stat-trend"><i class="bi bi-arrow-up-right"></i> <?= h($card['trend']) ?></div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <form class="filters-card" id="dashboardFilters" method="GET" action="<?= h(app_route('dashboard')) ?>">
                    <div class="section-title"><i class="bi bi-funnel"></i> Dashboard Filters</div>
                    <div class="filter-grid">
                        <?php if ($isCoordinator): ?>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status"><option value="">All Statuses</option><?php foreach ($options['statuses'] as $status): ?><option value="<?= h($status) ?>" <?= selected($filters['status'], $status) ?>><?= h($status) ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="field">
                            <label for="classification">Classification</label>
                            <select id="classification" name="classification"><option value="">All Classifications</option><?php foreach ($options['classifications'] as $classification): ?><option value="<?= h($classification) ?>" <?= selected($filters['classification'], $classification) ?>><?= h($classification) ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="field"><label for="date_from">Date From</label><input id="date_from" type="date" name="date_from" value="<?= h($filters['date_from']) ?>"></div>
                        <div class="field"><label for="date_to">Date To</label><input id="date_to" type="date" name="date_to" value="<?= h($filters['date_to']) ?>"></div>
                        <?php else: ?>
                        <div class="field">
                            <label for="academic_year">Academic Year</label>
                            <input id="academic_year" type="number" name="year" min="2000" max="2100" value="<?= h($filters['year']) ?>" placeholder="<?= h(date('Y')) ?>">
                        </div>
                        <div class="field">
                            <label for="college">College</label>
                            <select id="college" name="college">
                                <option value="">All Colleges</option>
                                <?php foreach ($options['colleges'] as $college): ?>
                                    <option value="<?= h($college) ?>" <?= selected($filters['college'], $college) ?>><?= h($college) ?></option>
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
                                    <option value="<?= h($status) ?>" <?= selected($filters['status'], $status) ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="classification">Classification</label>
                            <select id="classification" name="classification">
                                <option value="">All Classifications</option>
                                <?php foreach ($options['classifications'] as $classification): ?>
                                    <option value="<?= h($classification) ?>" <?= selected($filters['classification'], $classification) ?>><?= h($classification) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="coordinator">Coordinator</label>
                            <select id="coordinator" name="coordinator">
                                <option value="">All Coordinators</option>
                                <?php foreach ($options['coordinators'] as $coordinator): ?>
                                    <option value="<?= (int) $coordinator['account_id'] ?>" <?= selected($filters['coordinator'], $coordinator['account_id']) ?>>
                                        <?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="filter-actions">
                        <span id="dashboardFilterStatus" class="muted" role="status" aria-live="polite"></span>
                        <a class="btn btn-secondary" id="resetDashboardFilters" href="<?= h(app_route('dashboard')) ?>"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Apply Filters</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($canViewAnalytics): ?>
            <?php if ($isCoordinator): ?>
            <section class="panel" style="margin-bottom:18px">
                <div class="section-title"><i class="bi bi-folder-check"></i> My Assigned Cases</div>
                <div class="hearing-table-wrap">
                    <table data-page-size="10">
                        <thead><tr><th>Case Number</th><th>Complainant</th><th>Respondent</th><th>Classification</th><th>Status</th><th>Date Assigned</th><th>Actions</th></tr></thead>
                        <tbody id="coordinatorCaseRows">
                        <?php if (empty($rows)): ?><tr><td colspan="7">No assigned cases match the selected filters.</td></tr><?php endif; ?>
                        <?php foreach ($rows as $row): ?><tr><td><?= h($row['case_number']) ?></td><td><?= h($row['complainant_name']) ?></td><td><?= h($row['respondent_names'] ?: 'Not recorded') ?></td><td><?= h($row['case_classification']) ?></td><td><span class="status-pill"><?= h($row['status']) ?></span></td><td><?= h(date('M d, Y', strtotime($row['assigned_at']))) ?></td><td><div class="quick-grid"><a class="btn btn-secondary" href="web/views/cases/show.php?id=<?= (int) $row['complaint_id'] ?>"><i class="bi bi-eye"></i> View Case</a><a class="btn btn-secondary" href="web/views/cases/show.php?id=<?= (int) $row['complaint_id'] ?>#status-actions"><i class="bi bi-arrow-repeat"></i> Update Status</a><a class="btn btn-secondary" href="web/views/messages/index.php?conversation_id=<?= (int) $row['complaint_id'] ?>"><i class="bi bi-chat-dots"></i> Message</a><a class="btn btn-secondary" href="web/views/hearings/create.php?complaint_id=<?= (int) $row['complaint_id'] ?>"><i class="bi bi-calendar-plus"></i> Hearing</a></div></td></tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="dashboard-grid" style="margin-bottom:18px">
                <section class="panel"><div class="section-title"><i class="bi bi-list-check"></i> Pending Tasks</div><div class="activity-list">
                    <article class="activity-item"><div class="activity-check"><i class="bi bi-send"></i></div><div><div class="activity-title"><span data-task-count="submitted_cases"><?= (int) ($summary['submitted_cases'] ?? 0) ?></span> submitted complaint(s)</div><div class="activity-description">Awaiting coordinator review or action.</div></div></article>
                    <article class="activity-item"><div class="activity-check"><i class="bi bi-pencil"></i></div><div><div class="activity-title"><span data-task-count="returned_for_revision_cases"><?= (int) ($summary['returned_for_revision_cases'] ?? 0) ?></span> returned for revision</div><div class="activity-description">Awaiting revised submissions from students.</div></div></article>
                    <article class="activity-item"><div class="activity-check"><i class="bi bi-calendar-day"></i></div><div><div class="activity-title"><?= (int) $todaysHearings ?> hearing(s) today</div><div class="activity-description">Review today's scheduled hearing workload.</div></div></article>
                </div></section>
                <section class="quick-actions"><div class="section-title"><i class="bi bi-lightning-charge"></i> Quick Actions</div><div class="quick-grid"><?php foreach ($quickActions as $action): ?><?php if (allowed_for_role($action, $roleKey)): ?><a class="btn btn-secondary" href="<?= h($action['href']) ?>"><i class="bi <?= h($action['icon']) ?>"></i><?= h($action['label']) ?></a><?php endif; ?><?php endforeach; ?></div></section>
            </section>
            <?php endif; ?>
            <section class="dashboard-grid">
                <div class="charts-grid">
                    <?php if ($canViewAnalytics): ?>
                        <article class="panel chart-wide">
                            <div class="section-title"><i class="bi bi-graph-up"></i> Cases per Month</div>
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
                            <div class="chart-box">
                                <div class="empty-state">Sex data is not available in the current complaint records.</div>
                            </div>
                        </article>
                        <article class="panel chart-wide">
                            <div class="section-title"><i class="bi bi-calendar-range"></i> Hearing Schedule</div>
                            <div class="chart-box"><canvas id="hearingsByMonth"></canvas></div>
                        </article>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <aside class="side-stack">
                    <?php if (!$isCoordinator): ?><section class="quick-actions">
                        <div class="section-title"><i class="bi bi-lightning-charge"></i> Quick Actions</div>
                        <div class="quick-grid">
                            <?php foreach ($quickActions as $action): ?>
                                <?php if (allowed_for_role($action, $roleKey)): ?>
                                    <a class="btn btn-secondary" href="<?= h($action['href']) ?>">
                                        <i class="bi <?= h($action['icon']) ?>"></i>
                                        <?= h($action['label']) ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </section><?php endif; ?>

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
                                            <th>Schedule</th><th>Venue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($upcomingHearings)): ?>
                                            <tr><td colspan="<?= $isCoordinator ? 4 : 3 ?>">No upcoming hearings.</td></tr>
                                        <?php endif; ?>

                                        <?php foreach ($upcomingHearings as $hearing): ?>
                                            <tr>
                                                <td><?= h($hearing['case_number']) ?></td>
                                                <?php if ($isCoordinator): ?><td><?= h($hearing['complainant_name']) ?></td><?php endif; ?>
                                                <td><?= h(date('M d, h:i A', strtotime($hearing['hearing_datetime']))) ?></td>
                                                <td><?= h($hearing['venue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top:12px"><a class="btn btn-secondary" href="<?= h(app_route('hearings.index')) ?>">View All Hearings</a></div>
                        </section>
                    <?php endif; ?>
                    <?php if ($isCoordinator): ?>
                        <section class="panel">
                            <div class="section-title"><i class="bi bi-bell"></i> Recent Notifications</div>
                            <div class="activity-list">
                                <?php if (empty($recentNotifications)): ?><div class="empty-state">No recent notifications.</div><?php endif; ?>
                                <?php foreach ($recentNotifications as $notification): ?>
                                    <a class="notification-item" href="<?= h(app_route('notifications.index')) ?>"><span class="notification-icon"><i class="bi bi-info-circle"></i></span><span><span class="notification-title"><?= h($notification['title']) ?></span><span class="notification-message"><?= h($notification['message']) ?></span><span class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span></span></a>
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

        let dashboardChartData = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const chartPalette = ['#1A9D00', '#123c1b', '#e0a800', '#557a95', '#9c4668', '#59656f', '#0d7b66'];
        const dashboardCharts = {};

        function makeChart(id, type, options = {}) {
            let canvas = document.getElementById(id);
            const data = dashboardChartData[id] || { labels: [], values: [] };

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

            dashboardCharts[id] = new Chart(canvas, {
                type,
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Total',
                        data: data.values,
                        backgroundColor: type === 'line' ? 'rgba(26, 157, 0, 0.16)' : chartPalette,
                        borderColor: '#1A9D00',
                        borderWidth: 2,
                        fill: type === 'line',
                        tension: 0.34
                    }]
                },
                options: {
                    indexAxis: options.indexAxis || 'x',
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: type === 'doughnut', position: 'bottom' }
                    },
                    scales: type === 'doughnut' ? {} : {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        makeChart('casesByMonth', 'line');
        makeChart('casesByStatus', 'doughnut');
        makeChart('casesByClassification', 'bar');
        makeChart('casesByCollege', 'bar', { indexAxis: 'y' });
        makeChart('hearingsByMonth', 'line');

        const dashboardFilters = document.getElementById('dashboardFilters');
        const dashboardFilterStatus = document.getElementById('dashboardFilterStatus');
        const resetDashboardFilters = document.getElementById('resetDashboardFilters');
        const coordinatorCaseRows = document.getElementById('coordinatorCaseRows');
        let dashboardRequest;

        const escapeDashboardHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
        function renderCoordinatorCases(rows) {
            if (!coordinatorCaseRows) return;
            coordinatorCaseRows.innerHTML = rows.length ? rows.map(item => `<tr><td>${escapeDashboardHtml(item.case_number)}</td><td>${escapeDashboardHtml(item.complainant_name)}</td><td>${escapeDashboardHtml(item.respondent_names || 'Not recorded')}</td><td>${escapeDashboardHtml(item.case_classification)}</td><td><span class="status-pill">${escapeDashboardHtml(item.status)}</span></td><td>${escapeDashboardHtml(new Date(item.assigned_at.replace(' ','T')).toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}))}</td><td><div class="quick-grid"><a class="btn btn-secondary" href="web/views/cases/show.php?id=${+item.complaint_id}"><i class="bi bi-eye"></i> View Case</a><a class="btn btn-secondary" href="web/views/cases/show.php?id=${+item.complaint_id}#status-actions"><i class="bi bi-arrow-repeat"></i> Update Status</a><a class="btn btn-secondary" href="web/views/messages/index.php?conversation_id=${+item.complaint_id}"><i class="bi bi-chat-dots"></i> Message</a><a class="btn btn-secondary" href="web/views/hearings/create.php?complaint_id=${+item.complaint_id}"><i class="bi bi-calendar-plus"></i> Hearing</a></div></td></tr>`).join('') : '<tr><td colspan="7">No assigned cases match the selected filters.</td></tr>';
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
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    signal: dashboardRequest.signal,
                });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Unable to update the dashboard.');
                Object.entries(payload.stats).forEach(([key, value]) => {
                    const target = document.querySelector(`[data-stat-value="${key}"]`);
                    if (target) target.textContent = value;
                    const task = document.querySelector(`[data-task-count="${key}"]`);
                    if (task) task.textContent = value;
                });
                renderCoordinatorCases(payload.rows || []);
                dashboardChartData = payload.charts;
                makeChart('casesByMonth', 'line');
                makeChart('casesByStatus', 'doughnut');
                makeChart('casesByClassification', 'bar');
                makeChart('casesByCollege', 'bar', { indexAxis: 'y' });
                makeChart('hearingsByMonth', 'line');
                params.delete('ajax');
                const query = params.toString();
                history.replaceState({}, '', query ? `${dashboardFilters.action}?${query}` : dashboardFilters.action);
                dashboardFilterStatus.textContent = 'Dashboard updated.';
            } catch (error) {
                if (error.name !== 'AbortError') dashboardFilterStatus.textContent = error.message;
            } finally {
                dashboardFilters.classList.remove('sicms-ajax-loading');
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
