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
$updates = $viewData['updates'] ?? [];
$updateTypes = CaseUpdate::updateTypes();
$caseUpdateAttachments = [];
foreach (CaseUpdate::attachmentsForCase($complaintId) as $attachment) {
    $caseUpdateAttachments[(int) $attachment['update_id']][] = $attachment;
}
$coordinators = $viewData['coordinators'];
$reformationCoordinators = $viewData['reformationCoordinators'];
$reformationRecords = $viewData['reformationRecords'] ?? [];
$reformationReports = $viewData['reformationReports'] ?? [];
$messages = $viewData['messages'];
$messageReceiver = $viewData['messageReceiver'];
$message = $viewData['message'];
$errors = $viewData['errors'];
$resubmission = $viewData['resubmission'];
$classificationOptions = $viewData['classificationOptions'];
$respondentAccounts = $viewData['respondentAccounts'] ?? [];
$counterStatements = $viewData['counterStatements'] ?? [];
$pendingApproval = $viewData['pendingApproval'] ?? null;
$pendingApprovalForRequester = $viewData['pendingApprovalForRequester'] ?? null;
$caseStatus = $case['status'] ?? '';
$statusMeta = [
    'Under Investigation' => ['slug' => 'under-investigation', 'icon' => 'bi-search'],
    'Returned for Revision' => ['slug' => 'returned', 'icon' => 'bi-arrow-return-left'],
    'Rejected' => ['slug' => 'rejected', 'icon' => 'bi-x-circle'],
    'Resolved' => ['slug' => 'resolved', 'icon' => 'bi-check2-circle'],
    'Reformation in Progress' => ['slug' => 'reformation-in-progress', 'icon' => 'bi-arrow-repeat'],
    'Reformation Completed' => ['slug' => 'reformation-completed', 'icon' => 'bi-patch-check'],
    'Escalated' => ['slug' => 'escalated', 'icon' => 'bi-arrow-up-circle'],
    'Archived' => ['slug' => 'archived', 'icon' => 'bi-archive'],
];
$caseStatusSlug = $statusMeta[$caseStatus]['slug'] ?? 'under-investigation';
$caseStatusIcon = $statusMeta[$caseStatus]['icon'] ?? 'bi-tag';
$caseUpdatedAt = $case['updated_at'] ?? $case['submitted_at'] ?? null;
$viewerRoleKey = strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));
$viewOnlyStaffRoles = ['sdr-staff', 'sdru-staff'];
$isReformationCoordinator = $viewerRoleKey === 'reformation-coordinator';
$isHeadViewer = in_array($viewerRoleKey, ['head-of-sdru', 'sdru-head'], true);
$canManageCase = !in_array($viewerRoleKey, $viewOnlyStaffRoles, true)
    && ($viewerRoleKey !== 'coordinator'
        || ((int) ($case['assigned_coordinator_account_id'] ?? 0) === (int) ($user['account_id'] ?? 0)))
    && ($viewerRoleKey !== 'reformation-coordinator'
        || ((int) ($case['assigned_reformation_coordinator_account_id'] ?? 0) === (int) ($user['account_id'] ?? 0)));
$canManageRespondentAccounts = $isHeadViewer
    || ($viewerRoleKey === 'coordinator' && (int) ($case['assigned_coordinator_account_id'] ?? 0) === (int) ($user['account_id'] ?? 0))
    || ($viewerRoleKey === 'reformation-coordinator' && (int) ($case['assigned_reformation_coordinator_account_id'] ?? 0) === (int) ($user['account_id'] ?? 0));
$canDecideCounterStatement = $isHeadViewer
    || ($viewerRoleKey === 'coordinator' && (int) ($case['assigned_coordinator_account_id'] ?? 0) === (int) ($user['account_id'] ?? 0));
$hasCaseClassification = trim((string) ($case['case_classification'] ?? '')) !== ''
    && strcasecmp(trim((string) $case['case_classification']), 'Unclassified') !== 0;
$caseRefreshTargets = '#caseStatusBanner,#case-overview,#complaint-details,#case-actions,#case-timeline,#case-approval-slot,#case-outcome-slot,#reformation-records-slot,#respondents';
if (!$isReformationCoordinator) {
    $caseRefreshTargets .= ',#case-updates,#updateModalOverlay';
}
if ($canManageRespondentAccounts) {
    $caseRefreshTargets .= ',#forward-respondent,#forwardModalOverlay';
}
$released = !empty($case['respondent_released_at']);
$visibility = CaseRecord::respondentVisibility($complaintId);
$visibilityLabels = [
    'complaint_details' => 'Complaint Details (narrative)',
    'incident' => 'Incident date, time, and location',
    'hearings' => 'Scheduled hearings',
    'final_information' => 'Final information (outcome / action taken / remarks)',
];
$linkedForwardAccounts = array_values(array_filter(
    $respondentAccounts,
    fn($account) => !empty($account['linked_account_id']) && ($account['account_status'] ?? '') === 'active'
));
$hasLinkedAccounts = !empty($linkedForwardAccounts);

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

$jsonEncodeFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

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
    <title>Case Details | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        align-items: stretch;
        justify-content: flex-start;
        background: var(--bg-page, #f5f7f4);
        color: var(--text-primary, #172017);
        padding: 0;
    }

    .case-page {
        min-height: 100vh;
        width: 100%;
    }

    .case-header {
        background: var(--bg-sidebar, #123c1b);
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
        color: rgba(255,255,255,0.8);
        font-size: 13px;
    }

    .case-header a {
        background: var(--surface-primary, #fff);
        border-radius: 8px;
        color: var(--text-secondary, #123c1b);
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

    .case-section-nav { display:flex; gap:8px; overflow-x:auto; padding:10px 0 18px; position:sticky; top:0; z-index:5; background: var(--bg-page, #f5f7f4); }
    .case-section-nav a { white-space:nowrap; padding:7px 10px; border:1px solid var(--border-primary, #dce5da); border-radius:999px; color: var(--text-secondary, #123c1b); background: var(--surface-primary, #fff); text-decoration:none; font-size:12px; }
    .case-section-nav a:hover { background: rgba(26, 157, 0, 0.08); }
    .case-content-section { scroll-margin-top:66px; }
    .case-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin:0 0 18px; }
    .case-summary-item { background: var(--surface-primary, #fff); border:1px solid var(--border-primary, #dce5da); border-radius:10px; padding:12px 14px; }
    .case-summary-item .label { color: var(--text-muted, #637162); font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
    .case-summary-item .value { color: var(--text-secondary, #123c1b); font-weight:700; margin-top:4px; overflow-wrap:anywhere; }
    .people-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .people-summary details { background: var(--surface-secondary, #f9fcf8); border:1px solid var(--border-primary, #dce5da); border-radius:8px; padding:10px 12px; }
    .people-summary summary { cursor:pointer; color: var(--text-secondary, #123c1b); font-weight:700; }
    .respondent-record { background: var(--surface-primary, #fff); border:1px solid var(--border-primary, #dce5da); border-radius:10px; margin-bottom:10px; }
    .respondent-record > summary { cursor:pointer; color: var(--text-secondary, #123c1b); font-weight:700; list-style:none; padding:13px 14px; }
    .respondent-record > summary::-webkit-details-marker { display:none; }
    .respondent-record > summary::after { content:'View details'; color:#637162; float:right; font-size:12px; font-weight:400; }
    .respondent-record[open] > summary::after { content:'Hide details'; }
    .respondent-record-body { border-top:1px solid #e7eee5; padding:12px 14px; }
    .long-case-text { max-height:15rem; overflow:auto; line-height:1.65; white-space:normal; }
    @media (max-width:720px) { .case-summary-grid,.people-summary { grid-template-columns:1fr; } .case-section-nav { top:0; } }

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

    .status-banner[data-status="under-investigation"] {
        --status-color: #1c6dd0;
        --status-bg: #eaf3fd;
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

    .status-banner[data-status="reformation-in-progress"] {
        --status-color: #8a5a00;
        --status-bg: #fff4d6;
    }

    .status-banner[data-status="reformation-completed"] {
        --status-color: #087f5b;
        --status-bg: #e1f6ef;
    }

    .status-banner[data-status="escalated"] {
        --status-color: #c2410c;
        --status-bg: #fdeee3;
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

    .case-slot {
        display: contents;
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

    .respondent-details-list {
        display: grid;
        gap: 14px;
    }

    .respondent-details-card {
        padding: 0;
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

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .form-group label {
        color: #5c6a59;
        font-size: 12px;
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

    input[type="checkbox"] {
        width: 15px;
        height: 15px;
        min-width: 15px;
        margin: 0;
        padding: 0;
        accent-color: #1c6dd0;
        flex-shrink: 0;
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

    .case-update-respondent-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin: 8px 0 12px;
    }

    .case-update-respondent-grid input,
    .case-update-respondent-grid select {
        min-width: 0;
    }

    .student-number-row {
        display: flex;
        gap: 8px;
        width: 100%;
    }

    .student-number-row input {
        flex: 1;
        min-width: 0;
    }

    .btn-find-student {
        flex: 0 0 auto;
        padding: 8px 14px;
        white-space: nowrap;
    }

    .student-account-results {
        grid-column: 1 / -1;
        margin-top: 8px;
        width: 100%;
    }

    .student-account-results .find-hint {
        color: #6b7a68;
        font-size: 12px;
        padding: 4px 0;
    }

    .find-student-card {
        align-items: center;
        background: #f4f7f2;
        border: 1px solid #ccd9c8;
        border-radius: 8px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-top: 6px;
        padding: 10px 12px;
    }

    .find-student-card .find-student-meta {
        min-width: 0;
    }

    .find-student-card .find-student-meta strong {
        display: block;
        font-size: 13px;
    }

    .find-student-card .find-student-meta span {
        color: #6b7a68;
        font-size: 12px;
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

    .case-action-group > .btn {
        width: 100%;
    }

    .case-action-group > .btn + .btn {
        margin-top: 14px;
    }

    .btn-return {
        background: #b57600;
    }

    .btn-return:hover {
        background: #936d00;
    }

    .btn-reject {
        background: #c62828;
    }

    .btn-reject:hover {
        background: #a01818;
    }

    .btn-assign {
        background: #1c6dd0;
    }

    .btn-assign:hover {
        background: #15549e;
    }

    .btn-resolve {
        background: #157000;
    }

    .btn-resolve:hover {
        background: #0f5a00;
    }

    .approval-panel {
        background: linear-gradient(135deg, #fffdf5 0%, #ffffff 72%);
        border: 1px solid #ead9a5;
        border-left: 5px solid #b57600;
        box-shadow: 0 5px 18px rgba(104, 77, 14, .08);
        margin: 0 0 16px;
        padding: 14px 16px 16px;
    }

    .approval-heading {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        margin-bottom: 4px;
    }

    .approval-heading-icon {
        align-items: center;
        background: #fff1c7;
        border-radius: 10px;
        color: #966500;
        display: inline-flex;
        flex: 0 0 38px;
        font-size: 18px;
        height: 38px;
        justify-content: center;
    }

    .approval-heading h2 {
        margin: 0;
    }

    .approval-intro {
        color: #6a614c;
        font-size: 13px;
        line-height: 1.55;
        margin: 0 0 9px 50px;
    }

    .approval-summary {
        background: rgba(255, 255, 255, .78);
        border: 1px solid #eadfbd;
        border-radius: 8px;
        color: #283126;
        font-size: 13px;
        line-height: 1.55;
        margin: 0 0 10px 50px;
        padding: 9px 12px;
    }

    .approval-form {
        gap: 8px;
        margin: 0 0 0 50px;
    }

    .approval-form textarea {
        background: #fff;
        min-height: 52px;
    }

    .approval-actions {
        display: grid;
        gap: 8px;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    }

    .approval-actions .btn {
        align-items: center;
        display: inline-flex;
        gap: 7px;
        justify-content: center;
        min-height: 42px;
    }

    .btn-escalate {
        background: #c2410c;
    }

    .btn-escalate:hover {
        background: #9a3307;
    }

    .btn-archive {
        background: #59635a;
    }

    .btn-archive:hover {
        background: #454d45;
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

    .badge-status {
        border-radius: 20px;
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .3px;
        padding: 3px 10px;
        text-transform: uppercase;
    }

    .badge-status.active {
        background: #e2f5dc;
        color: #0d5c00;
    }

    .badge-status.inactive {
        background: #ffe9e2;
        color: #a02b00;
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

    .swal2-container {
        z-index: 4000 !important;
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

    .update-list { display: flex; flex-direction: column; gap: 12px; }
    .update-item { border: 1px solid #e8efe6; border-left-width: 4px; border-radius: 10px; padding: 12px 14px; }
    .update-item.original { border-left-color: #1A9D00; }
    .update-item.closed-stage { border-left-color: #cfd8cc; }
    .update-item.case-stage { border-left-color: #2f6dbc; }
    .update-stage { display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; border-radius: 999px; padding: 4px 9px; margin-bottom: 8px; }
    .update-stage-original { background: #eaf5e8; color: #176f22; }
    .update-stage-closed { background: #eef1ed; color: #4c5a4f; }
    .update-stage-case { background: #e8f0fb; color: #234a86; }
    .update-body strong { display: block; font-size: 14px; }
    .update-meta { align-items: center; display: flex; flex-wrap: wrap; gap: 6px 12px; margin-bottom: 6px; }
    .update-type { background: #f4f7f3; border: 1px solid #e0e8de; border-radius: 999px; color: #3c5241; font-size: 12px; font-weight: 700; padding: 3px 9px; }
    .update-attachments { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 10px; }
    .update-attachments .btn { font-size: 12px; padding: 4px 9px; }

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
        background: var(--surface-secondary, #f7faf6);
        border: 1px solid var(--border-primary, #edf4eb);
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
        background: var(--surface-primary, #fff);
        border: 1px solid var(--border-primary, #dce5da);
        border-radius: 18px 18px 18px 6px;
        color: var(--text-primary, #1f2a1f);
        line-height: 1.5;
        padding: 10px 13px;
        word-break: break-word;
    }

    .message-bubble.mine {
        align-self: flex-end;
    }

    .message-bubble.mine .message-content {
        background: var(--accent, #1A9D00);
        border-color: var(--accent, #1A9D00);
        border-radius: 18px 18px 6px 18px;
        color: #fff;
    }

    .message-meta {
        color: var(--text-muted, #536052);
        font-size: 12px;
        margin: 0 6px 6px;
    }

    .message-bubble.mine .message-meta {
        text-align: right;
    }

    .message-composer {
        background: var(--surface-primary, #fff);
        border: 1px solid var(--border-primary, #dce5da);
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

        .approval-intro,
        .approval-summary,
        .approval-form {
            margin-left: 0;
        }

        .approval-actions {
            grid-template-columns: 1fr;
        }

        .case-update-respondent-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=6">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-page app-content">
            <?php $pageTitle = $case['case_number']; require __DIR__ . '/../layout/topbar.php'; ?>

            <main class="case-wrap case-detail-view">
                <nav class="case-section-nav" aria-label="Case details sections">
                    <a href="#case-overview">Overview</a><a href="#complaint-details">Complaint</a><?php if (!$isReformationCoordinator): ?><a href="#counter-statements">Statements</a><?php endif; ?><a href="#case-evidence">Evidence</a><?php if (!$isReformationCoordinator): ?><a href="#hearings">Hearings</a><?php endif; ?><?php if (!$isReformationCoordinator): ?><a href="#case-messages">Messages</a><?php endif; ?><?php if (!$isReformationCoordinator): ?><a href="#case-updates">Updates</a><?php endif; ?><?php if (!in_array($viewerRoleKey, ['sdr-staff', 'sdru-staff'], true)): ?><a href="#case-actions">Actions</a><?php endif; ?><a href="#case-timeline">Timeline</a>
                </nav>
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
                            <?= $caseUpdatedAt ? h(date('M d, Y h:i A', strtotime($caseUpdatedAt))) : '—' ?></span>
                    </div>
                </div>

                <div id="case-approval-slot">
                    <?php if ($pendingApproval && $pendingApproval['status'] === 'Pending'): ?>
                    <section class="panel approval-panel" id="case-approval-panel">
                        <div class="approval-heading">
                            <span class="approval-heading-icon"><i class="bi bi-shield-check"></i></span>
                            <h2>Approve This Action?</h2>
                        </div>
                        <p class="approval-intro">A coordinator requested <strong><?= h($pendingApproval['action_label']) ?></strong>. Review the submitted details before deciding.</p>
                        <div class="approval-summary"><?= h(CaseApproval::description($pendingApproval)) ?></div>
                        <form class="action-form approval-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>">
                            <?= Security::csrfField() ?>
                            <textarea name="review_remarks" placeholder="Optional review note"></textarea>
                            <div class="approval-actions">
                                <input type="hidden" name="approval_id" value="<?= (int) $pendingApproval['approval_id'] ?>">
                                <input type="hidden" name="case_action" value="approval_decision">
                                <button class="btn btn-resolve" type="submit" name="approval_decision" value="approve" data-swal-action="approval_decision" data-swal-confirm="Approve this coordinator action? It will be applied to the case now."><i class="bi bi-check-lg"></i> Approve Action</button>
                                <button class="btn btn-reject" type="submit" name="approval_decision" value="reject" data-swal-action="approval_decision" data-swal-confirm="Reject this coordinator action? It will not be applied to the case."><i class="bi bi-x-lg"></i> Reject Action</button>
                            </div>
                        </form>
                    </section>
                    <?php elseif ($pendingApprovalForRequester): ?>
                    <section class="panel approval-panel" id="case-approval-pending">
                        <div class="approval-heading">
                            <span class="approval-heading-icon"><i class="bi bi-hourglass-split"></i></span>
                            <h2>Approval Pending</h2>
                        </div>
                        <p class="approval-intro">Your <strong><?= h($pendingApprovalForRequester['action_label']) ?></strong> request is waiting for the SDRU head's review.</p>
                        <div class="approval-summary"><?= h(CaseApproval::description($pendingApprovalForRequester)) ?></div>
                    </section>
                    <?php endif; ?>
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
                        <section class="panel case-content-section" id="case-overview">
                            <h2>Case Overview</h2>
                            <div class="case-summary-grid">
                                <div class="case-summary-item"><div class="label">Classification</div><div class="value"><?= h($case['case_classification'] ?: 'Unclassified') ?></div></div>
                                <div class="case-summary-item"><div class="label"><?= $isReformationCoordinator ? 'Reformation Coordinator' : 'Discipline Coordinator' ?></div><div class="value"><?= h($isReformationCoordinator ? person_name($case['reformation_coordinator_first_name'], $case['reformation_coordinator_last_name']) : person_name($case['coordinator_first_name'], $case['coordinator_last_name'])) ?></div></div>
                                <div class="case-summary-item"><div class="label">Submitted</div><div class="value"><?= h(date('M d, Y', strtotime($case['submitted_at']))) ?></div></div>
                            </div>
                        </section>

                        <section class="panel case-content-section" id="complaint-details">
                            <h2>Complaint Details</h2>
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
                                    <div class="label">Age</div>
                                    <div class="value"><?= h($case['complainant_age'] ?? 'Not provided') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Complainant Type</div>
                                    <div class="value"><?= h($case['complainant_type'] ?? 'Student') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Case Classification</div>
                                    <div class="value"><?= h($case['case_classification'] ?: 'Unclassified') ?></div>
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
                                <div class="detail">
                                    <div class="label">Reformation Coordinator</div>
                                    <div class="value">
                                        <?= h(person_name($case['reformation_coordinator_first_name'], $case['reformation_coordinator_last_name'])) ?>
                                    </div>
                                </div>
                                <div class="detail">
                                    <div class="label">Reformation Status</div>
                                    <div class="value">
                                        <?php if (!empty($case['reformation_completed_at'])): ?>
                                            <span class="reformation-completed-badge"><i class="bi bi-patch-check"></i> Completed &middot; <?= h(date('M d, Y', strtotime($case['reformation_completed_at']))) ?></span>
                                        <?php else: ?>
                                            Not yet completed
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <details class="respondent-record" style="grid-column:1 / -1">
                                    <summary>Details</summary>
                                    <div class="respondent-record-body">
                                        <div class="value long-case-text"><?= nl2br(h($case['complaint_details'])) ?></div>
                                    </div>
                                </details>
                            </div>
                        </section>

                        <section class="panel case-content-section" id="respondents">
                            <h2>Respondents</h2>
                            <div class="respondent-details-list">
                                <?php if (!$respondents): ?>
                                <p class="muted">Respondent: Not yet identified</p>
                                <?php endif; ?>
                                <?php foreach ($respondents as $respondent): ?>
                                <?php $rtype = $respondent['respondent_type'] ?? 'Student'; ?>
                                <details class="respondent-record">
                                    <summary><?= h($respondent['full_name']) ?> · <?= h($rtype) ?></summary>
                                    <div class="respondent-record-body">
                                    <div class="details-grid">
                                    <div class="detail">
                                        <div class="label">Full Name</div>
                                        <div class="value"><strong><?= h($respondent['full_name']) ?></strong></div>
                                    </div>
                                    <div class="detail">
                                        <div class="label">Respondent Type</div>
                                        <div class="value"><?= h($rtype) ?></div>
                                    </div>
                                    <?php if (!empty($respondent['gender'])): ?>
                                    <div class="detail"><div class="label">Gender</div><div class="value"><?= h($respondent['gender']) ?></div></div>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['age'])): ?>
                                    <div class="detail"><div class="label">Age</div><div class="value"><?= h($respondent['age']) ?></div></div>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['birthday'])): ?>
                                    <div class="detail"><div class="label">Birthday</div><div class="value"><?= h($respondent['birthday']) ?></div></div>
                                    <?php endif; ?>
                                    <?php if ($rtype === 'Student'): ?>
                                        <?php if (!empty($respondent['student_no'])): ?>
                                        <div class="detail"><div class="label">Student Number</div><div class="value"><?= h($respondent['student_no']) ?></div></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['college'])): ?>
                                        <div class="detail"><div class="label">College</div><div class="value"><?= h($respondent['college']) ?></div></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['course_year'])): ?>
                                        <div class="detail"><div class="label">Course and Section</div><div class="value"><?= h($respondent['course_year']) ?></div></div>
                                        <?php endif; ?>
                                    <?php elseif ($rtype === 'Employee'): ?>
                                        <?php if (!empty($respondent['employee_no'])): ?>
                                        <div class="detail"><div class="label">Employee Number</div><div class="value"><?= h($respondent['employee_no']) ?></div></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['position'])): ?>
                                        <div class="detail"><div class="label">Position</div><div class="value"><?= h($respondent['position']) ?></div></div>
                                        <?php endif; ?>
                                        <?php if (!empty($respondent['office_department'])): ?>
                                        <div class="detail"><div class="label">College / Office / Department</div><div class="value"><?= h($respondent['office_department']) ?></div></div>
                                        <?php endif; ?>
                                    <?php elseif ($rtype === 'Other'): ?>
                                        <?php if (!empty($respondent['affiliation'])): ?>
                                        <div class="detail"><div class="label">Affiliation / Organization</div><div class="value"><?= h($respondent['affiliation']) ?></div></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['contact_info'])): ?>
                                    <div class="detail"><div class="label">Contact Number</div><div class="value"><?= h($respondent['contact_info']) ?></div></div>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['email'])): ?>
                                    <div class="detail"><div class="label">Email</div><div class="value"><?= h($respondent['email']) ?></div></div>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['address'])): ?>
                                    <div class="detail"><div class="label">Address</div><div class="value"><?= h($respondent['address']) ?></div></div>
                                    <?php endif; ?>
                                    <?php if (!empty($respondent['details'])): ?>
                                    <div class="detail full"><div class="label">Details</div><div class="value"><?= nl2br(h($respondent['details'])) ?></div></div>
                                    <?php endif; ?>
                                    </div>
                                    </div>
                                </details>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel case-content-section" id="witnesses">
                            <h2>Witnesses</h2>
                            <div class="list">
                                <?php if (!$witnesses): ?>
                                <p class="muted">Witness: None provided</p>
                                <?php endif; ?>
                                <?php foreach ($witnesses as $witness): ?>
                                <?php $wtype = $witness['person_type'] ?? 'Private Individual'; ?>
                                <div class="list-item">
                                    <strong><?= h($witness['full_name']) ?>
                                        <span class="muted">(<?= h($wtype) ?>)</span></strong>
                                    <?php if (!empty($witness['gender'])): ?>
                                        <div class="muted">Gender: <?= h($witness['gender']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($witness['age'])): ?>
                                        <div class="muted">Age: <?= h($witness['age']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($witness['birthday'])): ?>
                                        <div class="muted">Birthday: <?= h($witness['birthday']) ?></div>
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
                                    <?php if (($witness['contact_info'] ?? '') !== ''): ?>
                                        <div class="muted">Contact Number: <?= h($witness['contact_info']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['email'] ?? '') !== ''): ?>
                                        <div class="muted">Email: <?= h($witness['email']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['address'] ?? '') !== ''): ?>
                                        <div class="muted">Address: <?= h($witness['address']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($witness['statement'] ?? '') !== ''): ?>
                                        <details class="respondent-record">
                                            <summary>Statement</summary>
                                            <div class="respondent-record-body">
                                                <div class="value"><?= h($witness['statement']) ?></div>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel case-content-section" id="case-evidence">
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

                        <?php if (!$isReformationCoordinator): ?>
                        <section class="panel case-content-section" id="counter-statements">
                            <h2>Statement Exchange</h2>
                            <?php if (empty($counterStatements)): ?>
                                <p class="muted">No counter-statements have been submitted by respondents yet.</p>
                            <?php else: ?>
                                <?php foreach ($counterStatements as $statement): ?>
                                <?php
                                $statementAttachments = CounterStatement::attachments((int) $statement['counter_statement_id']);
                                $statementStatus = $statement['status'] ?? 'Draft';
                                ?>
                                <div class="respondent-details-card" style="margin-bottom:12px">
                                    <div class="details-grid">
                                    <div class="detail">
                                        <div class="label">Respondent</div>
                                        <div class="value"><strong><?= h($statement['respondent_full_name']) ?></strong></div>
                                    </div>
                                    <div class="detail">
                                        <div class="label">Status</div>
                                        <div class="value">
                                            <span class="badge-status <?= $statementStatus === 'Submitted' ? 'active' : 'inactive' ?>"><?= h($statementStatus) ?></span>
                                            <?php if (!empty($statement['submitted_at'])): ?>
                                                &middot; submitted <?= h(date('M d, Y h:i A', strtotime($statement['submitted_at']))) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($statement['account_first_name'])): ?>
                                    <div class="detail">
                                        <div class="label">Submitted by</div>
                                        <div class="value"><?= h(trim($statement['account_first_name'] . ' ' . $statement['account_last_name'])) ?> (<?= h($statement['respondent_account_id'] ?? '-') ?>)</div>
                                    </div>
                                    <?php endif; ?>
                                    <details class="respondent-record" style="grid-column:1 / -1">
                                        <summary>Statement Content</summary>
                                        <div class="respondent-record-body">
                                            <div class="value long-case-text" style="white-space:pre-wrap"><?= nl2br(h($statement['content'] ?? '')) ?></div>
                                        </div>
                                    </details>
                                    <?php if (!empty($statementAttachments)): ?>
                                    <div class="detail full">
                                        <div class="label">Evidence Attachments</div>
                                        <div class="button-row" style="margin-top:6px">
                                            <?php foreach ($statementAttachments as $attachment): ?>
                                                <a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $attachment['evidence_id'] ?>&amp;mode=view"><i class="bi bi-paperclip"></i> View</a>
                                                <a class="btn btn-secondary" href="../complaints/attachment.php?id=<?= (int) $attachment['evidence_id'] ?>&amp;mode=download"><i class="bi bi-download"></i> Download</a>
                                                <span class="muted" style="display:inline-block;margin-left:4px"><?= h($attachment['original_filename']) ?> (<?= h(number_format($attachment['file_size'] / 1024, 1)) ?> KB)</span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    </div>
                                    <?php if ($statementStatus === 'Submitted' && !in_array($viewerRoleKey, ['sdr-staff', 'sdru-staff'], true)): ?>
                                    <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets . ',#counter-statements') ?>"
                                        style="margin-top:10px">
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="case_action" value="request_counter_revision">
                                        <input type="hidden" name="counter_statement_id" value="<?= (int) $statement['counter_statement_id'] ?>">
                                        <button class="btn btn-archive" type="submit" data-swal-confirm="Return this counter-statement to the respondent for revision?"><i class="bi bi-arrow-counterclockwise"></i> Request Revision</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($statementStatus === 'Submitted'): ?>
                                    <?php if (!empty($statement['coordinator_action'])): ?>
                                    <div class="details-grid" style="margin-top:12px">
                                        <div class="detail">
                                            <div class="label">Coordinator Decision</div>
                                            <div class="value">
                                                <?= $statement['coordinator_action'] === 'proceed_to_investigation'
                                                    ? 'Proceeded to Investigation'
                                                    : 'Forwarded to Complainant' ?>
                                                <?php if (!empty($statement['coordinator_action_at'])): ?>
                                                    &middot; <?= h(date('M d, Y h:i A', strtotime($statement['coordinator_action_at']))) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (trim((string) ($statement['complaint_response_content'] ?? '')) !== ''): ?>
                                        <details class="respondent-record" style="grid-column:1 / -1">
                                            <summary>Complainant Response
                                                <?php if (!empty($statement['complaint_response_submitted_at'])): ?>
                                                    <span class="badge-status active">Submitted <?= h(date('M d, Y h:i A', strtotime($statement['complaint_response_submitted_at']))) ?></span>
                                                <?php else: ?>
                                                    <span class="badge-status inactive">Draft</span>
                                                <?php endif; ?>
                                            </summary>
                                            <div class="respondent-record-body">
                                                <div class="value long-case-text" style="white-space:pre-wrap"><?= nl2br(h($statement['complaint_response_content'])) ?></div>
                                            </div>
                                        </details>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($statement['coordinator_action'] === 'forwarded_to_complainant' && $canDecideCounterStatement): ?>
                                        <?php if (!empty($statement['complaint_response_submitted_at'])): ?>
                                        <div style="margin-top:12px">
                                            <div class="label">Next Action</div>
                                            <div class="value" style="margin:6px 0 10px">The complainant has submitted a response. You may now advance the case to the investigation stage.</div>
                                            <div class="button-row">
                                    <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets . ',#counter-statements') ?>">
                                        <?= Security::csrfField() ?>
                                                    <input type="hidden" name="case_action" value="proceed_counter_statement">
                                                    <input type="hidden" name="counter_statement_id" value="<?= (int) $statement['counter_statement_id'] ?>">
                                                    <button class="btn btn-assign" type="submit" data-swal-confirm="Proceed to investigation? The submitted counter-statement will be accepted and the case workflow will continue."><i class="bi bi-search"></i> Proceed to Investigation</button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div style="margin-top:12px">
                                            <div class="value" style="margin:6px 0 10px">The complainant has not yet responded to the forwarded counter-statement.</div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php elseif ($canDecideCounterStatement): ?>
                                    <div style="margin-top:12px">
                                        <div class="label">Next Action</div>
                                        <div class="value" style="margin:6px 0 10px">What would you like to do with the Respondent&rsquo;s Counter-Statement?</div>
                                        <div class="button-row">
                                            <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets . ',#counter-statements') ?>">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="case_action" value="proceed_counter_statement">
                                                <input type="hidden" name="counter_statement_id" value="<?= (int) $statement['counter_statement_id'] ?>">
                                                <button class="btn btn-assign" type="submit" data-swal-confirm="Proceed to investigation? The case workflow will continue using the submitted counter-statement."><i class="bi bi-search"></i> Proceed to Investigation</button>
                                            </form>
                                            <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets . ',#counter-statements') ?>">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="case_action" value="forward_counter_statement">
                                                <input type="hidden" name="counter_statement_id" value="<?= (int) $statement['counter_statement_id'] ?>">
                                                <button class="btn btn-archive" type="submit" data-swal-confirm="Are you sure you want to forward the permitted Respondent Counter-Statement information to the Complainant?"><i class="bi bi-send"></i> Forward to Complainant</button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </section>
                        <?php endif; ?>

                        <?php if (!$isReformationCoordinator): ?>
                        <section class="panel case-content-section" id="hearings">
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
                                            <div class="muted"><?= h($hearing['status']) ?> · <?= h($hearing['venue']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <?php if (!$isReformationCoordinator): ?>
                        <section class="panel case-content-section" id="case-messages">
                            <h2>Conversation<?php if ($messageReceiver): ?> —
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
                        <?php endif; ?>

                        <?php if (!$isReformationCoordinator): ?>
                        <section class="panel case-content-section" id="case-updates">
                            <h2><i class="bi bi-journal-plus"></i> Case Updates</h2>
                            <p class="muted" style="margin:-8px 0 14px"><i class="bi bi-shield-lock"></i> Internal SDRU
                                records added by staff, coordinators, or the SDRU head. They are not shown to the
                                complainant and never change the case status.</p>
                            <div class="update-list">
                                <div class="update-item original">
                                    <div class="update-stage update-stage-original">Original Complaint</div>
                                    <div class="update-body">
                                        <strong><?= h($case['complaint_title'] ?? $case['case_classification']) ?></strong>
                                        <div class="muted">Submitted by <?= h($case['complainant_name']) ?> &middot; <?= h(date('M d, Y h:i A', strtotime($case['submitted_at']))) ?></div>
                                        <?php if (!empty($evidence)): ?>
                                        <div class="update-attachments">
                                            <?php foreach ($evidence as $file): ?>
                                            <a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=view"><i class="bi bi-paperclip"></i> <?= h($file['original_filename']) ?></a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (empty($updates)): ?>
                                <div class="muted">No case updates yet. Use "+ Add Case Update" to record additional
                                    internal information, evidence, or remarks.</div>
                                <?php endif; ?>
                                <?php foreach ($updates as $update): ?>
                                <?php $updateStageLabel = CaseUpdate::stageLabel($update['case_status_snapshot'] ?? $caseStatus); ?>
                                <div class="update-item <?= $updateStageLabel === 'Case Update' ? 'case-stage' : 'closed-stage' ?>">
                                    <div class="update-stage <?= $updateStageLabel === 'Case Update' ? 'update-stage-case' : 'update-stage-closed' ?>"><?= h($updateStageLabel) ?></div>
                                    <div class="update-body">
                                        <div class="update-meta">
                                            <span class="update-type"><i class="bi bi-tag"></i> <?= h($updateTypes[$update['update_type']] ?? $update['update_type']) ?></span>
                                            <span class="muted"><?= h(person_name($update['author_first_name'], $update['author_last_name'])) ?>
                                                (<?= h($update['author_role']) ?>) &middot; <?= h(date('M d, Y h:i A', strtotime($update['created_at']))) ?></span>
                                        </div>
                                        <div class="value"><?= nl2br(h($update['details'])) ?></div>
                                        <?php if (!empty($caseUpdateAttachments[(int) $update['update_id']])): ?>
                                        <div class="update-attachments">
                                            <?php foreach ($caseUpdateAttachments[(int) $update['update_id']] as $attachment): ?>
                                            <a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $attachment['evidence_id'] ?>&amp;mode=view"><i class="bi bi-paperclip"></i> <?= h($attachment['original_filename']) ?></a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <?php
                        $outcomeText = trim((string) ($case['outcome'] ?? ''));
                        $resolutionMeta = null;
                        if ($outcomeText !== '' && in_array($caseStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Archived'], true)) {
                            foreach ($history as $historyItem) {
                                if (($historyItem['new_status'] ?? '') === 'Resolved') {
                                    $resolutionMeta = $historyItem;
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="case-slot" id="case-outcome-slot">
                            <?php if ($outcomeText !== '' && in_array($caseStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Archived'], true)): ?>
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

                        <div class="case-slot" id="reformation-records-slot">
                            <?php if (!empty($reformationRecords)): ?>
                        <section class="panel">
                            <h2><i class="bi bi-journal-text"></i> Reformation Activities</h2>
                            <?php foreach ($reformationRecords as $record): ?>
                            <div class="reformation-entry">
                                <strong><?= h(($record['activity'] ?? '') ?: (($record['progress_status'] ?? '') === 'Ongoing' ? 'On Going' : ($record['progress_status'] ?: 'Progress Update'))) ?></strong>
                                <span class="muted">&middot; <?= h(date('M d, Y', strtotime($record['progress_date'] ?: $record['created_at']))) ?> &middot; <?= h(person_name($record['coordinator_first_name'], $record['coordinator_last_name'])) ?></span>
                                <div class="value"><?= nl2br(h($record['remarks'] ?: $record['activity'])) ?></div>
                                <?php foreach (ReformationRecord::attachments((int) $record['reformation_record_id']) as $attachment): ?>
                                <div class="update-attachments">
                                    <a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $attachment['evidence_id'] ?>&amp;mode=view"><i class="bi bi-paperclip"></i> <?= h($attachment['original_filename']) ?></a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </section>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside>
                        <?php $caseStatus = $case['status'] ?? ''; $caseLocked = in_array($caseStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Escalated', 'Archived'], true); ?>
                        <?php if (!in_array($viewerRoleKey, ['sdr-staff', 'sdru-staff'], true)): ?>
                        <?php if ($isReformationCoordinator): ?>
                        <section class="panel case-content-section" id="case-actions">
                            <h2><i class="bi bi-arrow-repeat"></i> Reformation Panel</h2>
                            <?php if (!$canManageCase): ?>
                            <p class="muted" style="margin-bottom:10px"><i class="bi bi-lock-fill"></i> This case is
                                not assigned to you for reformation, so its details and staff actions are unavailable.
                            </p>
                            <?php else: ?>
                            <div class="case-action-group">
                                    <h3 class="case-action-label">Reformation Work</h3>
                                <button type="button" class="btn btn-assign" id="openReformationActivityModal"
                                    <?= in_array($caseStatus, ['Resolved', 'Reformation in Progress'], true) ? '' : 'disabled title="Reformation begins once the case is resolved."' ?>><i class="bi bi-journal-text"></i> Add Progress Update</button>
                                <button type="button" class="btn btn-secondary" id="openReformationReportModal" style="margin-top:8px; width:100%"><i class="bi bi-upload"></i> Upload Reformation Report</button>
                            </div>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Completion</h3>
                                <?php if (!empty($case['reformation_completed_at'])): ?>
                                <p class="muted" style="margin-bottom:0"><i class="bi bi-check2-circle" style="color:#157000"></i> Reformation completed on <?= h(date('M d, Y', strtotime($case['reformation_completed_at']))) ?>.</p>
                                <?php else: ?>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>">
                                    <?= Security::csrfField() ?>
                                    <button class="btn btn-resolve" type="submit" name="case_action" value="reformation_completed"
                                        <?= $caseStatus === 'Reformation in Progress' ? 'data-swal-confirm="Mark this case reformation as completed?"' : 'disabled title="Reformation begins once the case is in progress."' ?>><i class="bi bi-check-lg"></i> Mark Reformation Completed</button>
                                </form>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($reformationReports)): ?>
                            <div class="case-action-group">
                                <h3 class="case-action-label">Uploaded Reports</h3>
                                <?php foreach ($reformationReports as $report): ?>
                                <div class="reformation-entry">
                                    <strong><i class="bi bi-file-earmark-pdf"></i> <?= h($report['report_title']) ?></strong>
                                    <div class="muted"><?= h($report['original_filename']) ?> &middot; <?= h(date('M d, Y', strtotime($report['created_at']))) ?></div>
                                    <div class="button-row" style="margin-top:6px">
                                        <a class="btn btn-secondary" href="reformation_report.php?id=<?= (int) $report['report_id'] ?>&amp;mode=view"><i class="bi bi-eye"></i> View</a>
                                        <a class="btn btn-secondary" href="reformation_report.php?id=<?= (int) $report['report_id'] ?>&amp;mode=download"><i class="bi bi-download"></i> Download</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </section>
                        <?php else: ?>
                        <section class="panel case-content-section" id="case-actions">
                            <h2>Case Actions</h2>
                            <?php if (!$canManageCase): ?>
                            <p class="muted" style="margin-bottom:10px"><i class="bi bi-lock-fill"></i> This case is
                                not assigned to you, so its details and staff actions are unavailable.
                            </p>
                            <?php else: ?>
                            <?php if ($caseLocked): ?><p class="muted" style="margin-bottom:10px"><i
                                    class="bi bi-lock-fill"></i> This case is closed. Only archiving remains available.
                            </p><?php endif; ?>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Staff Review</h3>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>">
                                    <?= Security::csrfField() ?>
                                    <textarea name="remarks" placeholder="Remarks / notes..."></textarea>
                                    <button class="btn btn-reject" type="submit" name="case_action" value="reject"
                                        <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>
                                        data-swal-confirm="Reject this complaint? This action changes its workflow status.">Reject</button>
                                </form>
                                <button type="button" class="btn btn-return" id="openReturnModal"
                                    <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>><i class="bi bi-arrow-return-left"></i> Return for Revision</button>
                            </div>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Case Classification</h3>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true">
                                    <?= Security::csrfField() ?>
                                    <select name="classification" id="caseClassification" required
                                        <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>>
                                        <option value="">Select classification</option>
                                        <?php foreach ($classificationOptions as $classification): ?>
                                        <option value="<?= h($classification) ?>" <?= ($case['case_classification'] ?? '') === $classification ? 'selected' : '' ?>><?= h($classification) ?></option>
                                        <?php endforeach; ?>
                                        <option value="Others">Others (specify)</option>
                                    </select>
                                    <input type="text" name="classification_other" id="caseClassificationOther"
                                        placeholder="Specify the case classification" autocomplete="off"
                                        maxlength="100" hidden <?= $caseLocked ? 'disabled' : '' ?>>
                                    <button class="btn btn-assign" type="submit" name="case_action" value="classify"
                                        <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>
                                        data-swal-action="classify" data-sicms-processing-label="Saving Classification...">Save Classification</button>
                                </form>
                            </div>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Case Workflow</h3>
                                <?php if ($viewerRoleKey !== 'coordinator'): ?>
                                <button type="button" class="btn btn-assign" id="openAssignModal"
                                    <?= $caseLocked ? 'disabled title="This case is closed."' : ($hasCaseClassification ? '' : 'disabled title="Classify the case before assigning a coordinator."') ?>><i class="bi bi-person-plus"></i> Assign to Discipline Coordinator</button>
                                <?php if ($isHeadViewer): ?>
                                <?php if (in_array($caseStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed'], true)): ?>
                                <button type="button" class="btn btn-assign" id="openAssignReformationModal"><i class="bi bi-arrow-repeat"></i> Assign to Reformation Coordinator</button>
                                <?php else: ?>
                                <button type="button" class="btn btn-assign" disabled title="A case must be resolved first."><i class="bi bi-arrow-repeat"></i> Assign to Reformation Coordinator</button>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php endif; ?>
                                <button type="button" class="btn btn-assign" id="openUpdateModal"
                                    <?= in_array($caseStatus, ['Escalated', 'Archived'], true) ? 'disabled title="This case is ' . ($caseStatus === 'Escalated' ? 'escalated' : 'archived') . ' and can no longer be updated."' : '' ?>><i class="bi bi-plus-circle"></i> Add Case Update</button>
                            </div>

                            <div class="case-action-group">
                                <h3 class="case-action-label">Case Outcome</h3>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>">
                                    <?= Security::csrfField() ?>
                                    <div class="outcome-field">
                                        <textarea name="outcome" id="caseOutcome" placeholder="Record the final outcome/resolution of this case (required for Resolve)."></textarea>
                                    </div>
                                    <div class="button-row two">
                                        <button class="btn btn-resolve" type="submit" name="case_action" value="<?= $caseStatus === 'Resolved' ? 'reopen' : 'resolve' ?>"
                                            <?= ($caseStatus === 'Under Investigation') ? 'data-swal-confirm="Mark this case as resolved?"' : (($caseStatus === 'Resolved') ? 'data-swal-confirm="Unresolve this case? Its status will return to Under Investigation."' : 'disabled title="Available once the case is Under Investigation."') ?>><i class="bi <?= $caseStatus === 'Resolved' ? 'bi-arrow-counterclockwise' : 'bi-check-lg' ?>"></i> <?= $caseStatus === 'Resolved' ? 'Unresolve Case' : 'Mark as Resolved' ?></button>
                                        <?php if ($caseStatus === 'Archived'): ?>
                                            <button class="btn btn-archive" type="submit" name="case_action" value="unarchive"
                                                data-swal-confirm="Unarchive this case? Its previous status will be restored.">Unarchive Case</button>
                                        <?php else: ?>
                                            <button class="btn btn-archive" type="submit" name="case_action" value="archive"
                                                <?= in_array($caseStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Escalated'], true) ? 'data-swal-confirm="Archive this case? It will be moved to Archived Cases."' : 'disabled title="Available once the case is Resolved or Escalated."' ?>>Archive Case</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                <form class="action-form" method="POST"
                                    action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>">
                                    <?= Security::csrfField() ?>
                                    <textarea name="remarks" placeholder="Reason for escalation (required to escalate)."></textarea>
                                    <button class="btn btn-escalate" type="submit" name="case_action" value="<?= $caseStatus === 'Escalated' ? 'withdraw_escalation' : 'escalate' ?>"
                                        <?= ($caseStatus === 'Under Investigation') ? 'data-swal-confirm="Mark this case as escalated? All case actions will be locked except archiving."' : (($caseStatus === 'Escalated') ? 'data-swal-confirm="Withdraw the escalation? The case will return to Under Investigation."' : 'disabled title="Available once the case is Under Investigation."') ?>><i class="bi <?= $caseStatus === 'Escalated' ? 'bi-arrow-counterclockwise' : 'bi-arrow-up-circle' ?>"></i> <?= $caseStatus === 'Escalated' ? 'Withdraw Escalation' : 'Mark as Escalated' ?></button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </section>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($canManageRespondentAccounts): ?>
                        <section class="panel" id="forward-respondent">
                            <h2><i class="bi bi-send"></i> Forward Case Information to Respondent</h2>
                            <?php if ($released): ?>
                                <p class="muted" style="font-size:12px;margin:0 0 12px">Permitted case information was released to the respondent(s) on <?= h(date('M d, Y h:i A', strtotime($case['respondent_released_at']))) ?>. Respondents see only what is checked when forwarding; evidence, witnesses, and internal notes are never shown to them.</p>
                            <?php else: ?>
                                <p class="muted" style="font-size:12px;margin:0 0 12px">The respondent(s) can only view the case once you forward it. Evidence, witnesses, and internal notes are never shown to respondents.</p>
                            <?php endif; ?>
                            <button type="button"
                                class="btn btn-assign"
                                id="openForwardModal"
                                style="width:100%"
                                <?= !$hasLinkedAccounts ? 'disabled title="No linked respondent accounts yet. Link a respondent first, then come back here to forward the case."' : '' ?>>
                                <i class="bi bi-send"></i> Forward
                            </button>
                            <?php if (!$hasLinkedAccounts): ?>
                                <p class="muted" style="font-size:11px;margin:6px 0 0">No linked respondent accounts yet. Link a respondent first, then come back here to forward the case.</p>
                            <?php endif; ?>
                        </section>
                        <?php endif; ?>

                        <section class="panel case-content-section" id="case-timeline">
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
                action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#returnModalOverlay">
                <?= Security::csrfField() ?>
                <div class="case-modal-body">
                    <label class="case-modal-label" for="revisionInstructions">Revision Guide / Instructions</label>
                    <textarea id="revisionInstructions" name="remarks" placeholder="Explain what the complainant needs to correct"
                        required></textarea>
                    <div class="revision-fields" id="revisionAreas">
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
                    <h3 id="assignModalTitle"><i class="bi bi-person-plus"></i> Assign to Discipline Coordinator</h3>
                    <p>Assign a discipline coordinator to handle this case.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
            <form class="action-form" method="POST"
                action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#assignModalOverlay">
                <?= Security::csrfField() ?>
                <div class="case-modal-body">
                    <label class="case-modal-label" for="coordinatorSelect">Discipline Coordinator</label>
                    <select id="coordinatorSelect" name="coordinator_account_id" required>
                        <option value="">Select discipline coordinator</option>
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
                        value="assign" data-swal-confirm="Assign this discipline coordinator to the case?">Assign to Discipline Coordinator</button>
                </div>
            </form>
        </div>
    </div>

    <div class="case-modal-overlay" id="assignReformationModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="assignReformationModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="assignReformationModalTitle"><i class="bi bi-arrow-repeat"></i> Assign to Reformation Coordinator</h3>
                    <p>Assign this resolved case for reformation work.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
            <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#assignReformationModalOverlay">
                <?= Security::csrfField() ?>
                <div class="case-modal-body">
                    <label class="case-modal-label" for="reformationCoordinatorSelect">Reformation Coordinator</label>
                    <select id="reformationCoordinatorSelect" name="reformation_coordinator_account_id" required>
                        <option value="">Select reformation coordinator</option>
                        <?php foreach ($reformationCoordinators as $coordinator): ?>
                        <option value="<?= (int) $coordinator['account_id'] ?>" <?= ((int) ($case['assigned_reformation_coordinator_account_id'] ?? 0) === (int) $coordinator['account_id']) ? 'selected' : '' ?>>
                            <?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="case-modal-label" for="reformationAssignRemarks">Assignment remarks</label>
                    <textarea id="reformationAssignRemarks" name="remarks" placeholder="Assignment remarks"></textarea>
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button class="btn btn-assign" type="submit" name="case_action" value="assign_reformation" data-swal-confirm="Assign this reformation coordinator to the case?">Assign to Reformation Coordinator</button>
                </div>
            </form>
        </div>
    </div>

    <div class="case-modal-overlay" id="reformationActivityModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="reformationActivityModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="reformationActivityModalTitle"><i class="bi bi-journal-text"></i> Add Progress Update</h3>
                    <p>Record the latest reformation progress for this case.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
             <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" enctype="multipart/form-data" data-sicms-validate data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#reformationActivityModalOverlay">
                 <?= Security::csrfField() ?>
                 <div class="case-modal-body">
                     <label class="case-modal-label" for="reformationActivityName">Activity Name</label>
                    <input id="reformationActivityName" type="text" name="activity_name" required placeholder="e.g. Counseling session, case review, follow-up" maxlength="120">
                    <label class="case-modal-label" for="progressDate">Date</label>
                    <input id="progressDate" type="date" value="<?= h(date('Y-m-d')) ?>" readonly>
                    <label class="case-modal-label" for="progressStatus">Progress Status</label>
                    <select id="progressStatus" name="progress_status" required>
                        <option value="">Select progress status</option>
                        <option value="Pending">Pending</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <label class="case-modal-label" for="progressRemarks">Remarks / Progress Notes</label>
                    <textarea id="progressRemarks" name="remarks" required placeholder="Enter observations and progress notes."></textarea>
                    <label class="case-modal-label" for="progressAttachments">Attachments (optional, max 5MB per file: PDF, JPG, PNG, DOCX)</label>
                    <input id="progressAttachments" type="file" name="progress_attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.docx" data-sicms-size-mb="5" data-sicms-accept-ext="pdf,jpg,jpeg,png,docx">
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                     <button class="btn btn-assign" type="submit" name="case_action" value="reformation_activity" data-direct-case-action data-swal-confirm="Add this reformation update?" data-sicms-processing-label="Saving Reformation Information..." data-sicms-processing-modal="true">Submit Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="case-modal-overlay" id="reformationReportModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="reformationReportModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="reformationReportModalTitle"><i class="bi bi-upload"></i> Upload Reformation Report</h3>
                    <p>Attach the final reformation report for this case.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
             <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" enctype="multipart/form-data" data-sicms-validate data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#reformationReportModalOverlay">
                 <?= Security::csrfField() ?>
                 <div class="case-modal-body">
                     <label class="case-modal-label" for="reformationReportTitle">Report Title</label>
                    <input id="reformationReportTitle" type="text" name="report_title" maxlength="120" value="Reformation Report" required>
                    <label class="case-modal-label" for="reformationReportFile">Reformation Report File</label>
                    <input id="reformationReportFile" type="file" name="reformation_report_file" accept=".pdf,.jpg,.jpeg,.png,.docx" required data-sicms-size-mb="5" data-sicms-accept-ext="pdf,jpg,jpeg,png,docx">
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                     <button class="btn btn-assign" type="submit" name="case_action" value="reformation_report_upload" data-direct-case-action data-swal-confirm="Upload this reformation report?" data-sicms-processing-label="Uploading Reformation Report..." data-sicms-processing-modal="true">Upload Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="case-modal-overlay" id="updateModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="updateModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="updateModalTitle"><i class="bi bi-plus-circle"></i> Add Case Update</h3>
                    <p>Internal SDRU record. The original complaint and the case status will remain unchanged.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
            <form class="action-form" method="POST"
                action="show.php?id=<?= (int) $case['complaint_id'] ?>" enctype="multipart/form-data" data-sicms-validate
                data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#updateModalOverlay">
                <?= Security::csrfField() ?>
                <div class="case-modal-body">
                    <label class="case-modal-label" for="caseUpdateType">Update Type</label>
                    <select id="caseUpdateType" name="update_type" required>
                        <option value="">Select update type</option>
                        <?php foreach ($updateTypes as $value => $label): ?>
                        <option value="<?= h($value) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="respondentUpdateFields" hidden>
                    <?php if (!empty($respondents)): ?>
                    <label class="case-modal-label" for="respondentId">Respondent</label>
                    <select id="respondentId" name="respondent_id">
                        <option value="">Select respondent</option>
                        <?php foreach ($respondents as $respondent): ?>
                        <option value="<?= (int) $respondent['respondent_id'] ?>" data-respondent="<?= h(json_encode([
                            'respondent_id' => (int) $respondent['respondent_id'],
                            'respondent_type' => (string) ($respondent['respondent_type'] ?? ''),
                            'full_name' => (string) ($respondent['full_name'] ?? ''),
                            'age' => (string) ($respondent['age'] ?? ''),
                            'gender' => (string) ($respondent['gender'] ?? ''),
                            'student_no' => (string) ($respondent['student_no'] ?? ''),
                            'employee_no' => (string) ($respondent['employee_no'] ?? ''),
                            'college' => (string) ($respondent['college'] ?? ''),
                            'course_year' => (string) ($respondent['course_year'] ?? ''),
                            'position' => (string) ($respondent['position'] ?? ''),
                            'office_department' => (string) ($respondent['office_department'] ?? ''),
                            'affiliation' => (string) ($respondent['affiliation'] ?? ''),
                            'contact_info' => (string) ($respondent['contact_info'] ?? ''),
                            'email' => (string) ($respondent['email'] ?? ''),
                            'address' => (string) ($respondent['address'] ?? ''),
                            'details' => (string) ($respondent['details'] ?? ''),
                        ], $jsonEncodeFlags)) ?>"><?= h($respondent['full_name']) ?> (<?= h($respondent['respondent_type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <div class="case-update-respondent-grid">
                        <input type="hidden" name="respondent_linked_account_id" value="">
                        <select name="respondent_type"><option value="">Respondent Type</option><option>Student</option><option>Employee</option><option>Private Individual</option><option>Other</option></select>
                        <input name="respondent_name" placeholder="Full Name">
                        <input name="respondent_age" type="number" min="1" max="120" placeholder="Age">
                        <select name="respondent_gender"><option value="">Gender</option><option>Male</option><option>Female</option></select>
<div class="update-respondent-field" data-update-respondent-types="Student">
                            <div class="student-number-row">
                                <input name="respondent_student_no" placeholder="Student Number">
                                <button type="button" class="btn btn-secondary btn-find-student" data-find-student>Find</button>
                            </div>
                        </div>
                        <div class="update-respondent-field" data-update-respondent-types="Student"><input name="respondent_college" placeholder="College"></div>
                        <div class="update-respondent-field" data-update-respondent-types="Student"><input name="respondent_course" placeholder="Course / Program"></div>
                        <div class="update-respondent-field" data-update-respondent-types="Student"><input name="respondent_section" placeholder="Section"></div>
                        <div class="student-account-results" data-update-respondent-types="Student" data-student-results></div>
                         <div class="update-respondent-field" data-update-respondent-types="Employee"><input name="respondent_employee_no" placeholder="Employee Number"></div>
                         <div class="update-respondent-field" data-update-respondent-types="Employee"><input name="respondent_position" placeholder="Position"></div>
                         <div class="update-respondent-field" data-update-respondent-types="Employee"><input name="respondent_department" placeholder="College / Office / Department"></div>
                        <div class="update-respondent-field" data-update-respondent-types="Other"><input name="respondent_affiliation" placeholder="Affiliation / Organization"></div>
                        <input name="respondent_contact" placeholder="Contact Number">
                        <input name="respondent_email" type="email" placeholder="Email">
                        <input name="respondent_address" placeholder="Address">
                        <input name="respondent_details" placeholder="Other Details">
                    </div>
                    </div>
                    <div id="standardCaseUpdateFields">
                    <label class="case-modal-label" for="caseUpdateDetails">Details / Remarks</label>
                    <textarea id="caseUpdateDetails" name="details" required
                        placeholder="Enter additional details, evidence notes, clarifications, investigation remarks, or administrative information for this case."></textarea>
                    </div>
                    <label class="case-modal-label" for="caseUpdateAttachments">Attach Evidence (optional, max 5MB
                        per file: PDF, JPG, PNG, DOCX)</label>
                    <input id="caseUpdateAttachments" type="file" name="attachments[]" multiple
                        accept=".pdf,.jpg,.jpeg,.png,.docx" data-sicms-size-mb="5" data-sicms-accept-ext="pdf,jpg,jpeg,png,docx">
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button class="btn btn-assign" type="submit" name="case_action" value="case_update" data-direct-case-action data-swal-confirm="Add this case update?" data-sicms-processing-label="Saving Case Update..." data-sicms-processing-modal="true"
                        id="submitCaseUpdate"><i class="bi bi-plus-circle"></i> <span id="submitCaseUpdateLabel">Add Case Update</span></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($canManageRespondentAccounts): ?>
    <div class="case-modal-overlay" id="forwardModalOverlay">
        <div class="case-modal" role="dialog" aria-modal="true" aria-labelledby="forwardModalTitle">
            <div class="case-modal-header">
                <div>
                    <h3 id="forwardModalTitle"><i class="bi bi-send"></i> Forward Case Information to Respondent</h3>
                    <p>Click the linked respondent(s) to receive the case and select which information they may see. Re-forwarding updates the permitted sections.</p>
                </div>
                <button class="case-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
            </div>
            <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-ajax-target="<?= h($caseRefreshTargets) ?>" data-ajax-reset="true" data-ajax-close="#forwardModalOverlay">
                <?= Security::csrfField() ?>
                <input type="hidden" name="case_action" value="forward_to_respondents">
                <div class="case-modal-body">
                    <label class="case-modal-label">Linked Respondent(s) to Receive the Case</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px">
                    <?php foreach ($linkedForwardAccounts as $ra): ?>
                    <label style="display:flex;gap:8px;align-items:flex-start;font-weight:400">
                        <input type="checkbox" name="respondent_ids[]" value="<?= (int) $ra['linked_account_id'] ?>" checked style="margin-top:1px">
                        <span>
                            <?= h($ra['full_name']) ?><?php if (!empty($ra['student_no'])): ?> <span class="muted"><?= h($ra['student_no']) ?></span><?php endif; ?>
                            <br>
                            <span class="muted" style="font-size:11px"><?= h($ra['account_email']) ?> &middot; Linked &amp; active</span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                    </div>
                    <p class="muted" style="font-size:11px;margin:6px 0 14px">Only ticked respondent(s) will receive the case. Untick a respondent to leave the case hidden from them.</p>
                    <label class="case-modal-label" for="respondent_extra_email">Additional Respondent Email (optional)</label>
                    <input type="email" id="respondent_extra_email" name="respondent_extra_email" class="form-control" placeholder="e.g. respondent@example.com" style="margin-top:6px" value="<?= h($_POST['respondent_extra_email'] ?? '') ?>">
                    <p class="muted" style="font-size:11px;margin:6px 0 14px">A copy of the case notice will also be sent to this typed email address.</p>
                    <label class="case-modal-label">Permitted Respondent Information</label>
                    <?php foreach ($visibilityLabels as $key => $label): ?>
                        <?php if ($key === 'complaint_details') continue; ?>
                        <label style="display:flex;gap:8px;align-items:center;font-weight:400;font-size:13px;margin:3px 0">
                            <input type="checkbox" name="respondent_visibility[]" value="<?= h($key) ?>" <?= $visibility[$key] ? 'checked' : '' ?>>
                            <?= h($label) ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="muted" style="font-size:11px;margin:4px 0 0">Complaint Details (narrative) is always included as the basis of the respondent&rsquo;s counter-statement.</p>
                    <div class="notice" style="font-size:12px;margin:12px 0 0;background:#fffdf5;border:1px solid #ead9a5;border-left:4px solid #b57600;border-radius:8px;color:#6a614c;padding:12px 14px">
                        <i class="bi bi-info-circle"></i> The selected respondent(s) will be sent the case through <strong>Gmail</strong> and receive an <strong>in-app notification inside DARIS</strong>, so they can review the case and file their counter-statement.
                    </div>
                </div>
                <div class="case-modal-actions">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button class="btn btn-assign" type="submit" data-sicms-processing-label="<?= $released ? 'Updating release...' : 'Forwarding case...' ?>" data-swal-confirm="<?= $released ? 'Update and re-forward the permitted case information to the selected respondent(s)?' : 'Forward the permitted case information to the selected respondent(s)? They will be notified by email and in-app.' ?>"><i class="bi bi-send"></i> <?= $released ? 'Re-Forward' : 'Forward to Respondent' ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    const caseConversation = document.getElementById('caseConversation');

    if (caseConversation) {
        caseConversation.scrollTop = caseConversation.scrollHeight;
    }

    const caseRespondents = <?= json_encode(array_values(array_map(function ($respondent) {
        return [
            'respondent_id' => (int) $respondent['respondent_id'],
            'respondent_type' => (string) ($respondent['respondent_type'] ?? ''),
            'full_name' => (string) ($respondent['full_name'] ?? ''),
            'age' => (string) ($respondent['age'] ?? ''),
            'gender' => (string) ($respondent['gender'] ?? ''),
            'student_no' => (string) ($respondent['student_no'] ?? ''),
            'employee_no' => (string) ($respondent['employee_no'] ?? ''),
            'college' => (string) ($respondent['college'] ?? ''),
            'course_year' => (string) ($respondent['course_year'] ?? ''),
            'position' => (string) ($respondent['position'] ?? ''),
            'office_department' => (string) ($respondent['office_department'] ?? ''),
            'affiliation' => (string) ($respondent['affiliation'] ?? ''),
            'contact_info' => (string) ($respondent['contact_info'] ?? ''),
            'email' => (string) ($respondent['email'] ?? ''),
            'address' => (string) ($respondent['address'] ?? ''),
            'details' => (string) ($respondent['details'] ?? ''),
        ];
    }, $respondents)), $jsonEncodeFlags) ?>;

    (() => {
        const archivedUrl = <?= json_encode(app_route('archived_cases.index'), $jsonEncodeFlags) ?>;

        let updateType = document.getElementById('caseUpdateType');
        let respondentFields = document.getElementById('respondentUpdateFields');
        let standardFields = document.getElementById('standardCaseUpdateFields');
        let respondentId = document.getElementById('respondentId');
        let respondentType = document.querySelector('[name="respondent_type"]');
        let detailsField = document.getElementById('caseUpdateDetails');
        const syncRespondentTypeFields = () => {
            const selectedType = respondentType?.value || '';
            document.querySelectorAll('[data-update-respondent-types]').forEach(field => {
                const allowedTypes = field.dataset.updateRespondentTypes.split(',');
                field.hidden = !allowedTypes.includes(selectedType);
                const input = field.querySelector('input');
                if (input && field.hidden) input.value = '';
            });
        };
        respondentType?.addEventListener('change', syncRespondentTypeFields);
        syncRespondentTypeFields();
        const syncUpdateFields = () => {
            const isRespondentUpdate = updateType?.value === 'additional_details';
            if (respondentFields) respondentFields.hidden = !isRespondentUpdate;
            if (standardFields) standardFields.hidden = isRespondentUpdate;
            if (respondentId) respondentId.required = isRespondentUpdate;
            if (detailsField) detailsField.required = !isRespondentUpdate;
            const submitButton = document.getElementById('submitCaseUpdate');
            const submitLabel = document.getElementById('submitCaseUpdateLabel');
            if (submitButton && submitLabel) {
                submitLabel.textContent = isRespondentUpdate ? 'Submit Respondent Details' : 'Add Case Update';
                submitButton.dataset.swalConfirm = isRespondentUpdate
                    ? <?= json_encode(in_array($viewerRoleKey, ['head-of-sdru', 'sdru-head'], true)
                        ? 'Update this respondent\'s details? They will be applied immediately.'
                        : 'Submit these respondent details for head approval?', $jsonEncodeFlags) ?>
                    : <?= json_encode(in_array($viewerRoleKey, ['head-of-sdru', 'sdru-head'], true)
                        ? 'Add this case update? It will be recorded immediately.'
                        : 'Submit this case update for head approval?', $jsonEncodeFlags) ?>;
            }
        };
        updateType?.addEventListener('change', syncUpdateFields);
        syncUpdateFields();

        const respondentFieldMap = {
            respondent_name: 'full_name',
            respondent_age: 'age',
            respondent_gender: 'gender',
            respondent_student_no: 'student_no',
            respondent_employee_no: 'employee_no',
            respondent_college: 'college',
            respondent_position: 'position',
            respondent_department: 'office_department',
            respondent_affiliation: 'affiliation',
            respondent_contact: 'contact_info',
            respondent_email: 'email',
            respondent_address: 'address',
            respondent_details: 'details'
        };
        const setRespondentField = (name, value) => {
            const field = document.querySelector('[name="' + name + '"]');
            if (field) field.value = value ?? '';
        };
        const splitCourseYear = value => {
            const parts = String(value || '').split('|').map(part => part.trim());
            return { course: parts[0] || '', section: parts[1] || '' };
        };
        const clearRespondentFields = () => {
            if (respondentType) respondentType.value = '';
            syncRespondentTypeFields();
            Object.keys(respondentFieldMap).forEach(name => setRespondentField(name, ''));
            setRespondentField('respondent_course', '');
            setRespondentField('respondent_section', '');
        let linkedAccountField = document.querySelector('[name="respondent_linked_account_id"]');
            if (linkedAccountField) linkedAccountField.value = '';
            const studentResults = document.querySelector('[data-student-results]');
            if (studentResults) studentResults.innerHTML = '';
        };
        const fillRespondentFields = () => {
            const selectedId = respondentId ? parseInt(respondentId.value, 10) : 0;
            const selectedOption = respondentId?.selectedOptions?.[0];
            let data = selectedId ? caseRespondents.find(r => r.respondent_id === selectedId) : null;
            if (!data && selectedOption?.dataset.respondent) {
                try {
                    data = JSON.parse(selectedOption.dataset.respondent);
                } catch (error) {}
            }
            if (!data) {
                clearRespondentFields();
                return;
            }
            if (respondentType) respondentType.value = data.respondent_type;
            syncRespondentTypeFields();
            Object.entries(respondentFieldMap).forEach(([name, key]) => setRespondentField(name, data[key]));
            const { course, section } = splitCourseYear(data.course_year);
            setRespondentField('respondent_course', course);
            setRespondentField('respondent_section', section);
        };
        respondentId?.addEventListener('change', () => {
            if (linkedAccountField) linkedAccountField.value = '';
            if (studentResultsBox) studentResultsBox.innerHTML = '';
            fillRespondentFields();
        });
        fillRespondentFields();

        const studentLookupUrl = <?= json_encode(app_url('web/api/student_lookup.php'), $jsonEncodeFlags) ?>;
        let studentLookupBtn = document.querySelector('[data-find-student]');
        let studentResultsBox = document.querySelector('[data-student-results]');
        let studentNumberField = document.querySelector('[name="respondent_student_no"]');
        const linkedAccountField = document.querySelector('[name="respondent_linked_account_id"]');

        const renderStudentResults = accounts => {
            if (!studentResultsBox) return;
            studentResultsBox.innerHTML = '';
            if (!accounts.length) {
                const empty = document.createElement('div');
                empty.className = 'find-hint';
                empty.textContent = 'No student account found for this Student Number.';
                studentResultsBox.appendChild(empty);
                return;
            }
            accounts.forEach(account => {
                const card = document.createElement('div');
                card.className = 'find-student-card';

                const meta = document.createElement('div');
                meta.className = 'find-student-meta';
                const name = document.createElement('strong');
                name.textContent = account.full_name || 'Unnamed Student';
                const sub = document.createElement('span');
                sub.textContent = [account.student_number, account.college, account.course].filter(Boolean).join(' · ');
                meta.appendChild(name);
                meta.appendChild(sub);

                const select = document.createElement('button');
                select.type = 'button';
                select.className = 'btn btn-secondary btn-find-student';
                select.textContent = 'Select Student';
                select.addEventListener('click', () => {
                    setRespondentField('respondent_name', account.full_name || '');
                    setRespondentField('respondent_student_no', account.student_number);
                    setRespondentField('respondent_college', account.college);
                    setRespondentField('respondent_course', account.course);
                    setRespondentField('respondent_section', account.section);
                    setRespondentField('respondent_contact', account.phone_number);
                    setRespondentField('respondent_email', account.email);
                    setRespondentField('respondent_address', account.address);
                    if (account.gender === 'Male' || account.gender === 'Female') {
                        setRespondentField('respondent_gender', account.gender);
                    }
                    if (Number.isFinite(account.age) && account.age > 0) {
                        setRespondentField('respondent_age', String(account.age));
                    }
                    if (linkedAccountField) linkedAccountField.value = String(account.account_id);
                    if (studentResultsBox) {
                        studentResultsBox.innerHTML = '';
                        const selected = document.createElement('div');
                        selected.className = 'find-hint';
                        selected.textContent = 'Selected student account: ' + (account.full_name || '') + ' (' + account.student_number + '). The fields remain editable.';
                        studentResultsBox.appendChild(selected);
                    }
                });

                card.appendChild(meta);
                card.appendChild(select);
                studentResultsBox.appendChild(card);
            });
        };

        studentNumberField?.addEventListener('input', () => {
            if (linkedAccountField) linkedAccountField.value = '';
            if (studentResultsBox) studentResultsBox.innerHTML = '';
        });

        studentLookupBtn?.addEventListener('click', async () => {
            const query = (studentNumberField?.value || '').trim();
            if (!studentResultsBox) return;
            studentResultsBox.innerHTML = '';
            if (!query) {
                const hint = document.createElement('div');
                hint.className = 'find-hint';
                hint.textContent = 'Enter a student number to search first.';
                studentResultsBox.appendChild(hint);
                return;
            }
            if (linkedAccountField) linkedAccountField.value = '';
            const hint = document.createElement('div');
            hint.className = 'find-hint';
            hint.textContent = 'Searching…';
            studentResultsBox.appendChild(hint);
            try {
                const response = await fetch(studentLookupUrl + '?student_number=' + encodeURIComponent(query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Search failed.');
                renderStudentResults(data.accounts || []);
            } catch (error) {
                studentResultsBox.innerHTML = '';
                const failed = document.createElement('div');
                failed.className = 'find-hint';
                failed.textContent = error.message || 'Unable to search for student accounts.';
                studentResultsBox.appendChild(failed);
            }
        });

        respondentType?.addEventListener('change', () => {
            if (linkedAccountField) linkedAccountField.value = '';
            if (studentResultsBox) studentResultsBox.innerHTML = '';
        });

        document.getElementById('updateModalOverlay')?.setAttribute('data-case-update-bound', 'true');

        const bindCaseUpdateModal = () => {
            const modal = document.getElementById('updateModalOverlay');
            if (!modal || modal.dataset.caseUpdateBound === 'true') return;
            modal.dataset.caseUpdateBound = 'true';
            updateType = document.getElementById('caseUpdateType');
            respondentFields = document.getElementById('respondentUpdateFields');
            standardFields = document.getElementById('standardCaseUpdateFields');
            respondentId = document.getElementById('respondentId');
            respondentType = modal.querySelector('[name="respondent_type"]');
            detailsField = document.getElementById('caseUpdateDetails');
            studentLookupBtn = modal.querySelector('[data-find-student]');
            studentResultsBox = modal.querySelector('[data-student-results]');
            studentNumberField = modal.querySelector('[name="respondent_student_no"]');
            linkedAccountField = modal.querySelector('[name="respondent_linked_account_id"]');

            updateType?.addEventListener('change', syncUpdateFields);
            respondentType?.addEventListener('change', syncRespondentTypeFields);
            respondentId?.addEventListener('change', () => {
                if (linkedAccountField) linkedAccountField.value = '';
                if (studentResultsBox) studentResultsBox.innerHTML = '';
                fillRespondentFields();
            });
            studentNumberField?.addEventListener('input', () => {
                if (linkedAccountField) linkedAccountField.value = '';
                if (studentResultsBox) studentResultsBox.innerHTML = '';
            });
            studentLookupBtn?.addEventListener('click', async () => {
                const query = (studentNumberField?.value || '').trim();
                if (!studentResultsBox) return;
                studentResultsBox.innerHTML = '';
                if (!query) {
                    const hint = document.createElement('div');
                    hint.className = 'find-hint';
                    hint.textContent = 'Enter a student number to search first.';
                    studentResultsBox.appendChild(hint);
                    return;
                }
                if (linkedAccountField) linkedAccountField.value = '';
                const hint = document.createElement('div');
                hint.className = 'find-hint';
                hint.textContent = 'Searching…';
                studentResultsBox.appendChild(hint);
                try {
                    const response = await fetch(studentLookupUrl + '?student_number=' + encodeURIComponent(query), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Search failed.');
                    renderStudentResults(data.accounts || []);
                } catch (error) {
                    studentResultsBox.innerHTML = '';
                    const failed = document.createElement('div');
                    failed.className = 'find-hint';
                    failed.textContent = error.message || 'Unable to search for student accounts.';
                    studentResultsBox.appendChild(failed);
                }
            });
            syncRespondentTypeFields();
            syncUpdateFields();
            fillRespondentFields();
        };
        document.addEventListener('daris:ajax-success', bindCaseUpdateModal);

        document.addEventListener('click', event => {
            const button = event.target.closest('[data-direct-case-action]');
            if (!button) return;
            const form = button.form;
            const action = button.value;
            const required = Array.from(form?.querySelectorAll('[required]') || []);
            const missing = required.find(control => !control.checkValidity());
            if (!missing) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            let title = 'Required Information Missing';
            let text = 'Please complete the required information before saving.';
            if (action === 'reformation_report_upload' && missing.type === 'file') {
                title = 'Report Required';
                text = 'Please select a reformation report to upload.';
            } else if (action === 'case_update') {
                title = 'Update Required';
                text = missing.name === 'details' ? 'Please enter the case update before saving.' : 'Please select the case update type before saving.';
            } else if (action === 'reformation_activity') {
                title = 'Reformation Information Required';
                text = 'Please complete the activity, progress status, and observations before saving.';
            }
            window.DARISAlert?.toast('warning', title, text);
            missing.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
            window.setTimeout(() => missing.focus?.(), 180);
        });

        document.addEventListener('click', event => {
            const button = event.target.closest('[data-swal-confirm], [data-swal-action]');
            if (!button) return;
            event.preventDefault();
            const action = button.dataset.swalAction || button.form?.querySelector('[name="case_action"]')?.value || button.value || '';
            const caseNumber = <?= json_encode((string) ($case['case_number'] ?? 'this case'), $jsonEncodeFlags) ?>;
            const selectedCoordinator = button.form?.querySelector('select[name="coordinator_account_id"], select[name="reformation_coordinator_account_id"]')?.selectedOptions?.[0]?.textContent?.trim();
            const actionCopy = {
                reject: ['Reject this complaint?', 'The complaint will be rejected and the complainant will be notified.'],
                return: ['Return complaint for revision?', 'The complainant will be notified and asked to revise the selected information.'],
                assign: ['Assign this case' + (selectedCoordinator ? ` to ${selectedCoordinator}` : '') + '?', 'The selected discipline coordinator will become responsible for handling ' + caseNumber + '.'],
                assign_reformation: ['Assign this Reformation Coordinator?', 'The selected coordinator will become responsible for the reformation stage of ' + caseNumber + '.'],
                classify: ['Save this case classification?', 'The selected classification will be recorded for ' + caseNumber + '.'],
                resolve: ['Resolve this case?', caseNumber + ' will be marked as resolved.'],
                reopen: ['Reopen this case?', caseNumber + ' will return to Under Investigation.'],
                archive: ['Archive this case?', caseNumber + ' will be moved to Archived Cases.'],
                unarchive: ['Restore this case?', caseNumber + ' will return to active cases with its previous status.'],
                escalate: ['Escalate this case?', caseNumber + ' will be marked as escalated and most case actions will be locked.'],
                withdraw_escalation: ['Withdraw this escalation?', caseNumber + ' will return to Under Investigation.'],
                reformation_completed: ['Complete the reformation stage?', caseNumber + ' will be marked as Reformation Completed.'],
                forward_counter_statement: ['Forward Counter-Statement?', 'The permitted counter-statement information will be shared with the complainant.'],
                request_counter_revision: ['Return Counter-Statement for Revision?', 'The respondent will be asked to revise the submitted counter-statement.'],
                proceed_counter_statement: ['Proceed to Investigation?', 'The counter-statement will be accepted and the case investigation will continue.'],
                reformation_activity: ['Add Reformation Update?', 'This update will become part of the case record.'],
                reformation_report_upload: ['Upload Reformation Report?', 'The selected report will become part of the case record.'],
                case_update: ['Add this Case Update?', 'The update will be added without changing the current case status.'],
                forward_to_respondents: ['Forward Case to Respondent?', 'The selected case information will be shared with the selected respondent recipients.'],
            };
            const copy = actionCopy[action];
            const topNotice = (icon, title, text, focusTarget) => {
                const options = { toast: true, position: 'top', icon, title, text, timer: 5200, timerProgressBar: true, showConfirmButton: false, showCloseButton: true, backdrop: false };
                (window.DARISAlert?.toast(icon, title, text) || Swal.fire(options));
                if (focusTarget) {
                    focusTarget.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
                    window.setTimeout(() => focusTarget.focus?.(), 180);
                }
            };

            if (action === 'classify') {
                const classification = button.form?.querySelector('[name="classification"]');
                const other = button.form?.querySelector('[name="classification_other"]');
                if (!classification?.value) {
                    topNotice('warning', 'Classification Required', 'Please select a classification before saving.', classification);
                    return;
                }
                if (classification.value === 'Others' && !other?.value.trim()) {
                    topNotice('warning', 'Classification Required', 'Please specify the case classification before saving.', other);
                    return;
                }
                button.dataset.sicmsProcessingModal = 'true';
            }

            if (action === 'return') {
                const selectedAreas = button.form?.querySelectorAll('[name="revision_fields[]"]:checked') || [];
                const instructions = button.form?.querySelector('[name="remarks"]');
                if (!selectedAreas.length) {
                    topNotice('warning', 'Select Revision Area', 'Please select at least one part of the complaint that needs revision.', button.form?.querySelector('[name="revision_fields[]"]'));
                    return;
                }
                if (!instructions?.value.trim()) {
                    topNotice('warning', 'Revision Instructions Required', 'Please explain what the complainant needs to revise.', instructions);
                    return;
                }
            }

            if (action === 'assign' || action === 'assign_reformation') {
                const coordinator = button.form?.querySelector('select[name="coordinator_account_id"], select[name="reformation_coordinator_account_id"]');
                if (!coordinator?.value) {
                    topNotice('warning', 'Coordinator Required', 'Please select a coordinator before assigning this case.', coordinator);
                    return;
                }
            }
            if (action === 'resolve') {
                const outcome = button.form?.querySelector('[name="outcome"]');
                if (!outcome?.value.trim()) {
                    topNotice('warning', 'Case Outcome Required', 'Please record the case outcome before marking the case as resolved.', outcome);
                    return;
                }
            }
            if (action === 'forward_to_respondents') {
                const recipients = button.form?.querySelectorAll('[name="respondent_ids[]"]:checked') || [];
                const extraEmail = button.form?.querySelector('[name="respondent_extra_email"]');
                if (!recipients.length && !extraEmail?.value.trim()) {
                    topNotice('warning', 'Respondent Required', 'Please select a linked respondent or provide a respondent email before forwarding the case.', extraEmail);
                    return;
                }
            }
            const firstMissingRequired = Array.from(button.form?.querySelectorAll('[required]') || [])
                .find(control => !control.checkValidity());
            if (firstMissingRequired) {
                const label = button.form?.querySelector(`label[for="${firstMissingRequired.id}"]`)?.textContent?.replace('*', '').trim();
                topNotice('warning', 'Required Information Missing', `Please complete ${label || 'the required information'} before continuing.`, firstMissingRequired);
                return;
            }
            let config = {
                icon: 'question',
                title: copy?.[0] || button.dataset.confirmTitle || 'Confirm this action?',
                text: copy?.[1] || button.dataset.swalConfirm,
                showCancelButton: true,
                confirmButtonText: button.dataset.confirmButton || 'Continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                allowOutsideClick: false
            };

            if (action === 'reject') {
                const remarksField = button.form?.querySelector('[name="remarks"]');
                const remarks = (remarksField?.value || '').trim();
                if (!remarks) {
                    topNotice('warning', 'Reason Required', 'Please provide a reason for rejecting this complaint.', remarksField);
                    return;
                }
                config.title = 'Reject Complaint?';
                config.text = 'Are you sure you want to reject this complaint? The complainant will receive the reason you provided.';
                config.icon = 'warning';
                config.confirmButtonText = 'Yes, Reject Complaint';
                config.confirmButtonColor = '#c0392b';
            } else if (action === 'archive') {
                config.icon = 'warning';
                config.confirmButtonText = 'Yes, archive';
                config.confirmButtonColor = '#9a6b00';
            } else if (action === 'unarchive') {
                config.icon = 'warning';
                config.confirmButtonText = 'Yes, unarchive';
                config.confirmButtonColor = '#9a6b00';
            } else if (action === 'reopen') {
                config.icon = 'warning';
                config.confirmButtonText = 'Yes, unresolve';
            } else if (action === 'return') {
                config.icon = 'warning';
                config.title = 'Return Complaint for Revision?';
                config.text = 'Are you sure you want to return this complaint to the complainant for revision? The revision instructions will be included.';
                config.confirmButtonText = 'Yes, Return for Revision';
                config.confirmButtonColor = '#b8860b';
            } else if (action === 'assign') {
                config.confirmButtonText = 'Yes, assign';
            } else if (action === 'classify') {
                config.confirmButtonText = 'Yes, save';
            } else if (action === 'resolve') {
                config.confirmButtonText = 'Yes, resolve';
                config.confirmButtonColor = '#157000';
            } else if (action === 'escalate') {
                const escalateRemarksField = button.form?.querySelector('[name="remarks"]');
                const escalateRemarks = (escalateRemarksField?.value || '').trim();
                if (!escalateRemarks) {
                    topNotice('warning', 'Escalation Reason Required', 'Please provide the reason for escalating this case.', escalateRemarksField);
                    return;
                }
                config.icon = 'warning';
                config.confirmButtonText = 'Yes, escalate';
                config.confirmButtonColor = '#c2410c';
            } else if (action === 'withdraw_escalation') {
                config.icon = 'warning';
                config.confirmButtonText = 'Yes, withdraw';
            }

            (window.DARISAlert?.fire(config) || Swal.fire(config)).then(result => {
                if (!result.isConfirmed) return;

                if (action === 'archive') {
                    const originalButtonHtml = button.innerHTML;
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                    button.innerHTML = '<span class="sicms-processing-spinner" aria-hidden="true"></span> Archiving case...';
                    window.DARISAlert?.processing('Archiving Case...');
                    const fd = new FormData(button.form);
                    if (button.name) fd.set(button.name, button.value);
                    fetch(button.form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                        credentials: 'same-origin'
                    })
                        .then(async response => {
                            const contentType = response.headers.get('content-type') || '';
                            if (contentType.includes('application/json')) {
                                const data = await response.json();
                                if (data && data.success) {
                                    window.DARISAlert?.closeProcessing?.();
                                    window.DARISAlert?.toast('success', 'Case Archived', `Case ${data.case_number || caseNumber} has been archived successfully.`);
                                    window.setTimeout(() => { window.location.href = archivedUrl; }, 1300);
                                    return;
                                }
                                const errors = Array.isArray(data?.errors) ? data.errors.join(' ') : (data?.message || 'Please try again.');
                                throw new Error(errors);
                            }

                            if (!response.ok) throw new Error('The case action could not be completed.');
                            const html = await response.text();
                            const parsed = new DOMParser().parseFromString(html, 'text/html');
                            const flash = parsed.querySelector('.alert-success, .alert-error, .alert-danger');
                            if (flash?.matches('.alert-error, .alert-danger')) {
                                throw new Error(flash.textContent.trim() || 'The case action could not be completed.');
                            }
                            if (flash) {
                                window.location.reload();
                                return;
                            }
                            throw new Error('The case action returned an unexpected response.');
                        })
                        .catch(error => {
                            window.DARISAlert?.closeProcessing?.();
                            window.DARISAlert?.toast('error', 'Unable to Archive Case', error.message || 'The case was not archived. Please try again.', { timer: 7000 });
                            button.disabled = false;
                            button.removeAttribute('aria-busy');
                            button.innerHTML = originalButtonHtml;
                        });
                } else {
                    const processingLabels = {
                        approval_decision: 'Processing approval...',
                        reject: 'Rejecting complaint...', return: 'Returning complaint...', assign: 'Assigning coordinator...',
                        assign_reformation: 'Assigning coordinator...', classify: 'Saving classification...', resolve: 'Resolving case...',
                        reopen: 'Reopening case...', archive: 'Archiving case...', unarchive: 'Restoring case...',
                        escalate: 'Escalating case...', withdraw_escalation: 'Withdrawing escalation...',
                        reformation_completed: 'Completing reformation...', forward_counter_statement: 'Forwarding statement...',
                        request_counter_revision: 'Returning statement...', proceed_counter_statement: 'Starting Investigation...',
                        reformation_activity: 'Saving reformation update...', reformation_report_upload: 'Uploading report...',
                        case_update: 'Saving case update...', forward_to_respondents: 'Forwarding case...'
                    };
                    if (!button.dataset.sicmsProcessingLabel) button.dataset.sicmsProcessingLabel = processingLabels[action] || 'Processing...';
                    button.dataset.sicmsProcessingModal = 'true';
                    button.form.requestSubmit(button);
                }
            });
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Enter' || event.defaultPrevented) return;
            const control = event.target;
            if (control instanceof HTMLTextAreaElement) return;
            const form = control instanceof HTMLFormElement ? control : control.closest('form');
            const submitter = control.closest('button[type="submit"], input[type="submit"]')
                || form?.querySelector('button[type="submit"][data-swal-confirm], input[type="submit"][data-swal-confirm]');
            if (!submitter?.dataset.swalConfirm) return;
            event.preventDefault();
            submitter.click();
        });

        const modalOverlays = {
            openReturnModal: 'returnModalOverlay',
            openAssignModal: 'assignModalOverlay',
            openAssignReformationModal: 'assignReformationModalOverlay',
            openReformationActivityModal: 'reformationActivityModalOverlay',
            openReformationReportModal: 'reformationReportModalOverlay',
            openUpdateModal: 'updateModalOverlay',
            openForwardModal: 'forwardModalOverlay',
        };

        const setModalOpen = (overlayId, open) => {
            const overlay = document.getElementById(overlayId);
            if (!overlay) return;
            overlay.classList.toggle('open', open);
            overlay.hidden = !open;
        };

        document.addEventListener('click', event => {
            const opener = event.target.closest('[id]');
            if (!opener || !modalOverlays[opener.id] || opener.disabled) return;
            setModalOpen(modalOverlays[opener.id], true);
        });

        document.addEventListener('click', event => {
            const closer = event.target.closest('[data-close-modal]');
            if (!closer) return;
            const overlay = closer.closest('.case-modal-overlay');
            if (overlay) setModalOpen(overlay.id, false);
        });

        document.addEventListener('mousedown', event => {
            const overlay = event.target.closest('.case-modal-overlay');
            if (overlay && event.target === overlay) setModalOpen(overlay.id, false);
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('.case-modal-overlay.open').forEach(overlay => setModalOpen(overlay.id, false));
        });

        <?php if ($message): ?>
        window.addEventListener('load', () => {
            const message = <?= json_encode((string) $message, $jsonEncodeFlags) ?>;
            const messageKey = message.toLowerCase();
            const title = messageKey.includes('classification') ? 'Classification Saved'
                : messageKey.includes('rejected') ? 'Complaint Rejected'
                : messageKey.includes('revision') ? 'Returned for Revision'
                : messageKey.includes('assigned') ? 'Coordinator Assigned'
                : messageKey.includes('forward') ? 'Case Information Forwarded'
                : messageKey.includes('resolved') ? 'Case Resolved'
                : messageKey.includes('reopened') ? 'Case Reopened'
                : messageKey.includes('archiv') ? 'Case Record Updated'
                : messageKey.includes('report') ? 'Report Saved'
                : 'Case Updated';
            const detail = messageKey.includes('classification')
                ? <?= json_encode('Classification has been set to “' . (string) ($case['case_classification'] ?? '') . '”.', $jsonEncodeFlags) ?>
                : messageKey.includes('rejected') ? 'The complaint has been rejected successfully.'
                : messageKey.includes('revision') ? 'The complaint has been returned with the revision instructions.'
                : message;
            (window.DARISAlert?.toast('success', title, detail) || Swal.fire({ toast: true, position: 'top', icon: 'success', title, text: detail, timer: 4800, showConfirmButton: false, showCloseButton: true, backdrop: false }));
        }, { once: true });
        <?php elseif (!empty($errors)): ?>
        window.addEventListener('load', () => {
            const text = <?= json_encode(implode(' ', array_map('strval', $errors)), $jsonEncodeFlags) ?>;
            (window.DARISAlert?.toast('error', 'Unable to Update Case', text, { timer: 7000 }) || Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Unable to Update Case', text, timer: 7000, showConfirmButton: false, showCloseButton: true, backdrop: false }));
        }, { once: true });
        <?php endif; ?>

        const syncClassification = () => {
            const caseClassification = document.getElementById('caseClassification');
            const caseClassificationOther = document.getElementById('caseClassificationOther');
            if (!caseClassification || !caseClassificationOther) return;
            const isOthers = caseClassification.value === 'Others';
            caseClassificationOther.hidden = !isOthers;
            caseClassificationOther.disabled = caseClassification.disabled || !isOthers;
            caseClassificationOther.required = isOthers && !caseClassification.disabled;
        };

        document.addEventListener('change', event => {
            if (event.target.id === 'caseClassification') syncClassification();
        });
        document.addEventListener('daris:ajax-success', syncClassification);
        syncClassification();
    })();

    const statusSlugs = {
        'Under Investigation': 'under-investigation',
        'Returned for Revision': 'returned',
        'Rejected': 'rejected',
        'Rejected Complaint': 'rejected',
        'Resolved': 'resolved',
        'Reformation in Progress': 'reformation-in-progress',
        'Reformation Completed': 'reformation-completed',
        'Escalated': 'escalated',
        'Archived': 'archived'
    };
    const statusIcons = {
        'Under Investigation': 'bi-search',
        'Returned for Revision': 'bi-arrow-return-left',
        'Rejected': 'bi-x-circle',
        'Rejected Complaint': 'bi-x-circle',
        'Resolved': 'bi-check2-circle',
        'Reformation in Progress': 'bi-arrow-repeat',
        'Reformation Completed': 'bi-patch-check',
        'Escalated': 'bi-arrow-up-circle',
        'Archived': 'bi-archive'
    };
    let statusPollTimer = null;

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

    const getStatusNodes = () => ({
        banner: document.getElementById('caseStatusBanner'),
        pill: document.getElementById('caseStatusPill'),
        updated: document.getElementById('caseStatusUpdated')
    });

    const applyStatus = (newStatus, updatedAt) => {
        const nodes = getStatusNodes();
        if (!nodes.banner || !nodes.pill) return;
        const slug = statusSlugs[newStatus] || 'under-investigation';
        nodes.pill.textContent = newStatus;
        nodes.banner.dataset.status = slug;
        const icon = nodes.banner.querySelector('.status-banner-badge i');
        if (icon && statusIcons[newStatus]) icon.className = 'bi ' + statusIcons[newStatus];
        if (updatedAt && nodes.updated) {
            const formatted = formatDbTime(updatedAt);
            if (formatted) nodes.updated.textContent = 'Updated ' + formatted;
        }
    };

    const pollStatus = async () => {
        const nodes = getStatusNodes();
        if (!nodes.banner || !nodes.pill) return;
        const endpoint = nodes.banner.dataset.statusEndpoint;
        const caseId = nodes.banner.dataset.caseId;
        if (!endpoint || !caseId) return;
        try {
            const response = await fetch(endpoint + '?id=' + encodeURIComponent(caseId), { credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success) return;

            const newStatus = String(data.status || '').trim();
            if (newStatus && newStatus !== nodes.pill.textContent.trim()) {
                applyStatus(newStatus, data.updated_at);
                nodes.banner.classList.remove('flash');
                void nodes.banner.offsetWidth;
                nodes.banner.classList.add('flash');
            } else if (newStatus) {
                applyStatus(newStatus, data.updated_at);
            }
        } catch (error) {}
    };

    const initializeStatusPolling = () => {
        if (statusPollTimer) window.clearInterval(statusPollTimer);
        const nodes = getStatusNodes();
        if (!nodes.banner || !nodes.pill) return;
        statusPollTimer = window.setInterval(pollStatus, 5000);
    };

    document.addEventListener('daris:ajax-success', initializeStatusPolling);
    initializeStatusPolling();
    </script>
</body>

</html>
