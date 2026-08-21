<?php
require_once __DIR__ . '/../../controllers/CaseController.php';

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

$controller->clearFlash();

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

        .case-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 24px;
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

        .revision-form { border-top: 1px solid #dce7d9; margin-top: 16px; padding-top: 16px; }
        .revision-form h3 { color: #123c1b; font-size: 14px; margin: 0; }
        .revision-form p { color: #62705f; font-size: 12px; margin: 4px 0 10px; }
        .revision-fields { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .revision-field { align-items: center; background: #f8fbf7; border: 1px solid #dce7d9; border-radius: 8px; display: flex; font-size: 12px; gap: 8px; padding: 9px; }
        .revision-field input { height: 16px; margin: 0; width: 16px; }

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

        .btn:disabled,
        .revision-fieldset:disabled .btn {
            cursor: not-allowed;
            filter: grayscale(0.5);
            opacity: 0.45;
        }

        .action-banner {
            align-items: center;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            padding: 12px 14px;
        }

        .action-banner.hidden {
            display: none;
        }

        .action-banner.banner-resolve {
            animation: banner-slide 0.25s ease;
            background: #eaf7e8;
            border: 1px solid #1a9d00;
        }

        .action-banner.banner-archive {
            animation: banner-slide 0.25s ease;
            background: #fff8e6;
            border: 1px solid #d99a06;
        }

        .action-banner.banner-reopen {
            animation: banner-slide 0.25s ease;
            background: #e8f1fc;
            border: 1px solid #1c6dd0;
        }

        .action-banner-icon {
            font-size: 20px;
        }

        .banner-resolve .action-banner-icon {
            color: #157000;
        }

        .banner-archive .action-banner-icon {
            color: #b27400;
        }

        .banner-reopen .action-banner-icon {
            color: #155fae;
        }

        .action-banner-text {
            display: flex;
            flex-direction: column;
            flex: 1;
            font-size: 13px;
        }

        .action-banner-text strong {
            color: #172017;
            font-size: 14px;
        }

        .action-banner-text span {
            color: #5c6a59;
            font-size: 12px;
        }

        .action-banner-buttons {
            display: flex;
            gap: 8px;
        }

        .banner-cancel,
        .banner-confirm {
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 12px;
        }

        .banner-cancel {
            background: #fff;
            border: 1px solid #c9d4c6;
            color: #3f4c3e;
        }

        .banner-confirm {
            background: #123c1b;
            color: #fff;
        }

        .banner-archive .banner-confirm {
            background: #8a5c00;
        }

        .banner-reopen .banner-confirm {
            background: #155fae;
        }

        .banner-cancel:hover {
            background: #f2f5f0;
        }

        .banner-confirm:hover {
            filter: brightness(1.15);
        }

        @keyframes banner-slide {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
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
    <link rel="stylesheet" href="../layout/system.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-page app-content">
            <?php $pageTitle = $case['case_number']; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="case-wrap">
            <div style="margin-bottom:14px"><a class="btn btn-secondary" href="<?= h(app_route('cases.index')) ?>"><i class="bi bi-arrow-left"></i> Back to Case Management</a></div>
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
                                <div class="value"><?= h(trim(($case['complainant_course'] ?? '') . ' ' . ($case['complainant_year_level'] ?? '') . ' ' . ($case['complainant_section'] ?? '')) ?: $case['complainant_course_year']) ?></div>
                            </div>
                            <?php elseif (($case['complainant_type'] ?? '') === 'Employee'): ?>
                            <div class="detail"><div class="label">Employee Number</div><div class="value"><?= h($case['complainant_employee_no']) ?></div></div>
                            <div class="detail"><div class="label">College / Office / Department</div><div class="value"><?= h($case['complainant_department']) ?></div></div>
                            <div class="detail"><div class="label">Position</div><div class="value"><?= h($case['complainant_position']) ?></div></div>
                            <?php elseif (($case['complainant_type'] ?? '') === 'Private Individual' && !empty($case['complainant_relationship'])): ?>
                            <div class="detail"><div class="label">Relationship to CLSU</div><div class="value"><?= h($case['complainant_relationship']) ?></div></div>
                            <?php elseif (($case['complainant_type'] ?? '') === 'Others'): ?>
                            <?php if (!empty($case['complainant_affiliation'])): ?><div class="detail"><div class="label">Affiliation / Organization</div><div class="value"><?= h($case['complainant_affiliation']) ?></div></div><?php endif; ?>
                            <?php if (!empty($case['complainant_purpose'])): ?><div class="detail"><div class="label">Relationship or Purpose</div><div class="value"><?= h($case['complainant_purpose']) ?></div></div><?php endif; ?>
                            <?php endif; ?>
                            <div class="detail">
                                <div class="label">Incident Date</div>
                                <div class="value"><?= h(date('M d, Y h:i A', strtotime($case['incident_datetime']))) ?></div>
                            </div>
                            <div class="detail">
                                <div class="label">Location</div>
                                <div class="value"><?= h($case['incident_location']) ?></div>
                            </div>
                            <div class="detail">
                                <div class="label">Date Submitted</div>
                                <div class="value"><?= h(date('M d, Y h:i A', strtotime($case['submitted_at']))) ?></div>
                            </div>
                            <div class="detail">
                                <div class="label">Coordinator</div>
                                <div class="value"><?= h(person_name($case['coordinator_first_name'], $case['coordinator_last_name'])) ?></div>
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
                                <div class="list-item">
                                    <strong><?= h($respondent['full_name']) ?></strong>
                                    <div class="muted"><?= h($respondent['student_no']) ?> <?= h($respondent['college']) ?> <?= h($respondent['course_year']) ?></div>
                                    <div class="value"><?= h($respondent['contact_info']) ?></div>
                                    <div class="value"><?= h($respondent['details']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <h2>Witnesses</h2>
                        <div class="list">
                            <?php foreach ($witnesses as $witness): ?>
                                <div class="list-item">
                                    <strong><?= h($witness['full_name']) ?></strong>
                                    <div class="muted"><?= h($witness['student_no']) ?> <?= h($witness['contact_info']) ?></div>
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
                                    <div class="muted"><?= h($file['mime_type']) ?> | <?= h(number_format($file['file_size'] / 1024, 1)) ?> KB | <?= h(date('M d, Y', strtotime($file['uploaded_at']))) ?></div>
                                    <div class="button-row"><a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=view">View</a><a class="btn btn-secondary" href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=download">Download</a></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <h2>Conversation<?php if ($messageReceiver): ?> — <?= h(trim(($messageReceiver['first_name'] ?? '') . ' ' . ($messageReceiver['last_name'] ?? ''))) ?><?php endif; ?></h2>
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

                        <form class="message-composer" method="POST" action="../messages/send.php" data-no-ajax="true">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="complaint_id" value="<?= (int) $case['complaint_id'] ?>">
                            <textarea name="message" placeholder="Write a message" required></textarea>
                            <div class="message-send-row">
                                <input type="hidden" name="receiver_account_id" value="<?= (int) ($messageReceiver['account_id'] ?? 0) ?>">
                                <?php if ($messageReceiver): ?>
                                    <span class="muted message-receiver-note">Sending to <?= h(trim(($messageReceiver['first_name'] ?? '') . ' ' . ($messageReceiver['last_name'] ?? ''))) ?> (<?= h($messageReceiver['role'] ?? '') ?>)</span>
                                <?php endif; ?>
                                <button class="btn btn-assign" type="submit">Send</button>
                            </div>
                        </form>
                    </section>when
                </div>

                <aside>
                    <?php $caseStatus = $case['status'] ?? ''; $caseLocked = in_array($caseStatus, ['Resolved', 'Archived'], true); ?>
                    <section class="panel">
                        <h2>Staff Actions</h2>
                        <?php if ($caseLocked): ?><p class="muted" style="margin-bottom:10px"><i class="bi bi-lock-fill"></i> This case is closed. Only archiving remains available.</p><?php endif; ?>
                        <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                            <?= Security::csrfField() ?>
                            <textarea name="remarks" placeholder="Remarks"></textarea>
                            <div class="action-banner hidden" id="actionConfirmBanner" role="alert">
                                <i class="bi bi-exclamation-circle-fill action-banner-icon"></i>
                                <div class="action-banner-text">
                                    <strong id="actionBannerTitle"></strong>
                                    <span>This action will be recorded in the case timeline.</span>
                                </div>
                                <div class="action-banner-buttons">
                                    <button type="button" class="banner-cancel" id="actionBannerCancel">Cancel</button>
                                    <button type="button" class="banner-confirm" id="actionBannerConfirm">Yes, continue</button>
                                </div>
                            </div>
                            <div class="button-row">
                                <button class="btn btn-verify" type="submit" name="case_action" value="verify" <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?>>Verify</button>
                                <button class="btn btn-reject" type="submit" name="case_action" value="reject" <?= $caseLocked ? 'disabled title="This case is closed."' : '' ?> data-confirm="Reject this complaint? This action changes its workflow status.">Reject</button>
                                <button class="btn btn-resolve" type="submit" name="case_action" value="resolve" <?= ($caseStatus === 'Verified') ? 'data-confirm-banner="Mark this case as resolved?"' : ($caseLocked ? 'disabled title="This case is closed."' : 'disabled title="Available once the case is Verified."') ?>>Resolve</button>
                                <button class="btn btn-archive" type="submit" name="case_action" value="archive" <?= ($caseStatus === 'Resolved') ? 'data-confirm-banner="Archive this case?"' : 'disabled title="Available once the case is Resolved."' ?>>Archive</button>
                                <button class="btn btn-reopen" type="submit" name="case_action" value="reopen" <?= ($caseStatus === 'Resolved') ? 'data-confirm-banner="Open this case again? Its status will return to Verified."' : 'disabled title="Available once the case is Resolved."' ?>>Open this case again</button>
                            </div>
                        </form>

                        <fieldset class="revision-fieldset" <?= $caseLocked ? 'disabled' : '' ?>>
                        <form class="action-form revision-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                            <?= Security::csrfField() ?>
                            <h3>Return for Revision</h3>
                            <p>Explain the correction and select every section the student may update.</p>
                            <textarea name="remarks" placeholder="Revision reason and instructions" required></textarea>
                            <div class="revision-fields">
                                <?php foreach ([
                                    'complaint_title' => 'Complaint Title', 'complaint_details' => 'Complaint Description',
                                    'incident_date' => 'Incident Date', 'incident_time' => 'Incident Time',
                                    'incident_location' => 'Incident Location', 'respondents' => 'Respondent Information',
                                    'witnesses' => 'Witness Information', 'evidence' => 'Supporting Evidence',
                                ] as $field => $label): ?>
                                    <label class="revision-field"><input type="checkbox" name="revision_fields[]" value="<?= h($field) ?>"> <span><?= h($label) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-return" type="submit" name="case_action" value="return" data-confirm="Return this complaint with the selected revision requirements?">Return for Revision</button>
                        </form>
                        </fieldset>

                        <fieldset class="revision-fieldset" <?= $caseLocked ? 'disabled' : '' ?>>
                        <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                            <?= Security::csrfField() ?>
                            <select name="coordinator_account_id" required>
                                <option value="">Select coordinator</option>
                                <?php foreach ($coordinators as $coordinator): ?>
                                    <option value="<?= (int) $coordinator['account_id'] ?>" <?= ((int) $case['assigned_coordinator_account_id'] === (int) $coordinator['account_id']) ? 'selected' : '' ?>>
                                        <?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?> (<?= h($coordinator['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="remarks" placeholder="Assignment remarks"></textarea>
                            <button class="btn btn-assign" type="submit" name="case_action" value="assign">Assign Coordinator</button>
                        </form>
                        </fieldset>
                    </section>

                    <section class="panel">
                        <h2>Timeline</h2>
                        <?php if (empty($history)): ?>
                            <p class="muted">No case history yet.</p>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <div class="timeline-item">
                                    <strong><?= h($item['action']) ?></strong>
                                    <div class="muted"><?= h(date('M d, Y h:i A', strtotime($item['created_at']))) ?></div>
                                    <div class="value">
                                        <?= h($item['previous_status']) ?><?= $item['new_status'] ? ' to ' . h($item['new_status']) : '' ?>
                                    </div>
                                    <?php if (!empty($item['assigned_coordinator_account_id'])): ?>
                                        <div class="value">Coordinator: <?= h(person_name($item['coordinator_first_name'], $item['coordinator_last_name'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['remarks'])): ?>
                                        <div class="value"><?= nl2br(h($item['remarks'])) ?></div>
                                    <?php endif; ?>
                                    <div class="muted">By <?= h(person_name($item['actor_first_name'], $item['actor_last_name'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                </aside>
            </div>
        </main>
    </div>
    </div>
    <script>
        const caseConversation = document.getElementById('caseConversation');

        if (caseConversation) {
            caseConversation.scrollTop = caseConversation.scrollHeight;
        }

        (() => {
            const banner = document.getElementById('actionConfirmBanner');
            const bannerTitle = document.getElementById('actionBannerTitle');
            const confirmButton = document.getElementById('actionBannerConfirm');
            const cancelButton = document.getElementById('actionBannerCancel');
            let pendingAction = null;

            if (!banner) return;

            const hideBanner = () => {
                pendingAction = null;
                banner.classList.add('hidden');
            };

            document.querySelectorAll('[data-confirm-banner]').forEach(button => {
                button.addEventListener('click', event => {
                    event.preventDefault();
                    pendingAction = button;
                    bannerTitle.textContent = button.dataset.confirmBanner;
                    const variants = ['banner-resolve', 'banner-archive', 'banner-reopen'];
                    banner.classList.remove('hidden', ...variants);
                    banner.classList.add(`banner-${button.value}`);
                    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });

            confirmButton.addEventListener('click', () => {
                if (!pendingAction) return;
                const button = pendingAction;
                hideBanner();
                button.form.requestSubmit(button);
            });

            cancelButton.addEventListener('click', hideBanner);
        })();
    </script>
</body>

</html>
