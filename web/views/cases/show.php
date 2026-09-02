<?php
require_once __DIR__ . '/../../controllers/CaseController.php';
require_once __DIR__ . '/../../helpers/Courses.php';

$controller = new CaseController();
$complaintId = (int) ($_GET['id'] ?? 0);
$viewData = $controller->show($complaintId);

$user = $viewData['user'];
$case = $viewData['case'];
$respondents = $viewData['respondents'];
$witnesses = $viewData['witnesses'];
$evidence = $viewData['evidence'];
$history = $viewData['history'];
$coordinators = $viewData['coordinators'];
$messages = $viewData['messages'];
$messageReceiver = $viewData['messageReceiver'];
$message = $viewData['message'];
$errors = $viewData['errors'];
$resubmission = $viewData['resubmission'];
$caseStatus = $case['status'] ?? '';
$statusMeta = [
    'Submitted' => ['slug' => 'submitted', 'icon' => 'bi-send'],
    'Verified' => ['slug' => 'verified', 'icon' => 'bi-shield-check'],
    'Returned for Revision' => ['slug' => 'returned', 'icon' => 'bi-arrow-return-left'],
    'Rejected' => ['slug' => 'rejected', 'icon' => 'bi-x-circle'],
    'Resolved' => ['slug' => 'resolved', 'icon' => 'bi-check2-circle'],
    'Archived' => ['slug' => 'archived', 'icon' => 'bi-archive'],
];
$caseStatusSlug = $statusMeta[$caseStatus]['slug'] ?? 'submitted';
$caseStatusIcon = $statusMeta[$caseStatus]['icon'] ?? 'bi-tag';
$caseUpdatedAt = $case['updated_at'] ?? $case['submitted_at'] ?? null;
$viewerRoleKey = strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));
$canManageCase = $viewerRoleKey !== 'coordinator'
    || ((int) ($case['assigned_coordinator_account_id'] ?? 0) === (int) ($user['account_id'] ?? 0));

$controller->clearFlash();

$revisionFieldLabels = [
    'complaint_details' => 'Complaint Description',
    'incident_date' => 'Incident Date', 'incident_time' => 'Incident Time',
    'incident_location' => 'Incident Location', 'respondents' => 'Respondent Information',
    'witnesses' => 'Witness Information', 'evidence' => 'Supporting Evidence',
];

function h($value) {
    return htmlspecialchars((string) $value);
}

function person_name($first, $last) {
    $name = trim(($first ?? '') . ' ' . ($last ?? ''));
    return $name !== '' ? $name : 'Unassigned';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        align-items: stretch;
        justify-content: flex-start;
        background: #f5f7f4;
        padding: 0;
    }

    .case-page {
        min-height: 100vh;
        width: 100%;
    }

    .case-header {
        background: #123c1b;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 22px 30px;
    }

    .case-header h1 {
        font-size: 24px;
        margin-bottom: 4px;
    }

    .case-header p {
        color: #dbe9d9;
        font-size: 13px;
    }

    .case-header a {
        background: #fff;
        border-radius: 8px;
        color: #123c1b;
        font-size: 14px;
        padding: 10px 14px;
        text-decoration: none;
    }

    .revision-banner {
        align-items: center;
        background: #fffbeb;
        border: 1px solid #f5d78e;
        border-left: 4px solid #d9a406;
        border-radius: 8px;
        color: #7a5c00;
        display: flex;
        flex-wrap: wrap;
        gap: 8px 12px;
        margin-bottom: 18px;
        padding: 12px 16px;
    }

    .revision-banner strong {
        font-size: 14px;
    }

    .revision-banner .revised-parts {
        font-size: 13px;
    }

    .revision-banner .revised-parts .chip {
        background: #fff3cd;
        border: 1px solid #eed08a;
        border-radius: 20px;
        display: inline-block;
        font-size: 12px;
        margin: 0 4px 2px 0;
        padding: 2px 10px;
    }

    .case-wrap {
        max-width: 1180px;
        margin: 0 auto;
        padding: 24px;
    }

    .status-banner {
        --status-color: #5b6b5c;
        --status-bg: #edf4eb;
        align-items: center;
        background: linear-gradient(180deg, #ffffff, #f9fcf8);
        border: 1px solid #dce5da;
        border-left: 5px solid var(--status-color);
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(18, 60, 27, 0.07);
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        justify-content: space-between;
        margin-bottom: 18px;
        padding: 14px 18px;
        transition: border-color 0.3s ease, box-shadow 0.4s ease;
    }

    .status-banner[data-status="submitted"] {
        --status-color: #1c6dd0;
        --status-bg: #eaf3fd;
    }

    .status-banner[data-status="verified"] {
        --status-color: #1a9d00;
        --status-bg: #e9f8e4;
    }

    .status-banner[data-status="returned"] {
        --status-color: #936d00;
        --status-bg: #fbf3dd;
    }

    .status-banner[data-status="rejected"] {
        --status-color: #b42318;
        --status-bg: #fdeceb;
    }

    .status-banner[data-status="resolved"] {
        --status-color: #157000;
        --status-bg: #e6f5e0;
    }

    .status-banner[data-status="archived"] {
        --status-color: #59635a;
        --status-bg: #eef1ee;
    }

    .status-banner-main {
        align-items: center;
        display: flex;
        gap: 13px;
        min-width: 0;
    }

    .status-banner-badge {
        align-items: center;
        background: var(--status-bg);
        border-radius: 50%;
        color: var(--status-color);
        display: flex;
        flex: 0 0 44px;
        font-size: 20px;
        height: 44px;
        justify-content: center;
        width: 44px;
    }

    .status-banner-label {
        color: #536052;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        margin-bottom: 3px;
        text-transform: uppercase;
    }

    .status-pill {
        align-items: center;
        background: var(--status-bg);
        border-radius: 999px;
        color: var(--status-color);
        display: inline-flex;
        font-size: 14px;
        font-weight: 700;
        gap: 6px;
        padding: 5px 14px;
    }

    .status-banner-title {
        font-size: 14px;
    }

    .status-banner-side {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px 14px;
    }

    .status-banner-updated {
        color: #6b7969;
        font-size: 12.5px;
    }

    .status-banner.flash {
        animation: sicmsStatusFlash 0.8s ease;
    }

    @keyframes sicmsStatusFlash {
        0% {
            box-shadow: 0 0 0 0 var(--status-color);
        }
        100% {
            box-shadow: 0 0 0 16px rgba(18, 60, 27, 0);
        }
    }

    .grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 18px;
    }

    .panel {
        background: #fff;
        border: 1px solid #dce5da;
        border-radius: 8px;
        padding: 18px;
        margin-bottom: 18px;
    }

    .panel h2 {
        color: #172017;
        font-size: 18px;
        margin-bottom: 14px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .detail {
        border-bottom: 1px solid #edf4eb;
        padding-bottom: 10px;
    }

    .detail.full {
        grid-column: 1 / -1;
    }

    .label {
        color: #536052;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .value {
        color: #172017;
        font-size: 14px;
        line-height: 1.5;
    }

    .status {
        background: #edf4eb;
        border-radius: 999px;
        color: #123c1b;
        display: inline-block;
        font-size: 12px;
        padding: 5px 10px;
    }

    .alert {
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 18px;
        padding: 12px 14px;
    }

    .alert-success {
        background: #f0fdf0;
        border: 1px solid #1A9D00;
        color: #137500;
    }

    .alert-error {
        background: #fff5f5;
        border: 1px solid #dc3545;
        color: #b42318;
    }

    .action-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 14px;
    }

    .revision-form {
        border-top: 1px solid #dce7d9;
        margin-top: 16px;
        padding-top: 16px;
    }

    .revision-form h3 {
        color: #123c1b;
        font-size: 14px;
        margin: 0;
    }

    .revision-form p {
        color: #62705f;
        font-size: 12px;
        margin: 4px 0 10px;
    }

    .revision-fields {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .revision-field {
        align-items: center;
        background: #f8fbf7;
        border: 1px solid #dce7d9;
        border-radius: 8px;
        display: flex;
        font-size: 12px;
        gap: 8px;
        padding: 9px;
    }

    .revision-field input {
        height: 16px;
        margin: 0;
        width: 16px;
    }

    select,
    textarea,
    input {
        border: 1px solid #b9c7b7;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        padding: 10px 12px;
        width: 100%;
    }

    textarea {
        min-height: 78px;
        resize: vertical;
    }

    .button-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .button-row.two {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .btn {
        border: 0;
        border-radius: 8px;
        color: #fff;
        cursor: pointer;
        font-family: inherit;
        font-size: 13px;
        padding: 10px;
    }

    .btn-verify {
        background: #1A9D00;
    }

    .btn-return {
        background: #936d00;
    }

    .btn-reject {
        background: #b42318;
    }

    .btn-assign {
        background: #123c1b;
    }

    .btn-resolve {
        background: #157000;
    }

    .btn-archive {
        background: #59635a;
    }

    .btn-reopen {
        background: #1c6dd0;
    }

    .btn-secondary {
        background: #eef1ee;
        color: #3f4c3e;
    }

    .btn-secondary:hover {
        background: #e2e7df;
    }

    .staff-actions-extra .btn {
        text-align: center;
        width: 100%;
    }

    .btn:disabled,
    .revision-fieldset:disabled .btn {
        cursor: not-allowed;
        filter: grayscale(0.5);
        opacity: 0.45;
    }

    .case-outcome-panel {
        background: #fbfdf9;
        border: 1px solid #cfe6ca;
        border-left: 4px solid #1a9d00;
        border-radius: 8px;
        padding: 16px 18px;
        margin-top: 8px;
    }

    .case-outcome-panel h2 {
        align-items: center;
        color: #145c00;
        display: flex;
        font-size: 15px;
        gap: 8px;
        margin: 0 0 10px;
    }

    .case-outcome-meta {
        color: #5c6a59;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .case-outcome-text {
        color: #172017;
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .outcome-field {
        margin-bottom: 12px;
    }

    .outcome-field label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .outcome-field textarea {
        border: 1px solid #b9c7b7;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        min-height: 70px;
        padding: 10px 12px;
        width: 100%;
        resize: vertical;
    }

    .case-action-group {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #eef1ed;
    }

    .case-action-group:first-of-type {
        border-top: 0;
        margin-top: 0;
        padding-top: 0;
    }

    .case-action-label {
        color: #5c6a59;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        margin: 0 0 8px;
        text-transform: uppercase;
    }

    .case-modal-overlay {
        -webkit-backdrop-filter: blur(2px);
        align-items: center;
        backdrop-filter: blur(2px);
        background: rgba(15, 24, 16, 0.55);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 20px;
        position: fixed;
        z-index: 3000;
    }

    .case-modal-overlay.open {
        display: flex;
    }

    .case-modal {
        animation: sicmsModalIn 0.18s ease;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.28);
        max-height: calc(100vh - 40px);
        overflow: auto;
        padding: 22px;
        width: min(520px, 100%);
    }

    @keyframes sicmsModalIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
    }

    .case-modal-header {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .case-modal-header h3 {
        color: #123c1b;
        font-size: 17px;
        margin: 0 0 3px;
    }

    .case-modal-header h3 .bi {
        color: #1a9d00;
    }

    .case-modal-header p {
        color: #5c6a59;
        font-size: 12.5px;
        margin: 0;
    }

    .case-modal-close {
        background: #eef1ee;
        border: none;
        border-radius: 8px;
        color: #435241;
        cursor: pointer;
        flex: 0 0 30px;
        font-size: 15px;
        height: 30px;
        line-height: 1;
    }

    .case-modal-body {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .case-modal-body textarea,
    .case-modal-body select {
        border: 1px solid #b9c7b7;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        padding: 10px 12px;
        width: 100%;
        resize: vertical;
    }

    .case-modal-body textarea {
        min-height: 80px;
    }

    .case-modal-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .case-modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .btn-resolve:disabled,
    .btn-archive:disabled {
        cursor: not-allowed;
        opacity: .45;
    }

    .list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .list-item {
        border: 1px solid #edf4eb;
        border-radius: 8px;
        padding: 12px;
    }

    .timeline-item {
        border-left: 3px solid #1A9D00;
        padding: 0 0 16px 12px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-scroll {
        max-height: 1019px;
        overflow-y: auto;
    }

    .muted {
        color: #536052;
        font-size: 13px;
    }

    .message-thread {
        background: #f7faf6;
        border: 1px solid #edf4eb;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 420px;
        overflow-y: auto;
        padding: 16px;
    }

    .message-bubble {
        align-self: flex-start;
        max-width: min(76%, 560px);
    }

    .message-content {
        background: #fff;
        border: 1px solid #dce5da;
        border-radius: 18px 18px 18px 6px;
        color: #1f2a1f;
        line-height: 1.5;
        padding: 10px 13px;
        word-break: break-word;
    }

    .message-bubble.mine {
        align-self: flex-end;
    }

    .message-bubble.mine .message-content {
        background: #1A9D00;
        border-color: #1A9D00;
        border-radius: 18px 18px 6px 18px;
        color: #fff;
    }

    .message-meta {
        color: #536052;
        font-size: 12px;
        margin: 0 6px 6px;
    }

    .message-bubble.mine .message-meta {
        text-align: right;
    }

    .message-composer {
        background: #fff;
        border: 1px solid #dce5da;
        border-radius: 8px;
        display: grid;
        gap: 10px;
        margin-top: 12px;
        padding: 12px;
    }

    .message-composer textarea {
        min-height: 84px;
        resize: vertical;
    }

    .message-send-row {
        align-items: center;
        display: flex;
        gap: 10px;
    }

    .message-receiver-note {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .message-send-row .btn {
        white-space: nowrap;
    }

    @media (max-width: 900px) {
        .case-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .grid,
        .details-grid,
        .button-row {
            grid-template-columns: 1fr;
        }

        .message-bubble {
            max-width: 92%;
        }

        .message-send-row {
            align-items: stretch;
            flex-direction: column;
        }
    }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=2">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-page app-content">
            <?php $pageTitle = $case['case_number']; require __DIR__ . '/../layout/topbar.php'; ?>

            <main class="case-wrap">
                <div style="margin-bottom:14px"><a class="btn btn-secondary"
                        href="<?= h(app_route('cases.index')) ?>"><i class="bi bi-arrow-left"></i> Back to Case
                        Management</a></div>

                <div class="status-banner" id="caseStatusBanner" data-status="<?= h($caseStatusSlug) ?>"
                    data-status-endpoint="<?= h(app_url('web/api/case_status.php')) ?>"
                    data-case-id="<?= (int) $case['complaint_id'] ?>">
                    <div class="status-banner-main">
                        <span class="status-banner-badge"><i class="bi <?= h($caseStatusIcon) ?>"></i></span>
                        <div>
                            <div class="status-banner-label">Current Status</div>
                            <div class="status-banner-title"><span class="status-pill"
                                    id="caseStatusPill"><?= h($caseStatus) ?></span></div>
                        </div>
                    </div>
                    <div class="status-banner-side">
                        <span class="status-banner-updated" id="caseStatusUpdated">Updated
                            <?= $caseUpdatedAt ? h(date('M d, Y h:i A', strtotime($caseUpdatedAt))) : 'â€”' ?></span>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success"><?= h($message) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                    <div><?= h($error) ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($resubmission) && !empty($resubmission['revision_fields'])): ?>
                <div class="revision-banner">
                    <strong><i class="bi bi-arrow-repeat"></i> Resubmitted after revision</strong>
                    <div class="revised-parts">
                        <?= h(date('M d, Y h:i A', strtotime($resubmission['created_at']))) ?>
                        &middot; Revised:
                        <?php foreach ($resubmission['revision_fields'] as $revisedField): ?>
                        <span class="chip"><?= h($revisionFieldLabels[$revisedField] ?? ucwords(str_replace('_', ' ', $revisedField))) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid">
                    <div>
                        <section class="panel">
                            <h2>Complaint Information</h2>
                            <div class="details-grid">
                                <div class="detail">
                                    <div class="label">Complainant</div>
                                    <div class="value"><?= h($case['complainant_name']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Gender</div>
                                    <div class="value"><?= h($case['complainant_gender'] ?: 'Not provided') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Complainant Type</div>
                                    <div class="value"><?= h($case['complainant_type'] ?? 'Student') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Email</div>
                                    <div class="value"><?= h($case['complainant_email']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Contact</div>
                                    <div class="value"><?= h($case['complainant_contact']) ?></div>
                                </div>
                                <?php if (($case['complainant_type'] ?? 'Student') === 'Student'): ?>
                                <div class="detail">
                                    <div class="label">Student Number</div>
                                    <div class="value"><?= h($case['complainant_student_no']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">College</div>
                                    <div class="value"><?= h($case['complainant_college']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Course and Section</div>
                                    <div class="value">
                                        <?= h(trim(($case['complainant_course'] ?? '') . ' ' . (($case['complainant_year_level'] ?? '') ?: Courses::yearLevel($case['complainant_section'] ?? '')) . ' ' . ($case['complainant_section'] ?? '')) ?: $case['complainant_course_year']) ?>
                                    </div>
                                </div>
                                <?php elseif (($case['complainant_type'] ?? '') === 'Employee'): ?>
                                <div class="detail">
                                    <div class="label">Employee Number</div>
                                    <div class="value"><?= h($case['complainant_employee_no']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">College / Office / Department</div>
                                    <div class="value"><?= h($case['complainant_department']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Position</div>
                                    <div class="value"><?= h($case['complainant_position']) ?></div>
                                </div>
                                <?php elseif (($case['complainant_type'] ?? '') === 'Private Individual' && !empty($case['complainant_relationship'])): ?>
                                <div class="detail">
                                    <div class="label">Relationship to CLSU</div>
                                    <div class="value"><?= h($case['complainant_relationship']) ?></div>
                                </div>
                                <?php elseif (($case['complainant_type'] ?? '') === 'Others'): ?>
                                <?php if (!empty($case['complainant_affiliation'])): ?><div class="detail">
                                    <div class="label">Affiliation / Organization</div>
                                    <div class="value"><?= h($case['complainant_affiliation']) ?></div>
                                </div><?php endif; ?>
                                <?php if (!empty($case['complainant_purpose'])): ?><div class="detail">
                                    <div class="label">Relationship or Purpose</div>
                                    <div class="value"><?= h($case['complainant_purpose']) ?></div>
                                </div><?php endif; ?>
                                <?php endif; ?>
                                <div class="detail">
                                    <div class="label">Incident Date</div>
                                    <div class="value">
                                        <?= h(date('M d, Y h:i A', strtotime($case['incident_datetime']))) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Location</div>
                                    <div class="value"><?= h($case['incident_location']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Date Submitted</div>
                                    <div class="value"><?= h(date('M d, Y h:i A', strtotime($case['submitted_at']))) ?>
                                    </div>
                                </div>
                                <div class="detail">
                                    <div class="label">Coordinator</div>
                                    <div class="value">
                                        <?= h(person_name($case['coordinator_first_name'], $case['coordinator_last_name'])) ?>
                                    </div>
                                </div>
                                <div class="detail full">
                                    <div class="label">Details</div>
                                    <div class="value"><?= nl2br(h($case['complaint_details'])) ?></div>
                                </div>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Respondents</h2>
                            <div class="list">
                                <?php foreach ($respondents as $respondent): ?>
                                <?php $rtype = $respondent['respondent_type'] ?? 'Student'; ?>
                                <div class="list-item">
                                    <strong><?= h($respondent['full_name']) ?>
                                        <span class="muted">(<?= h($rtype) ?>)</span></strong>
                                    <?php if (!empty($respondent['gender'])): ?>
                                        <div class="muted">Gender: <?= h($respondent['gender']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($rtype === 'Student'): ?>
                                        <?php if (!empty($respondent['student_no'])): ?>
                                            <div class="muted">Student Number: <?= h($respondent['student_no']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['college'])): ?>
                                            <div class="muted">College: <?= h($respondent['college']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['course_year'])): ?>
                                            <div class="muted">Course and Section: <?= h($respondent['course_year']) ?></div>
                                        <?php endif; ?>
                                    <?php elseif ($rtype === 'Employee'): ?>
                                        <?php if (!empty($respondent['employee_no'])): ?>
                                            <div class="muted">Employee Number: <?= h($respondent['employee_no']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['position'])): ?>
                                            <div class="muted">Position: <?= h($respondent['position']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['office_department'])): ?>
                                            <div class="muted">College/Office/Department: <?= h($respondent['office_department']) ?></div>
                                        <?php endif; ?>
                                    <?php elseif ($rtype === 'Other'): ?>
                                        <?php if (!empty($respondent['affiliation'])): ?>
                                            <div class="muted">Affiliation/Organization: <?= h($respondent['affiliation']) ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['contact_info'])): ?>
                                        <div class="muted">Contact Information: <?= h($respondent['contact_info']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['details'])): ?>
                                        <div class="value"><?= h($respondent['details']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Witnesses</h2>
                            <div class="list">
                                <?php foreach ($witnesses as $witness): ?>
                                <?php $wtype = $witness['person_type'] ?? 'Private Individual'; ?>
                                <div class="list-item">
                                    <strong><?= h($witness['full_name']) ?>
                                        <span class="muted">(<?= h($wtype) ?>)</span></strong>
                                    <?php if (!empty($witness['gender'])): ?>
                                        <div class="muted">Gender: <?= h($witness['gender']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['student_no'] ?? '') !== ''): ?>
                                        <div class="muted">Student Number: <?= h($witness['student_no']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['employee_no'] ?? '') !== ''): ?>
                                        <div class="muted">Employee Number: <?= h($witness['employee_no']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['college'] ?? '') !== '' || ($witness['course_year'] ?? '') !== ''): ?>
                                        <div class="muted"><?= h(trim(($witness['college'] ?? '') . ' | ' . ($witness['course_year'] ?? ''), ' |')) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['position'] ?? '') !== ''): ?>
                                        <div class="muted">Position: <?= h($witness['position']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['office_department'] ?? '') !== ''): ?>
                                        <div class="muted">College/Office or Department: <?= h($witness['office_department']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['affiliation'] ?? '') !== ''): ?>
                                        <div class="muted">Affiliation/Organization: <?= h($witness['affiliation']) ?></div>
                                    <?php endif; ?>
                                    <div class="muted"><?= h($witness['contact_info']) ?></div>
                                    <div class="value"><?= h($witness['statement']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Evidence Attachments</h2>
                            <div class="list">
                                <?php foreach ($evidence as $file): ?>
                                <div class="list-item">
                                    <strong><?= h($file['original_filename']) ?></strong>
                                    <div class="muted"><?= h($file['mime_type']) ?> |
                                        <?= h(number_format($file['file_size'] / 1024, 1)) ?> KB |
                                        <?= h(date('M d, Y', strtotime($file['uploaded_at']))) ?></div>
                                    <div class="button-row"><a class="btn btn-secondary" target="_blank"
                                            href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=view">View</a><a
                                            class="btn btn-secondary"
                                            href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=download">Download</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Hearing Schedule</h2>
                            <?php
                            $caseHearings = $viewData['hearings'] ?? [];
                            $scheduledHearings = array_values(array_filter($caseHearings, fn($h) => ($h['status'] ?? '') === 'Scheduled'));
                            $pastHearings = array_values(array_filter($caseHearings, fn($h) => in_array(($h['status'] ?? ''), ['Completed', 'Cancelled'], true)));
                            ?>
                            <div class="list">
                                <?php if (empty($scheduledHearings) && empty($pastHearings)): ?>
                                    <div class="muted">No hearings scheduled for this case.</div>
                                <?php endif; ?>
                                <?php foreach ($scheduledHearings as $hearing): ?>
                                    <div class="list-item" style="border-left: 4px solid #1A9D00">
                                        <strong><i class="bi bi-calendar-event"></i> <?= h(date('M d, Y - h:i A', strtotime($hearing['hearing_datetime']))) ?></strong>
                                        <div class="value"><span class="label">Venue</span><br><?= h($hearing['venue']) ?></div>
                                        <?php if (!empty($hearing['google_meet_link'])): ?>
                                            <div class="value"><a href="<?= h($hearing['google_meet_link']) ?>" target="_blank" rel="noopener"><i class="bi bi-camera-video"></i> Join Google Meet</a></div>
                                        <?php endif; ?>
                                        <?php if (!empty($hearing['remarks'])): ?>
                                            <div class="muted"><?= nl2br(h($hearing['remarks'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($pastHearings)): ?>
                                    <div class="label" style="margin-top:6px">Previous Hearings</div>
                                    <?php foreach ($pastHearings as $hearing): ?>
                                        <div class="list-item">
                                            <strong><?= h(date('M d, Y - h:i A', strtotime($hearing['hearing_datetime']))) ?></strong>
                                            <div class="muted"><?= h($hearing['status']) ?> Â· <?= h($hearing['venue']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Conversation<?php if ($messageReceiver): ?> â€”
                                <?= h(trim(($messageReceiver['first_name'] ?? '') . ' ' . ($messageReceiver['last_name'] ?? ''))) ?><?php endif; ?>
                            </h2>
                            <div class="message-thread" id="caseConversation">
                                <?php if (empty($messages)): ?>
                                <p class="muted">No messages yet.</p>
                                <?php endif; ?>
                                <?php foreach ($messages as $caseMessage): ?>
                                <?php $isMine = (int) $caseMessage['sender_account_id'] === (int) $user['account_id']; ?>
                                <div class="message-bubble <?= $isMine ? 'mine' : '' ?>">
                                    <div class="message-meta">
                                        <?= $isMine ? 'You' : h(person_name($caseMessage['sender_first_name'], $caseMessage['sender_last_name'])) ?>
                                        <?= h(date('M d, Y h:i A', strtotime($caseMessage['created_at']))) ?>
                                        <?= ((int) $caseMessage['is_read'] === 0 && !$isMine) ? ' | Unread' : '' ?>
                                    </div>
                                    <div class="message-content"><?= nl2br(h($caseMessage['message'])) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <form class="message-composer" method="POST" action="../messages/send.php"
                                data-no-ajax="true">
                                <?php if ($messageReceiver): ?>
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="complaint_id" value="<?= (int) $case['complaint_id'] ?>">
                                <textarea name="message" placeholder="Write a message" required></textarea>
                                <div class="message-send-row">
                                    <input type="hidden" name="receiver_account_id"
                                        value="<?= (int) ($messageReceiver['account_id'] ?? 0) ?>">
                                    <span class="muted message-receiver-note">Sending to
                                        <?= h(trim(($messageReceiver['first_name'] ?? '') . ' ' . ($messageReceiver['last_name'] ?? ''))) ?>
                                        (<?= h($messageReceiver['role'] ?? '') ?>)</span>
                                    <button class="btn btn-assign" type="submit">Send</button>
                                </div>
                                <?php else: ?>
                                <p class="muted" style="margin:12px 0 0"><i class="bi bi-person-lock"></i> You can only
                                    chat with students assigned to a case you handle.</p>
                                <?php endif; ?>
                            </form>
                        </section>

                        <?php
                        $outcomeText = trim((string) ($case['outcome'] ?? ''));
                        if ($outcomeText !== '' && in_array($caseStatus, ['Resolved', 'Archived'], true)):
                            $resolutionMeta = null;
                            foreach ($history as $historyItem) {
                                if (($historyItem['new_status'] ?? '') === 'Resolved') {
                                    $resolutionMeta = $historyItem;
                                    break;
                                }
                            }
                        ?>
                        <section class="panel case-outcome-panel">
                            <h2><i class="bi bi-check2-circle"></i> Outcome / Resolution</h2>
                            <?php if ($resolutionMeta): ?>
                                <div class="case-outcome-meta">
                                    Resolved by
                                    <?= h(person_name($resolutionMeta['actor_first_name'], $resolutionMeta['actor_last_name'])) ?>
                                    &middot; <?= h(date('M d, Y h:i A', strtotime($resolutionMeta['created_at']))) ?>
                                </div>
                            <?php endif; ?>
                            <div class="case-outcome-text"><?= nl2br(h($outcomeText)) ?></div>
                        </section>
                        <?php endif; ?>
                    </div>

                    <aside>
                        <?php $caseStatus = $case['status'] ?? ''; $caseLocked = in_array($caseStatus, ['Resolved', 'Archived'], true); ?>
                        <section class="panel">
                            <h2>Case Actions</h2>
                            <?php if (!$canManageCase): ?>
                            <p class="muted" style="margin-bottom:10px"><i class="bi bi-lock-fill"></i> This case is
                                not assigned to you. Staff actions are only available on cases assigned to you, but you
                                can still view all case details.
                            </p>
                            <?php else: ?>
                            <?php if ($caseLocked): ?><p class="muted" style="margin-bottom:10px"><i
                                    class="bi bi-lock-fill"></i> This case is closed. Only archiving remains available.
                            </p><?php endif; ?>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Staff Review</h3>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                                    <?= Security::csrfField() ?>
                                    <textarea name="remarks" placeholder="Remarks / notes..."></textarea>
                                    <div class="button-row two">
                                        <button class="btn btn-verify" type="submit" name="case_action" value="verify"
                                            <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>>Verify</button>
                                        <button class="btn btn-reject" type="submit" name="case_action" value="reject"
                                            <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>
                                            data-swal-confirm="Reject this complaint? This action changes its workflow status.">Reject</button>
                                    </div>
                                </form>
                            </div>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Case Workflow</h3>
                                <div class="button-row two">
                                    <button type="button" class="btn btn-return" id="openReturnModal"
                                        <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>><i class="bi bi-arrow-return-left"></i> Return for Revision</button>
                                    <button type="button" class="btn btn-assign" id="openAssignModal"
                                        <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>><i class="bi bi-person-plus"></i> Assign Coordinator</button>
                                </div>
                            </div>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Case Outcome</h3>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                                    <?= Security::csrfField() ?>
                                    <div class="outcome-field">
                                        <textarea name="outcome" id="caseOutcome" placeholder="Record the final outcome/resolution of this case (required for Resolve)."></textarea>
                                    </div>
                                    <div class="button-row two">
                                        <button class="btn btn-resolve" type="submit" name="case_action" value="<?= $caseStatus === 'Resolved' ? 'reopen' : 'resolve' ?>"
                                            <?= ($caseStatus === 'Verified') ? 'data-swal-confirm="Mark this case as resolved?"' : (($caseStatus === 'Resolved') ? 'data-swal-confirm="Unresolve this case? Its status will return to Verified."' : 'disabled title="Available once the case is Verified."') ?>><i class="bi <?= $caseStatus === 'Resolved' ? 'bi-arrow-counterclockwise' : 'bi-check-lg' ?>"></i> <?= $caseStatus === 'Resolved' ? 'Unresolve Case' : 'Resolve Case' ?></button>
                                        <button class="btn btn-archive" type="submit" name="case_action" value="archive"
                                            <?= ($caseStatus === 'Resolved') ? 'data-swal-confirm="Archive this case? It will be moved to Archived Cases."' : 'disabled title="Available once the case is Resolved."' ?>>Archive Case</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </section>

                        <section class="panel">
                            <h2>Timeline</h2>
                            <div class="timeline-scroll">
                            <?php if (empty($history)): ?>
                            <p class=" muted">No case history yet.</p>
                            <?php else: ?>
                            <?php foreach ($history as $item): ?>
                            <div class="timeline-item">
                                <strong><?= h($item['action']) ?></strong>
                                <div class="muted"><?= h(date('M d, Y h:i A', strtotime($item['created_at']))) ?></div>
                                <div class="value">
                                    <?= h($item['previous_status']) ?><?= $item['new_status'] ? ' to ' . h($item['new_status']) : '' ?>
                                </div>
                                <?php if (!empty($item['assigned_coordinator_account_id'])): ?>
                                <div class="value">Coordinator:
                                    <?= h(person_name($item['coordinator_first_name'], $item['coordinator_last_name'])) ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($item['remarks'])): ?>
                                <div class="value"><?= nl2br(h($item['remarks'])) ?></div>
                                <?php endif; ?>
                                <div class="muted">By
                                    <?= h(person_name($item['actor_first_name'], $item['actor_last_name'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            </div>
                        </section>
                    </aside>
                </div>
            </main>
        </div>
    </div>

    <div class="case-modal-overlay" id="returnModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="returnModalTitle"><i class="bi bi-arrow-return-left"></i> Return for Revision</h3>
                    <p>Explain the correction and select every section the student may update.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
            <form class="action-form revision-form" method="POST"
                action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                <?= Security::csrfField() ?>
                <div class="case-modal-body">
                    <textarea name="remarks" placeholder="Revision reason and instructions"
                        required></textarea>
                    <div class="revision-fields">
                        <?php foreach ($revisionFieldLabels as $field => $label): ?>
                        <label class="revision-field"><input type="checkbox" name="revision_fields[]"
                                value="<?= h($field) ?>"> <span><?= h($label) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button class="btn btn-return" type="submit" name="case_action" value="return"
                        data-swal-confirm="Return this complaint with the selected revision requirements?">Return for Revision</button>
                </div>
            </form>
        </div>
    </div>

    <div class="case-modal-overlay" id="assignModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="assignModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="assignModalTitle"><i class="bi bi-person-plus"></i> Assign Coordinator</h3>
                    <p>Assign a coordinator to handle this case.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
            <form class="action-form" method="POST"
                action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                <?= Security::csrfField() ?>
                <div class="case-modal-body">
                    <label class="case-modal-label" for="coordinatorSelect">Coordinator</label>
                    <select id="coordinatorSelect" name="coordinator_account_id" required>
                        <option value="">Select coordinator</option>
                        <?php foreach ($coordinators as $coordinator): ?>
                        <option value="<?= (int) $coordinator['account_id'] ?>"
                            <?= ((int) $case['assigned_coordinator_account_id'] === (int) $coordinator['account_id']) ? 'selected' : '' ?>>
                            <?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?>
                            (<?= h($coordinator['role']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="case-modal-label" for="assignRemarks">Assignment remarks</label>
                    <textarea id="assignRemarks" name="remarks" placeholder="Assignment remarks"></textarea>
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button class="btn btn-assign" type="submit" name="case_action"
                        value="assign" data-swal-confirm="Assign this coordinator to the case?">Assign Coordinator</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const caseConversation = document.getElementById('caseConversation');

    if (caseConversation) {
        caseConversation.scrollTop = caseConversation.scrollHeight;
    }

    (() => {
        const archivedUrl = <?= json_encode(app_route('archived_cases.index')) ?>;

        document.querySelectorAll('[data-swal-confirm]').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const action = button.value;
                let config = {
                    icon: 'question',
                    title: button.dataset.swalConfirm,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, continue',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                };

                if (action === 'reject') {
                    const remarksField = button.form?.querySelector('[name="remarks"]');
                    const remarks = (remarksField?.value || '').trim();
                    if (!remarks) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Rejection note required',
                            text: 'Please enter a note in the Remarks field explaining why this complaint is being rejected.',
                            confirmButtonText: 'Okay',
                            confirmButtonColor: '#c0392b'
                        });
                        remarksField?.focus();
                        return;
                    }
                    config.icon = 'warning';
                    config.confirmButtonText = 'Yes, reject';
                    config.confirmButtonColor = '#c0392b';
                } else if (action === 'archive') {
                    config.icon = 'warning';
                    config.confirmButtonText = 'Yes, archive';
                    config.confirmButtonColor = '#9a6b00';
                } else if (action === 'reopen') {
                    config.icon = 'warning';
                    config.confirmButtonText = 'Yes, unresolve';
                } else if (action === 'reject') {
                    config.icon = 'warning';
                    config.confirmButtonText = 'Yes, reject';
                    config.confirmButtonColor = '#c0392b';
                } else if (action === 'return') {
                    config.icon = 'warning';
                    config.confirmButtonText = 'Yes, return';
                    config.confirmButtonColor = '#b8860b';
                } else if (action === 'assign') {
                    config.confirmButtonText = 'Yes, assign';
                } else if (action === 'resolve') {
                    config.confirmButtonText = 'Yes, resolve';
                    config.confirmButtonColor = '#157000';
                }

                Swal.fire(config).then(result => {
                    if (!result.isConfirmed) return;

                    if (action === 'archive') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Case archived',
                            text: 'This case has been moved to Archived Cases. You can find all archived cases under Settings > Archived Cases.',
                            showCancelButton: true,
                            confirmButtonText: 'Open Archived Cases',
                            cancelButtonText: 'Close',
                            confirmButtonColor: '#1a9d00',
                            reverseButtons: true
                        }).then(next => {
                            if (next.isConfirmed) {
                                window.location.href = archivedUrl;
                            } else {
                                button.form.requestSubmit(button);
                            }
                        });
                    } else {
                        button.form.requestSubmit(button);
                    }
                });
            });
        });

        const bindModal = (openSelector, overlayId) => {
            const openBtn = document.getElementById(openSelector);
            const overlay = document.getElementById(overlayId);
            if (!openBtn || !overlay) return;
            const setOpen = open => overlay.classList.toggle('open', open);
            openBtn.addEventListener('click', () => setOpen(true));
            overlay.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => setOpen(false)));
            overlay.addEventListener('mousedown', e => { if (e.target === overlay) setOpen(false); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
        };

        bindModal('openReturnModal', 'returnModalOverlay');
        bindModal('openAssignModal', 'assignModalOverlay');
    })();

    const statusBanner = document.getElementById('caseStatusBanner');

    if (statusBanner) {
        const statusCaseId = statusBanner.dataset.caseId;
        const statusEndpoint = statusBanner.dataset.statusEndpoint + '?id=' + statusCaseId;
        const statusPill = document.getElementById('caseStatusPill');
        const statusUpdated = document.getElementById('caseStatusUpdated');
        const statusSlugs = {
            'Submitted': 'submitted',
            'Verified': 'verified',
            'Returned for Revision': 'returned',
            'Rejected': 'rejected',
            'Resolved': 'resolved',
            'Archived': 'archived'
        };
        const statusIcons = {
            'Submitted': 'bi-send',
            'Verified': 'bi-shield-check',
            'Returned for Revision': 'bi-arrow-return-left',
            'Rejected': 'bi-x-circle',
            'Resolved': 'bi-check2-circle',
            'Archived': 'bi-archive'
        };
        let lastStatus = document.getElementById('caseStatusPill').textContent.trim();

        const formatDbTime = raw => {
            const match = String(raw || '').match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/);
            if (!match) return '';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const year = match[1];
            const month = months[Number(match[2]) - 1];
            const day = Number(match[3]);
            let hour = Number(match[4]);
            const minute = match[5];
            const suffix = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;
            return month + ' ' + String(day).padStart(2, '0') + ', ' + year + ' ' + String(hour).padStart(2, '0') + ':' + minute + ' ' + suffix;
        };

        const applyStatus = newStatus => {
            const slug = statusSlugs[newStatus] || 'submitted';
            statusPill.textContent = newStatus;
            statusBanner.dataset.status = slug;
            const icon = statusBanner.querySelector('.status-banner-badge i');
            if (icon && statusIcons[newStatus]) {
                icon.className = 'bi ' + statusIcons[newStatus];
            }
        };

        const pollStatus = async () => {
            try {
                const response = await fetch(statusEndpoint, { credentials: 'same-origin' });
                if (!response.ok) return;
                const data = await response.json();
                if (!data.success) return;

                const newStatus = String(data.status || '').trim();
                if (newStatus && newStatus !== lastStatus) {
                    lastStatus = newStatus;
                    applyStatus(newStatus);
                    statusBanner.classList.remove('flash');
                    void statusBanner.offsetWidth;
                    statusBanner.classList.add('flash');
                }

                if (data.updated_at) {
                    const formatted = formatDbTime(data.updated_at);
                    if (formatted) {
                        statusUpdated.textContent = 'Updated ' + formatted;
                    }
                }
            } catch (error) {
                // Ignore transient polling failures; retry on the next tick.
            }
        };

        setInterval(pollStatus, 5000);
    }
    </script>
</body>

</html>