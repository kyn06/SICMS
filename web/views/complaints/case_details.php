<?php
require_once __DIR__ . '/../../controllers/ComplaintController.php';
$controller = new ComplaintController();
$viewData = $controller->handleStudentCaseDetails((int) ($_GET['id'] ?? 0));
$user = $viewData['user'];
$case = $viewData['case'];
$evidence = $viewData['evidence'];
$history = $viewData['history'];
$hearings = $viewData['hearings'];
$counterStatement = $viewData['counterStatement'] ?? null;
$complaintResponse = $viewData['complaintResponse'] ?? null;
$complaintResponseClosed = $viewData['complaintResponseClosed'] ?? false;
$complaintResponseError = $viewData['complaintResponseError'] ?? null;
$complaintResponseInfo = $viewData['complaintResponseInfo'] ?? null;

function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function progress_steps($status) {
    if ($status === 'Reformation in Progress' || $status === 'Reformation Completed') return ['Under Investigation', 'Resolved', $status];
    if ($status === 'Returned for Revision') return ['Under Investigation', 'Returned for Revision'];
    if ($status === 'Rejected') return ['Under Investigation', 'Rejected'];
    if ($status === 'Escalated') return ['Under Investigation', 'Escalated'];
    return ['Under Investigation', 'Resolved'];
}
function stage_done($status, $stage) {
    if (in_array($status, ['Reformation in Progress', 'Reformation Completed'], true)) {
        if ($stage === 'Reformation in Progress') return true;
        if ($stage === 'Reformation Completed') return $status === 'Reformation Completed';
        return in_array($stage, ['Under Investigation', 'Resolved'], true);
    }
    $order = ['Under Investigation' => 0, 'Resolved' => 1, 'Archived' => 2];
    if (in_array($status, ['Returned for Revision', 'Rejected', 'Escalated'], true)) return $stage === 'Under Investigation' || $stage === $status;
    return isset($order[$stage], $order[$status]) && $order[$stage] <= $order[$status];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/system.css?v=5">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { align-items: stretch; display: block; justify-content: flex-start; padding: 0; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 24px; }
        .case-heading { align-items: center; display: flex; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .case-heading h1 { color: #172017; font-size: 23px; margin: 0 0 4px; }
        .case-heading p { color: #657164; font-size: 13px; margin: 0; }
        .status-pill { background: #eaf5e8; border-radius: 999px; color: #176f22; font-size: 12px; font-weight: 800; padding: 7px 10px; }
        .detail-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1.25fr) minmax(290px, .75fr); }
        .panel { background: #fff; border: 1px solid #dce5da; border-radius: 8px; box-shadow: 0 4px 14px rgba(18,60,27,.06); margin-bottom: 16px; padding: 18px; }
        .panel h2 { color: #19311c; font-size: 16px; margin: 0 0 14px; }
        .info-grid { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .label { color: #657164; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .value { color: #253024; font-size: 14px; line-height: 1.55; margin-top: 4px; overflow-wrap: anywhere; }
        .description { border-top: 1px solid #e5ebe3; margin-top: 16px; padding-top: 16px; }
        .timeline, .mini-list { display: grid; gap: 10px; }
        .timeline-step { align-items: center; display: grid; gap: 10px; grid-template-columns: 30px minmax(0, 1fr); padding: 8px; }
        .timeline-step.current { background: #f0f8ee; border: 1px solid #b8d8b3; border-radius: 8px; }
        .timeline-dot { align-items: center; background: #edf1ec; border-radius: 50%; color: #728071; display: flex; height: 30px; justify-content: center; width: 30px; }
        .timeline-step.done .timeline-dot { background: #1a8c2b; color: #fff; }
        .timeline-title { color: #273526; font-size: 13px; font-weight: 800; }
        .timeline-note { color: #6a7768; font-size: 11px; margin-top: 2px; }
        .mini-item { background: #f9fbf8; border: 1px solid #e0e8de; border-radius: 8px; padding: 12px; }
        .remarks, .hearing-item { border-left: 4px solid #1a8c2b; }
        .empty-state { color: #6a7768; font-size: 13px; padding: 16px; text-align: center; }
        .back-row { margin-top: 4px; }
        .next-action { background:#f4faf2; border:1px solid #cfe2cb; border-radius:8px; margin:0 0 16px; padding:12px 14px; }
        .next-action strong { color:#19311c; display:block; font-size:13px; }
        .next-action p { color:#5d6b5b; font-size:13px; margin:3px 0 10px; }
        .statement-flow { border-left:3px solid #1a8c2b; padding-left:14px; }
        @media (max-width: 820px) { .detail-grid, .info-grid { grid-template-columns: 1fr; } .case-heading { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="app-content">
        <?php $pageTitle = 'Case Details'; require __DIR__ . '/../layout/topbar.php'; ?>
        <main class="wrap case-detail-view">
            <div class="back-row" style="margin-bottom:14px"><a class="btn btn-secondary" href="my_cases.php"><i class="bi bi-arrow-left"></i> Back to My Complaints</a></div>
            <header class="case-heading"><div><h1><?= h($case['complaint_title'] ?? $case['case_classification']) ?></h1><p><?= h($case['case_number']) ?></p></div><span class="status-pill"><?= h($case['status']) ?></span></header>
            <nav class="case-detail-nav" aria-label="Case details sections">
                <a href="#case-overview">Overview</a>
                <?php if ($counterStatement): ?><a href="#respondent-counter-statement">Statement Exchange</a><?php endif; ?>
                <a href="#hearing-schedule">Hearings</a><a href="#case-progress">Progress</a><a href="#case-evidence">Evidence</a>
            </nav>
            <section class="detail-grid">
                <div>
                    <section class="panel" id="case-overview">
                        <h2>Case Overview</h2>
                        <div class="info-grid">
                            <div><div class="label">Case Number</div><div class="value"><?= h($case['case_number']) ?></div></div>
                            <?php if (!empty($case['complaint_title']) && $case['complaint_title'] !== $case['case_classification']): ?>
                                <div><div class="label">Complaint Title</div><div class="value"><?= h($case['complaint_title']) ?></div></div>
                            <?php endif; ?>
                            <div><div class="label">Classification</div><div class="value"><?= h($case['case_classification']) ?></div></div>
                            <div><div class="label">Date Submitted</div><div class="value"><?= h(date('M d, Y', strtotime($case['submitted_at']))) ?></div></div>
                            <div><div class="label">Current Status</div><div class="value"><?= h($case['status']) ?></div></div>
                            <div><div class="label">Complainant Type</div><div class="value"><?= h($case['complainant_type'] ?? 'Student') ?></div></div>
                            <div><div class="label">Complainant</div><div class="value"><?= h($case['complainant_name']) ?></div></div>
                            <div><div class="label">Gender</div><div class="value"><?= h($case['complainant_gender'] ?: 'Not provided') ?></div></div>
                            <div><div class="label">Age</div><div class="value"><?= h($case['complainant_age'] ?? 'Not provided') ?></div></div>
                            <?php if (($case['complainant_type'] ?? 'Student') === 'Student'): ?>
                                <div><div class="label">Student Number</div><div class="value"><?= h($case['complainant_student_no']) ?></div></div>
                                <div><div class="label">Academic Information</div><div class="value"><?= h(trim(($case['complainant_college'] ?? '') . ' | ' . ($case['complainant_course'] ?? '') . ' | ' . (($case['complainant_year_level'] ?? '') ?: Courses::yearLevel($case['complainant_section'] ?? '')) . ' | ' . ($case['complainant_section'] ?? ''), ' |') ?: $case['complainant_course_year']) ?></div></div>
                            <?php elseif (($case['complainant_type'] ?? '') === 'Employee'): ?>
                                <div><div class="label">Employee Number</div><div class="value"><?= h($case['complainant_employee_no']) ?></div></div>
                                <div><div class="label">Department</div><div class="value"><?= h($case['complainant_department']) ?></div></div>
                                <div><div class="label">Position</div><div class="value"><?= h($case['complainant_position']) ?></div></div>
                            <?php elseif (($case['complainant_type'] ?? '') === 'Private Individual' && !empty($case['complainant_relationship'])): ?>
                                <div><div class="label">Relationship to CLSU</div><div class="value"><?= h($case['complainant_relationship']) ?></div></div>
                            <?php elseif (($case['complainant_type'] ?? '') === 'Others'): ?>
                                <?php if (!empty($case['complainant_affiliation'])): ?><div><div class="label">Affiliation / Organization</div><div class="value"><?= h($case['complainant_affiliation']) ?></div></div><?php endif; ?>
                                <?php if (!empty($case['complainant_purpose'])): ?><div><div class="label">Relationship or Purpose</div><div class="value"><?= h($case['complainant_purpose']) ?></div></div><?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="description"><div class="label">Original Complaint / Statement</div><div class="value"><?= nl2br(h($case['complaint_details'])) ?></div></div>
                    </section>
                    <?php if ($counterStatement): ?>
                    <section class="panel" id="respondent-counter-statement">
                        <h2>Statement Exchange</h2>
                        <div class="timeline-note" style="margin-bottom:10px">Case <?= h($case['case_number']) ?><?= !empty($complaintResponse['forwarded_at']) ? ' &middot; forwarded to you for review on ' . h(date('M d, Y h:i A', strtotime($complaintResponse['forwarded_at']))) : '' ?></div>
                        <div class="mini-item remarks statement-flow">
                            <div class="label">Respondent Counter-Statement</div>
                            <div class="value" style="white-space:pre-wrap"><?= nl2br(h($counterStatement['content'] ?? '')) ?></div>
                            <div class="timeline-note">Submitted by: Respondent &middot; <?= h(date('M d, Y h:i A', strtotime($counterStatement['submitted_at']))) ?></div>
                        </div>
                        <?php if ($complaintResponseError): ?><div class="timeline-note" style="margin-top:10px;color:#b3261e"><?= h($complaintResponseError) ?></div><?php endif; ?>
                        <?php if ($complaintResponse): ?>
                        <?php if ($complaintResponse['status'] === 'Submitted'): ?>
                        <div class="mini-item" style="margin-top:12px">
                            <strong>Your Response <span class="status-pill" style="font-size:10px"><?= h($complaintResponse['status']) ?><?= !empty($complaintResponse['submitted_at']) ? ' &middot; ' . h(date('M d, Y h:i A', strtotime($complaintResponse['submitted_at']))) : '' ?></span></strong>
                            <div class="value" style="margin-top:6px;white-space:pre-wrap"><?= nl2br(h($complaintResponse['content'])) ?></div>
                        </div>
                        <?php elseif (!$complaintResponseClosed): ?>
                        <div class="next-action" style="margin-top:14px">
                            <strong>Your response is requested</strong>
                            <p>Reply to the respondent&rsquo;s statement when you are ready.</p>
                            <button type="button" class="btn btn-primary" data-complaint-response-guide><i class="bi bi-pencil-square"></i> Respond to Statement</button>
                        </div>
                        <form method="POST" action="case_details.php?id=<?= (int) $case['complaint_id'] ?>" style="margin-top:14px" id="complaintResponseForm" data-sicms-validate>
                            <?= Security::csrfField() ?>
                            <div class="label">Your Response</div>
                            <textarea id="complaint_response" name="complaint_response" rows="6" required style="width:100%;min-height:120px;margin-top:6px;line-height:1.55" placeholder="Share your response to the respondent&rsquo;s counter-statement."><?= h($complaintResponse['content'] ?? '') ?></textarea>
                            <input type="hidden" name="case_action" id="complaintResponseAction" value="save_complaint_response">
                            <div class="button-row" style="margin-top:10px">
                                <button class="btn btn-secondary" type="submit" id="complaintResponseSaveBtn"><i class="bi bi-save"></i> Save Draft</button>
                                <button class="btn btn-primary" type="submit" id="complaintResponseSubmitBtn"><i class="bi bi-send"></i> Submit Response</button>
                            </div>
                        </form>
                        <script>
                            (function () {
                                var form = document.getElementById('complaintResponseForm');
                                if (!form) return;
                                var action = document.getElementById('complaintResponseAction');
                                document.getElementById('complaintResponseSaveBtn').addEventListener('click', function () { action.value = 'save_complaint_response'; });
                                var submitBtn = document.getElementById('complaintResponseSubmitBtn');
                                var submitting = false;
                                document.querySelector('[data-complaint-response-guide]').addEventListener('click', function () {
                                    if (!window.Swal) { form.querySelector('textarea[name="complaint_response"]').focus(); return; }
                                    Swal.fire({ icon: 'info', title: 'Respond to Statement', text: 'You can submit your response to the statement. Keep it focused on the case.', showCancelButton: true, confirmButtonText: 'Continue', cancelButtonText: 'Cancel', reverseButtons: true }).then(function (result) {
                                        if (result.isConfirmed) form.querySelector('textarea[name="complaint_response"]').focus();
                                    });
                                });
                                submitBtn.addEventListener('click', function (e) {
                                    action.value = 'submit_complaint_response';
                                    if (!(form.querySelector('textarea[name="complaint_response"]').value || '').trim()) return;
                                    if (submitting) return;
                                    e.preventDefault();
                                    var continueSubmit = function () { submitting = true; form.requestSubmit(submitBtn); };
                                    if (!window.Swal) { if (window.confirm("Submit your response to the respondent's counter-statement? You will not be able to edit it afterward.")) continueSubmit(); return; }
                                    Swal.fire({ icon: 'question', title: 'Submit response?', text: 'You will not be able to edit it afterward.', showCancelButton: true, confirmButtonText: 'Submit', cancelButtonText: 'Cancel', reverseButtons: true }).then(function (result) { if (result.isConfirmed) continueSubmit(); });
                                });
                            })();
                        </script>
                        <?php endif; ?>
                        <?php endif; ?>
                    </section>
                    <?php endif; ?>
                    <section class="panel" id="hearing-schedule">
                        <?php
                        $scheduledHearings = array_values(array_filter($hearings, fn($hearing) => ($hearing['status'] ?? '') === 'Scheduled'));
                        $pastHearings = array_values(array_filter($hearings, fn($hearing) => in_array(($hearing['status'] ?? ''), ['Completed', 'Cancelled'], true)));
                        ?>
                        <h2>Hearing Schedule</h2>
                        <div class="mini-list">
                            <?php if (empty($scheduledHearings)): ?>
                                <div class="empty-state">No hearing is currently scheduled for this case.</div>
                            <?php else: ?>
                                <?php foreach ($scheduledHearings as $hearing): ?>
                                    <div class="mini-item hearing-item">
                                        <strong><i class="bi bi-calendar-event"></i> <?= h(date('M d, Y - h:i A', strtotime($hearing['hearing_datetime']))) ?></strong>
                                        <div class="value"><span class="label">Venue</span><br><?= h($hearing['venue']) ?></div>
                                        <?php if (!empty($hearing['google_meet_link'])): ?><div class="value"><a href="<?= h($hearing['google_meet_link']) ?>" target="_blank" rel="noopener"><i class="bi bi-camera-video"></i> Join Google Meet</a></div><?php endif; ?>
                                        <?php if (!empty($hearing['remarks'])): ?><div class="timeline-note"><?= nl2br(h($hearing['remarks'])) ?></div><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (!empty($pastHearings)): ?>
                                <div class="label" style="margin-top:6px">Previous Hearings</div>
                                <?php foreach ($pastHearings as $hearing): ?>
                                    <div class="mini-item">
                                        <strong><?= h(date('M d, Y - h:i A', strtotime($hearing['hearing_datetime']))) ?></strong>
                                        <div class="timeline-note"><?= h($hearing['status']) ?> · <?= h($hearing['venue']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
                <aside>
                    <section class="panel" id="case-progress">
                        <h2>Case Progress</h2>
                        <div class="timeline">
                            <?php foreach (progress_steps($case['status']) as $stage): ?>
                                <div class="timeline-step <?= stage_done($case['status'], $stage) ? 'done' : '' ?> <?= $case['status'] === $stage ? 'current' : '' ?>">
                                    <div class="timeline-dot"><?php if (stage_done($case['status'], $stage)): ?><i class="bi bi-check-lg"></i><?php endif; ?></div>
                                    <div><div class="timeline-title"><?= h($stage) ?></div><div class="timeline-note"><?= $case['status'] === $stage ? 'Current status' : (stage_done($case['status'], $stage) ? 'Completed' : 'Pending') ?></div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <section class="panel" id="case-evidence">
                        <h2>Uploaded Evidence</h2>
                        <div class="mini-list">
                            <?php if (empty($evidence)): ?><div class="empty-state">No evidence files recorded.</div><?php endif; ?>
                            <?php foreach ($evidence as $file): ?><div class="mini-item"><strong><i class="bi bi-paperclip"></i> <?= h($file['original_filename']) ?></strong><div class="timeline-note"><?= h($file['mime_type']) ?> · <?= h(number_format((int) $file['file_size'] / 1024, 1)) ?> KB · <?= h(date('M d, Y', strtotime($file['uploaded_at']))) ?></div><div style="display:flex;gap:7px;margin-top:9px"><a class="btn btn-secondary" target="_blank" href="attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=view">View</a><a class="btn btn-secondary" href="attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=download">Download</a></div></div><?php endforeach; ?>
                        </div>
                    </section>
                </aside>
            </section>
        </main>
    </div>
</div>
<?php if ($complaintResponseInfo && strpos((string) $complaintResponseInfo, 'submitted') !== false): ?>
<script>
Swal.fire({ icon: 'success', title: 'Response submitted', text: 'Your response has been added to the case successfully.', confirmButtonText: 'OK' });
</script>
<?php endif; ?>
</body>
</html>
