<?php
require_once __DIR__ . '/../../controllers/LegacyCaseController.php';
require_once __DIR__ . '/../../../routes.php';
require_once __DIR__ . '/../../helpers/Courses.php';
require_once __DIR__ . '/../../helpers/Security.php';

$controller = new LegacyCaseController();
$complaintId = (int) ($_GET['id'] ?? 0);
$viewData = $controller->show($complaintId);

$user = $viewData['user'];
$case = $viewData['case'];
$respondents = $viewData['respondents'];
$witnesses = $viewData['witnesses'];
$evidence = $viewData['evidence'];
$history = $viewData['history'];
$hearings = $viewData['hearings'];
$canEdit = $viewData['canEdit'];
$statuses = $viewData['statuses'];
$message = $viewData['message'];
$errors = $viewData['errors'];

$controller->clearFlash();

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('person_name')) {
    function person_name($first, $last) {
        $name = trim(($first ?? '') . ' ' . ($last ?? ''));
        return $name !== '' ? $name : 'Unassigned';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrated Case Details | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/cases.css">
    <link rel="stylesheet" href="../layout/legacy_cases.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .legacy-show-page {
            min-height: 100vh;
            width: 100%;
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
            background: var(--surface-primary, #fff);
            border: 1px solid var(--border-primary, #dce5da);
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .panel h2 {
            color: var(--text-primary, #172017);
            font-size: 18px;
            margin-bottom: 14px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .detail {
            border-bottom: 1px solid var(--divider, #edf4eb);
            padding-bottom: 10px;
        }

        .detail.full {
            grid-column: 1 / -1;
        }

        .label {
            color: var(--text-muted, #536052);
            font-size: 12px;
            margin-bottom: 4px;
        }

        .value {
            color: var(--text-primary, #172017);
            font-size: 14px;
            line-height: 1.5;
        }

        .status {
            background: var(--surface-accent, #edf4eb);
            border-radius: 999px;
            color: var(--text-secondary, #123c1b);
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
            background: rgba(26, 157, 0, 0.08);
            border: 1px solid rgba(26, 157, 0, 0.35);
            color: var(--status-success-text, #137500);
        }

        .alert-error {
            background: var(--status-danger-bg, #fff5f5);
            border: 1px solid rgba(180, 35, 24, 0.5);
            color: var(--status-danger-text, #b42318);
        }

        .action-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 14px;
        }

        select,
        textarea,
        input {
            border: 1px solid var(--input-border, #b9c7b7);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            padding: 10px 12px;
            width: 100%;
            background: var(--input-bg, #fff);
            color: var(--text-primary, #172017);
        }

        textarea {
            min-height: 68px;
            resize: vertical;
        }

        .btn {
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: inline-block;
            font-family: inherit;
            font-size: 13px;
            padding: 10px;
            text-align: center;
            text-decoration: none;
        }

        .btn-primary { background: var(--bg-sidebar, #123c1b); }
        .btn-secondary { background: rgba(26, 157, 0, 0.08); color: var(--text-secondary, #123c1b); }
        .btn-danger { background: var(--status-danger-text, #b42318); }
        .list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .list-item {
            border: 1px solid var(--divider, #edf4eb);
            border-radius: 8px;
            padding: 12px;
        }

        .timeline-item {
            border-left: 3px solid var(--accent, #1A9D00);
            padding: 0 0 16px 12px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-scroll {
            max-height: 560px;
            overflow-y: auto;
        }

        .muted {
            color: var(--text-muted, #536052);
            font-size: 13px;
        }

        .inline-form {
            display: grid;
            gap: 10px;
            margin-bottom: 12px;
        }

        @media (max-width: 900px) {
            .grid,
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=6">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="legacy-show-page app-content">
            <?php $pageTitle = 'Migrated Case ' . $case['case_number']; require __DIR__ . '/../layout/topbar.php'; ?>

            <main class="case-wrap case-detail-view">
                <div style="margin-bottom:14px">
                    <a class="btn btn-secondary" href="<?= h(app_route('cases.index')) ?>"><i class="bi bi-arrow-left"></i> Back to Case Management</a>
                    <?php if ($canEdit): ?>
                        <a class="btn btn-primary" href="edit.php?id=<?= (int) $case['complaint_id'] ?>"><i class="bi bi-pencil"></i> Edit Migrated Case</a>
                    <?php endif; ?>
                </div>

                <header class="case-heading">
                    <div><h1>Legacy Case <?= h($case['case_number']) ?></h1><p>Historical / digitized record</p></div>
                    <span class="status"><?= h($case['status']) ?></span>
                </header>
                <nav class="case-detail-nav" aria-label="Legacy case details sections">
                    <a href="#legacy-overview">Overview</a><a href="#legacy-people">People</a><a href="#legacy-evidence">Evidence</a><a href="#legacy-hearings">Hearings</a>
                    <?php if ($canEdit): ?><a href="#legacy-actions">Actions</a><?php endif; ?><a href="#legacy-timeline">Timeline</a>
                </nav>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?= h($message) ?></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= h($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="grid">
                    <div>
                        <section class="panel" id="legacy-overview">
                            <h2>Case Overview</h2>
                            <div class="details-grid">
                                <div class="detail">
                                    <div class="label">Original Case Number</div>
                                    <div class="value"><?= h($case['case_number']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Original Case Date</div>
                                    <div class="value"><?= h(date('M d, Y', strtotime($case['original_case_date']))) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Case Classification</div>
                                    <div class="value"><?= h($case['case_classification']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Status</div>
                                    <div class="value"><span class="status"><?= h($case['status']) ?></span></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Source Document</div>
                                    <div class="value"><?= h($case['legacy_entry_source'] ?: 'Not indicated') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Digitized On</div>
                                    <div class="value"><?= h(date('M d, Y h:i A', strtotime($case['created_at']))) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Incident Date</div>
                                    <div class="value"><?= h($case['incident_datetime'] ? date('M d, Y h:i A', strtotime($case['incident_datetime'])) : 'Not provided') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Incident Location</div>
                                    <div class="value"><?= h($case['incident_location'] ?: 'Not provided') ?></div>
                                </div>
                                <div class="detail full">
                                    <div class="label">Case Description</div>
                                    <div class="value"><?= nl2br(h($case['complaint_details'])) ?></div>
                                </div>
                            </div>
                        </section>

                        <section class="panel" id="legacy-people">
                            <h2>People Involved &mdash; Complainant</h2>
                            <div class="details-grid">
                                <div class="detail">
                                    <div class="label">Name</div>
                                    <div class="value"><?= h($case['complainant_name']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Type</div>
                                    <div class="value"><?= h($case['complainant_type'] ?? 'Student') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Gender</div>
                                    <div class="value"><?= h($case['complainant_gender'] ?: 'Not provided') ?></div>
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
                                        <div class="value"><?= h(trim(($case['complainant_course'] ?? '') . ' ' . (($case['complainant_year_level'] ?? '') ?: Courses::yearLevel($case['complainant_section'] ?? '')) . ' ' . ($case['complainant_section'] ?? '')) ?: $case['complainant_course_year']) ?></div>
                                    </div>
                                <?php elseif (($case['complainant_type'] ?? '') === 'Employee'): ?>
                                    <div class="detail">
                                        <div class="label">Employee Number</div>
                                        <div class="value"><?= h($case['complainant_employee_no']) ?></div>
                                    </div>
                                    <div class="detail">
                                        <div class="label">Department</div>
                                        <div class="value"><?= h($case['complainant_department']) ?></div>
                                    </div>
                                    <div class="detail">
                                        <div class="label">Position</div>
                                        <div class="value"><?= h($case['complainant_position']) ?></div>
                                    </div>
                                <?php elseif (($case['complainant_type'] ?? '') === 'Private Individual'): ?>
                                    <div class="detail">
                                        <div class="label">Relationship</div>
                                        <div class="value"><?= h($case['complainant_relationship']) ?></div>
                                    </div>
                                <?php elseif (($case['complainant_type'] ?? '') === 'Others'): ?>
                                    <div class="detail">
                                        <div class="label">Affiliation</div>
                                        <div class="value"><?= h($case['complainant_affiliation']) ?></div>
                                    </div>
                                    <div class="detail">
                                        <div class="label">Purpose</div>
                                        <div class="value"><?= h($case['complainant_purpose']) ?></div>
                                    </div>
                                <?php endif; ?>
                                <div class="detail">
                                    <div class="label">Email</div>
                                    <div class="value"><?= h($case['complainant_email']) ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Contact</div>
                                    <div class="value"><?= h($case['complainant_contact']) ?></div>
                                </div>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Respondents</h2>
                            <div class="list">
                                <?php if (empty($respondents)): ?>
                                    <div class="muted">No respondents recorded.</div>
                                <?php endif; ?>
                                <?php foreach ($respondents as $respondent): ?>
                                    <?php $rtype = $respondent['respondent_type'] ?? 'Student'; ?>
                                    <div class="list-item">
                                        <strong><?= h($respondent['full_name']) ?> <span class="muted">(<?= h($rtype) ?>)</span></strong>
                                        <?php if (!empty($respondent['student_no'])): ?><div class="muted">Student Number: <?= h($respondent['student_no']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['employee_no'])): ?><div class="muted">Employee Number: <?= h($respondent['employee_no']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['college'])): ?><div class="muted">College: <?= h($respondent['college']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['course_year'])): ?><div class="muted">Course and Section: <?= h($respondent['course_year']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['office_department'])): ?><div class="muted">Department: <?= h($respondent['office_department']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['position'])): ?><div class="muted">Position: <?= h($respondent['position']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['affiliation'])): ?><div class="muted">Affiliation: <?= h($respondent['affiliation']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['contact_info'])): ?><div class="muted">Contact: <?= h($respondent['contact_info']) ?></div><?php endif; ?>
                                        <?php if (!empty($respondent['details'])): ?><div class="value"><?= h($respondent['details']) ?></div><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Witnesses</h2>
                            <div class="list">
                                <?php if (empty($witnesses)): ?>
                                    <div class="muted">No witnesses recorded.</div>
                                <?php endif; ?>
                                <?php foreach ($witnesses as $witness): ?>
                                    <?php $wtype = $witness['person_type'] ?? 'Private Individual'; ?>
                                    <div class="list-item">
                                        <strong><?= h($witness['full_name']) ?> <span class="muted">(<?= h($wtype) ?>)</span></strong>
                                        <?php if (!empty($witness['student_no'])): ?><div class="muted">Student Number: <?= h($witness['student_no']) ?></div><?php endif; ?>
                                        <?php if (!empty($witness['contact_info'])): ?><div class="muted">Contact: <?= h($witness['contact_info']) ?></div><?php endif; ?>
                                        <?php if (!empty($witness['statement'])): ?><div class="value"><?= h($witness['statement']) ?></div><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="panel" id="legacy-evidence">
                            <h2>Evidence Attachments</h2>
                            <div class="list">
                                <?php if (empty($evidence)): ?>
                                    <div class="muted">No evidence uploaded.</div>
                                <?php endif; ?>
                                <?php foreach ($evidence as $file): ?>
                                    <div class="list-item">
                                        <strong><?= h($file['original_filename']) ?></strong>
                                        <div class="muted"><?= h(ucfirst($file['doc_type'] ?: 'supporting')) ?> &middot; <?= h(number_format($file['file_size'] / 1024, 1)) ?> KB &middot; <?= h(date('M d, Y', strtotime($file['uploaded_at']))) ?></div>
                                        <div style="display:flex;gap:8px;margin-top:8px">
                                            <a class="btn btn-secondary" target="_blank" href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=view">View</a>
                                            <a class="btn btn-secondary" href="../complaints/attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=download">Download</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($canEdit): ?>
                                    <form class="inline-form" method="POST" enctype="multipart/form-data" action="show.php?id=<?= (int) $case['complaint_id'] ?>" data-sicms-validate>
                                        <?= Security::csrfField() ?>
                                        <input type="hidden" name="legacy_action" value="add_evidence">
                                        <div class="label">Upload additional document</div>
                                        <input type="file" name="evidence_single" required accept=".pdf,.jpg,.jpeg,.png,.docx" data-sicms-size-mb="5" data-sicms-accept-ext="pdf,jpg,jpeg,png,docx">
                                        <select name="doc_type">
                                            <option value="supporting">Supporting</option>
                                            <option value="resolution">Resolution</option>
                                        </select>
                                        <button class="btn btn-primary" type="submit" data-sicms-processing-label="Uploading Document..." data-sicms-processing-modal="true">Upload Document</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Outcome &amp; Resolution</h2>
                            <div class="details-grid">
                                <div class="detail">
                                    <div class="label">Legacy Outcome</div>
                                    <div class="value"><?= h($case['legacy_outcome'] ?: 'Not indicated') ?></div>
                                </div>
                                <div class="detail">
                                    <div class="label">Resolution Date</div>
                                    <div class="value"><?= h($case['resolution_date'] ? date('M d, Y', strtotime($case['resolution_date'])) : 'Not indicated') ?></div>
                                </div>
                                <div class="detail full">
                                    <div class="label">Action Taken</div>
                                    <div class="value"><?= nl2br(h($case['action_taken'] ?: 'Not indicated')) ?></div>
                                </div>
                                <div class="detail full">
                                    <div class="label">Remarks / Notes</div>
                                    <div class="value"><?= nl2br(h($case['remarks_notes'] ?: 'Not indicated')) ?></div>
                                </div>
                            </div>
                        </section>

                        <section class="panel" id="legacy-hearings">
                            <h2>Hearings</h2>
                            <div class="list">
                                <?php if (empty($hearings)): ?>
                                    <div class="muted">No hearings recorded for this legacy case.</div>
                                <?php endif; ?>
                                <?php foreach ($hearings as $hearing): ?>
                                    <div class="list-item">
                                        <strong><?= h(date('M d, Y - h:i A', strtotime($hearing['hearing_datetime']))) ?></strong>
                                        <div class="muted"><?= h($hearing['status']) ?> &middot; <?= h($hearing['venue']) ?></div>
                                        <?php if (!empty($hearing['remarks'])): ?><div class="value"><?= nl2br(h($hearing['remarks'])) ?></div><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>

                    <aside>
                        <section class="panel" id="legacy-actions">
                            <h2>Staff Actions</h2>
                            <?php if (!$canEdit): ?>
                                <p class="muted">Migrated cases can only be modified by SDRU staff.</p>
                            <?php else: ?>
                                <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="legacy_action" value="status">
                                    <div class="label">Change Status</div>
                                    <select name="status">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= h($status) ?>" <?= $case['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="remarks" placeholder="Remarks for this status change"></textarea>
                                    <button class="btn btn-primary" type="submit" data-confirm-title="Update Migrated Case Status?" data-confirm="This will change the recorded workflow status of the migrated case." data-confirm-button="Yes, Update Status" data-sicms-processing-label="Updating Status..." data-sicms-processing-modal="true">Update Status</button>
                                </form>

                                <form class="action-form" method="POST" action="show.php?id=<?= (int) $case['complaint_id'] ?>">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="legacy_action" value="outcome">
                                    <div class="label">Update Outcome &amp; Resolution</div>
                                    <input name="legacy_outcome" placeholder="Legacy outcome" value="<?= h($case['legacy_outcome']) ?>">
                                    <input name="action_taken" placeholder="Action taken">
                                    <input type="date" name="resolution_date" value="<?= h($case['resolution_date']) ?>">
                                    <textarea name="remarks_notes" placeholder="Remarks / notes"><?= h($case['remarks_notes']) ?></textarea>
                                    <button class="btn btn-primary" type="submit" data-sicms-processing-label="Saving Legacy Case..." data-sicms-processing-modal="true">Save Outcome</button>
                                </form>
                            <?php endif; ?>
                        </section>

                        <section class="panel" id="legacy-timeline">
                            <h2>Timeline</h2>
                            <div class="timeline-scroll">
                                <?php if (empty($history)): ?>
                                    <p class="muted">No case history yet.</p>
                                <?php else: ?>
                                    <?php foreach ($history as $item): ?>
                                        <div class="timeline-item">
                                            <strong><?= h($item['action']) ?></strong>
                                            <div class="muted"><?= h(date('M d, Y h:i A', strtotime($item['created_at']))) ?></div>
                                            <div class="value"><?= h($item['previous_status']) ?><?= $item['new_status'] ? ' to ' . h($item['new_status']) : '' ?></div>
                                            <?php if (!empty($item['remarks'])): ?><div class="value"><?= nl2br(h($item['remarks'])) ?></div><?php endif; ?>
                                            <div class="muted">By <?= h(person_name($item['actor_first_name'], $item['actor_last_name'])) ?></div>
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
</body>

</html>
