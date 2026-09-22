<?php
require_once __DIR__ . '/../../controllers/RespondentController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new RespondentController();
$complaintId = (int) ($_GET['id'] ?? 0);
$viewData = $controller->caseShow($complaintId);

$user = $viewData['user'];
$case = $viewData['case'];
$link = $viewData['link'];
$visibility = $viewData['visibility'];
$statement = $viewData['statement'];
$attachments = $viewData['attachments'];
$counterErrors = $viewData['counterErrors'];
$counterInfo = $viewData['counterInfo'];
$hearings = $viewData['hearings'];
$timeline = $viewData['timeline'];
$caseActive = $viewData['caseActive'];
$statementDraft = $statement && ($statement['status'] ?? '') === 'Draft';

$controller->clearFlash();

function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function status_class($status) { return strtolower(str_replace(' ', '-', $status)); }

$pageTitle = 'Case Details';
$caseStatus = $case['status'] ?? '';
$isStudentAccount = strtolower((string) str_replace(['_', ' '], '-', (string) ($user['role'] ?? ''))) === 'student';
$backHref = $isStudentAccount ? '../complaints/my_cases.php' : 'cases.php';

$stageLabels = [
    'Complaint Submitted' => 'Complaint Submitted',
    'Under Investigation' => 'Investigation Started',
    'Returned for Revision' => 'Counter-Statement Returned for Revision',
    'Rejected Complaint' => 'Case Rejected',
    'Resolved Case' => 'Case Resolved',
    'Escalated Case' => 'Case Escalated',
    'Archived Case' => 'Case Archived',
    'Unarchived Case' => 'Case Reopened',
    'Reformation in Progress' => 'Reformation in Progress',
    'Reformation Completed' => 'Reformation Completed',
    'Counter-Statement Submitted' => 'Counter-Statement Submitted',
    'Counter-Statement Updated' => 'Counter-Statement Updated',
    'Respondent Account Activated' => 'Respondent Invitation Activated',
    'Forwarded to Respondent' => 'Case Forwarded to You',
    'Coordinator Assigned' => 'Case Assigned to Coordinator',
    'Hearing Scheduled' => 'Hearing Scheduled',
    'Hearing Cancelled' => 'Hearing Cancelled',
    'Hearing Completed' => 'Hearing Completed',
];

$stageLabel = fn($action) => $stageLabels[$action] ?? $action;

$incidentDatetime = !empty($case['incident_datetime']) ? strtotime((string) $case['incident_datetime']) : null;
$finalStatuses = ['Resolved', 'Reformation Completed', 'Archived', 'Rejected', 'Escalated'];
$statementStatus = $statement ? ($statement['status'] ?? '') : '';
$csRequired = !in_array($caseStatus, $finalStatuses, true) && $statementStatus !== 'Submitted';
$hasFinalInfo = in_array($caseStatus, $finalStatuses, true)
    || !empty($case['outcome'])
    || !empty($case['action_taken'])
    || !empty($case['resolution_date'])
    || !empty($case['remarks_notes']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($case['case_number'] ?? 'Case Details') ?> | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/system.css?v=5">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { align-items: stretch; display: block; justify-content: flex-start; padding: 0; }
        .case-wrap { max-width: 100%; margin: 0 auto; padding: 24px; }
        .case-head { align-items: center; background: #123c1b; border-radius: 10px; color: #fff; display: flex; flex-wrap: wrap; gap: 14px; justify-content: space-between; padding: 18px 22px; }
        .case-head h1 { font-size: 22px; margin: 0; }
        .case-head .meta { color: #cfe6ca; font-size: 13px; margin-top: 3px; }
        .panel { background: #fff; border: 1px solid #dce5da; border-radius: 8px; box-shadow: 0 4px 14px rgba(18,60,27,.06); margin-top: 18px; padding: 18px 20px; }
        .panel h2 { align-items: center; border-bottom: 1px solid #edf3ec; color: #123c1b; display: flex; font-size: 15px; gap: 8px; margin: 0 0 14px; padding-bottom: 10px; }
        .details-grid { display: grid; gap: 10px 18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .label { color: #637060; font-size: 11px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; }
        .value { color: #263225; font-size: 14px; margin-top: 2px; }
        .full { grid-column: 1 / -1; }
        .status-pill { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 800; padding: 6px 9px; white-space: nowrap; }
        .status-under-investigation { background: #e7f0ff; color: #275ca8; }
        .status-returned-for-revision { background: #fff5d8; color: #825e00; }
        .status-resolved { background: #e5f6e3; color: #157000; }
        .status-reformation-in-progress { background: #fff4d6; color: #8a5a00; }
        .status-reformation-completed { background: #e1f6ef; color: #087f5b; }
        .status-escalated { background: #fdeee3; color: #c2410c; }
        .status-rejected { background: #fff0ef; color: #a92c23; }
        .status-archived { background: #edf0ed; color: #59635a; }
        .status-counter-statement-required { background: #fff4d6; color: #8a5a00; }
        .cs-required-banner { align-items: center; background: #fffdf5; border: 1px solid #ead9a5; border-left: 4px solid #b57600; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px 16px; justify-content: space-between; margin-top: 16px; padding: 14px 16px; }
        .cs-required-banner p { color: #6a614c; font-size: 13px; margin: 6px 0 0; }
        .statement-box { background: #f8fbf7; border: 1px solid #dce7d9; border-radius: 8px; padding: 14px 16px; }
        .next-action { align-items:center; background:#f4faf2; border:1px solid #cfe2cb; border-radius:8px; display:flex; gap:12px; justify-content:space-between; margin:16px 0; padding:12px 14px; }
        .next-action p { color:#3f4c3e; font-size:13px; margin:3px 0 0; }
        .statement-evidence { border-top:1px solid #e6ede4; margin-top:16px; padding-top:14px; }
        .statement-evidence h3 { color:#284127; font-size:13px; margin:0 0 8px; }
        .statement-locked { background: #f6f8f5; border: 1px solid #e2eae0; border-radius: 8px; color: #3f4c3e; padding: 14px 16px; }
        .statement-locked .statement-text { white-space: pre-wrap; }
        .action-form { display: flex; flex-direction: column; gap: 10px; margin-top: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { color: #5c6a59; font-size: 12px; }
        textarea { min-height: 170px; resize: vertical; }
        .hearing-item { border-left: 4px solid #1a9d00; padding: 4px 12px; margin-bottom: 10px; }
        .hearing-item.cancelled { border-left-color: #c0392b; }
        .hearing-item.completed { border-left-color: #59635a; }
        .timeline-item { border-left: 2px solid #cfe0cb; margin-left: 8px; padding: 0 0 14px 18px; position: relative; }
        .timeline-item::before { background: #1a9d00; border-radius: 50%; content: ""; height: 10px; left: -6px; position: absolute; top: 4px; width: 10px; }
        .timeline-action { color: #172017; font-size: 13px; font-weight: 700; }
        .timeline-time { color: #84917f; font-size: 11px; }
        .attach-row { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
        .btn-remove { background: #fff0ef; color: #a92c23; }
        .btn-remove:hover { background: #fde3e0; }
        .notice { background: #fffdf5; border: 1px solid #ead9a5; border-left: 4px solid #b57600; border-radius: 8px; color: #6a614c; font-size: 13px; padding: 12px 14px; }
        .statement-confirmed { align-items: flex-start; background: #eaf7e8; border: 1px solid #bfe3ba; border-left: 4px solid #1a9d00; border-radius: 8px; display: flex; gap: 10px; margin-bottom: 14px; padding: 12px 14px; }
        .statement-confirmed i { color: #157000; font-size: 18px; margin-top: 1px; }
    </style>
</head>
<body>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="app-content">
        <?php require __DIR__ . '/../layout/topbar.php'; ?>
        <main class="case-wrap case-detail-view">
            <a class="text-button" href="<?= h($backHref) ?>" style="display:inline-block;margin-bottom:12px"><i class="bi bi-arrow-left"></i> Back to Complaint Cases</a>

            <header class="case-head">
                <div>
                    <h1><?= h($case['case_number']) ?></h1>
                    <div class="meta">Case forwarded to you</div>
                </div>
                <span class="status-pill status-<?= h(status_class($caseStatus)) ?>"><?= h($caseStatus) ?></span>
            </header>

            <nav class="case-detail-nav" aria-label="Case details sections">
                <a href="#case-overview">Overview</a>
                <?php if ($visibility['complaint_details']): ?><a href="#complaint-details">Complaint</a><?php endif; ?>
                <a href="#counter-statement">My Statement</a>
                <a href="#case-progress">Progress</a>
                <?php if ($visibility['hearings']): ?><a href="#hearing">Hearing</a><?php endif; ?>
                <?php if ($visibility['final_information']): ?><a href="#final-information">Final Information</a><?php endif; ?>
            </nav>

            <?php if ($csRequired): ?>
            <section class="next-action" aria-label="Counter-statement required">
                <div>
                    <span class="status-pill status-counter-statement-required"><i class="bi bi-pencil-square"></i> Counter-Statement Required</span>
                    <p>Review the complaint, then provide your response when ready.</p>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($counterErrors)): foreach ($counterErrors as $counterError): ?>
                <div class="alert alert-danger" role="alert" style="margin-top:16px"><?= h($counterError) ?></div>
            <?php endforeach; endif; ?>
            <?php if (!empty($counterInfo)): ?>
                <div class="alert alert-success" role="status" style="margin-top:16px"><?= h(is_array($counterInfo) ? implode(' ', $counterInfo) : $counterInfo) ?></div>
            <?php endif; ?>

            <section class="panel" id="case-overview">
                <h2><i class="bi bi-folder2-open"></i> Case Overview</h2>
                <div class="details-grid">
                    <div><div class="label">Case Number</div><div class="value"><strong><?= h($case['case_number']) ?></strong></div></div>
                    <div><div class="label">Case Status</div><div class="value"><?= h($caseStatus) ?></div></div>
                    <div><div class="label">Classification</div><div class="value"><?= h($case['case_classification']) ?></div></div>
                    <div><div class="label">Date Filed</div><div class="value"><?= h(date('M d, Y', strtotime($case['submitted_at']))) ?></div></div>
                    <?php if ($visibility['incident']): ?>
                    <?php if ($incidentDatetime): ?>
                    <div><div class="label">Incident Date</div><div class="value"><?= h(date('M d, Y', $incidentDatetime)) ?></div></div>
                    <div><div class="label">Incident Time</div><div class="value"><?= h(date('h:i A', $incidentDatetime)) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($case['incident_location'])): ?>
                    <div><div class="label">Incident Location</div><div class="value"><?= h($case['incident_location']) ?></div></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($visibility['complaint_details']): ?>
            <section class="panel" id="complaint-details">
                <h2><i class="bi bi-chat-left-text"></i> Original Complaint / Statement</h2>
                <?php if (!empty($case['complaint_details'])): ?>
                    <div class="value" style="white-space:pre-wrap"><?= nl2br(h($case['complaint_details'])) ?></div>
                <?php else: ?>
                    <p class="muted">No complaint narrative was recorded for this case.</p>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <section class="panel" id="counter-statement">
                <h2><i class="bi bi-pencil-square"></i> My Counter-Statement</h2>
                <?php if ($caseActive && $statementDraft): ?>
                    <p class="muted" style="font-size:12px;margin:0 0 8px">Your statement is saved as a draft until you submit it to the SDRU.</p>
                    <form class="action-form" method="POST" action="case_show.php?id=<?= (int) $complaintId ?>" data-sicms-validate>
                        <?= Security::csrfField() ?>
                        <div class="form-group">
                            <label for="statement_content">Your Counter-Statement</label>
                            <textarea id="statement_content" name="statement_content" required placeholder="State your response to the complaint..."><?= h($statement['content'] ?? '') ?></textarea>
                        </div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <button class="btn btn-secondary" type="submit" name="case_action" value="save_draft" data-sicms-processing-label="Saving draft..."><i class="bi bi-save"></i> Save Draft</button>
                            <button class="btn btn-primary" type="submit" name="case_action" value="submit_counter_statement" data-sicms-processing-label="Submitting statement..." data-swal-confirm="Submit this counter-statement to the SDRU? You will no longer be able to edit it unless the SDRU returns it for revision."><i class="bi bi-send"></i> Submit Counter-Statement</button>
                        </div>
                    </form>

                    <div class="statement-evidence" id="evidence">
                    <h3><i class="bi bi-paperclip"></i> Supporting Evidence for This Statement</h3>
                    <?php if (!empty($attachments)): ?>
                        <?php foreach ($attachments as $attachment): ?>
                        <div class="attach-row">
                            <a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $attachment['evidence_id'] ?>&amp;mode=view"><i class="bi bi-eye"></i> View</a>
                            <a class="btn btn-secondary" href="../complaints/attachment.php?id=<?= (int) $attachment['evidence_id'] ?>&amp;mode=download"><i class="bi bi-download"></i> Download</a>
                            <span class="muted" style="font-size:13px"><?= h($attachment['original_filename']) ?> (<?= h(number_format($attachment['file_size'] / 1024, 1)) ?> KB)</span>
                            <form method="POST" action="case_show.php?id=<?= (int) $complaintId ?>" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="case_action" value="remove_counter_evidence">
                                <input type="hidden" name="evidence_id" value="<?= (int) $attachment['evidence_id'] ?>">
                                <button class="btn btn-remove" type="submit" data-swal-confirm="Remove this supporting file?"><i class="bi bi-trash"></i> Remove</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <form class="action-form" method="POST" action="case_show.php?id=<?= (int) $complaintId ?>" enctype="multipart/form-data" data-sicms-validate>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="case_action" value="upload_counter_evidence">
                        <div class="form-group">
                            <label for="counter_evidence">Attach Supporting Evidence (pdf, jpg, jpeg, png, docx &middot; max 5MB each)</label>
                            <input type="file" id="counter_evidence" name="counter_evidence[]" multiple accept=".pdf,.jpg,.jpeg,.png,.docx" data-sicms-size-mb="5" data-sicms-accept-ext=".pdf,.jpg,.jpeg,.png,.docx">
                        </div>
                        <div><button class="btn btn-remove" type="submit" data-sicms-processing-label="Uploading files..." style="background:#eef1ee;color:#3f4c3e"><i class="bi bi-paperclip"></i> Attach Files</button></div>
                    </form>
                    </div>
                <?php elseif ($statement): ?>
                    <?php if ($statement['status'] === 'Submitted'): ?>
                    <div class="statement-confirmed">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        <div>
                            <strong>Counter-Statement Submitted</strong>
                            <div>Your counter-statement has been submitted for review.</div>
                            <div class="muted" style="font-size:12px;margin-top:4px">Case: <?= h($case['case_number']) ?> &middot; Submitted: <?= h(date('M d, Y h:i A', strtotime($statement['submitted_at']))) ?> &middot; Current Status: <?= h($caseStatus) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="statement-locked">
                        <p class="muted" style="margin:0 0 8px;font-size:12px">
                            <?php if ($statement['status'] === 'Submitted'): ?>
                                <i class="bi bi-lock-fill"></i> Submitted on <?= h(date('M d, Y h:i A', strtotime($statement['submitted_at']))) ?> — this statement is final unless the SDRU requests a revision.
                            <?php else: ?>
                                <i class="bi bi-lock-fill"></i> This case is closed and can no longer be edited.
                            <?php endif; ?>
                        </p>
                        <div class="statement-text"><?= nl2br(h($statement['content'] ?? '')) ?></div>
                    </div>
                <?php else: ?>
                    <p class="muted">No counter-statement recorded for this case.</p>
                <?php endif; ?>
            </section>

            <section class="panel" id="case-progress">
                <h2><i class="bi bi-clock-history"></i> Case Progress</h2>
                <?php if (empty($timeline)): ?>
                    <p class="muted">No case progress has been recorded yet.</p>
                <?php else: foreach ($timeline as $event): ?>
                    <div class="timeline-item">
                        <div class="timeline-action"><?= h($stageLabel($event['action'])) ?></div>
                        <div class="timeline-time"><?= h(date('M d, Y h:i A', strtotime($event['created_at']))) ?><?= !empty($event['is_own_action']) ? ' · You' : '' ?><?= !empty($event['new_status']) && $event['new_status'] !== $event['action'] ? ' · Status: ' . h($event['new_status']) : '' ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </section>

            <?php if ($visibility['hearings']): ?>
            <section class="panel" id="hearing">
                <h2><i class="bi bi-calendar-event"></i> Hearing</h2>
                <?php if (empty($hearings)): ?>
                    <p class="muted">No hearings have been scheduled for this case.</p>
                <?php else: foreach ($hearings as $hearing): ?>
                    <div class="hearing-item <?= h(strtolower((string) $hearing['status'])) ?>">
                        <div class="value"><strong><i class="bi bi-clock"></i> <?= h(date('M d, Y - h:i A', strtotime($hearing['hearing_datetime']))) ?></strong></div>
                        <div class="muted" style="font-size:13px">Status: <?= h($hearing['status']) ?></div>
                        <?php if (!empty($hearing['venue'])): ?>
                            <div class="muted" style="font-size:13px">Venue: <?= h($hearing['venue']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($hearing['google_meet_link'])): ?>
                            <div style="margin-top:4px"><a href="<?= h($hearing['google_meet_link']) ?>" target="_blank" rel="noopener"><i class="bi bi-camera-video"></i> Join Google Meet</a></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </section>
            <?php endif; ?>

            <?php if ($visibility['final_information']): ?>
            <section class="panel" id="final-information">
                <h2><i class="bi bi-clipboard-check"></i> Final Information</h2>
                <?php if (!$hasFinalInfo): ?>
                    <p class="muted">No final outcome has been recorded for this case yet.</p>
                <?php else: ?>
                    <div class="details-grid">
                        <?php if (!empty($case['resolution_date'])): ?>
                        <div><div class="label">Resolution Date</div><div class="value"><?= h(date('M d, Y', strtotime($case['resolution_date']))) ?></div></div>
                        <?php endif; ?>
                        <?php if (in_array($caseStatus, $finalStatuses, true)): ?>
                        <div><div class="label">Final Status</div><div class="value"><?= h($caseStatus) ?></div></div>
                        <?php endif; ?>
                        <?php if (!empty($case['outcome'])): ?>
                        <div class="full"><div class="label">Outcome</div><div class="value" style="white-space:pre-wrap"><?= nl2br(h($case['outcome'])) ?></div></div>
                        <?php endif; ?>
                        <?php if (!empty($case['action_taken'])): ?>
                        <div class="full"><div class="label">Action Taken</div><div class="value" style="white-space:pre-wrap"><?= nl2br(h($case['action_taken'])) ?></div></div>
                        <?php endif; ?>
                        <?php if (!empty($case['remarks_notes'])): ?>
                        <div class="full"><div class="label">Remarks</div><div class="value" style="white-space:pre-wrap"><?= nl2br(h($case['remarks_notes'])) ?></div></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </main>
    </div>
</div>
<script>
(() => {
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-swal-confirm]');
        if (!button || !window.Swal) return;
        event.preventDefault();
        Swal.fire({ icon: 'question', title: button.dataset.swalConfirm, showCancelButton: true, confirmButtonText: 'Continue', cancelButtonText: 'Cancel', reverseButtons: true })
            .then(result => { if (result.isConfirmed) button.form?.requestSubmit(button); });
    });

    <?php $counterInfoText = is_array($counterInfo) ? implode(' ', $counterInfo) : (string) $counterInfo; ?>
    <?php if (strpos($counterInfoText, 'Counter-Statement Submitted') !== false): ?>
    if (window.Swal) {
        Swal.fire({ icon: 'success', title: 'Counter-statement submitted', text: 'Your response has been added to the case successfully.', confirmButtonText: 'OK' });
    }
    <?php endif; ?>
})();
</script>
<script src="../layout/system.js?v=20260922b" defer></script>
</body>
</html>
