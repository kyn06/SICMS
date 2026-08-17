<?php
require_once __DIR__ . '/../../controllers/ComplaintController.php';
require_once __DIR__ . '/../../helpers/Colleges.php';
require_once __DIR__ . '/../../helpers/Courses.php';

$controller = new ComplaintController();
$viewData = $controller->handleCreateRequest();

$user = $viewData['user'];
$classifications = $viewData['classifications'];
$errors = $viewData['errors'];
$old = $viewData['old'];
$success = $viewData['success'];
$oldIncident = !empty($old['incident_datetime']) ? strtotime($old['incident_datetime']) : false;
$successCaseNumber = $success && preg_match('/Case Number:\s*([^\s]+)/', $success, $caseMatch) ? $caseMatch[1] : '';
$complainantProgram = Courses::split($old['complainant_course_year'] ?? '');
$complainantCourse = $old['complainant_course'] ?? $complainantProgram['course'];
$complainantSection = $old['complainant_section'] ?? $complainantProgram['section'];
$complainantType = $old['complainant_type'] ?? 'Student';
$complainantName = $complainantType === 'Student' ? trim($user['first_name'] . ' ' . $user['last_name']) : ($old['complainant_name'] ?? '');

function old_value($old, $key, $default = '') {
    return htmlspecialchars($old[$key] ?? $default);
}

function old_array_value($old, $key, $index) {
    return htmlspecialchars($old[$key][$index] ?? '');
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <style>
        body {
            align-items: stretch;
            justify-content: flex-start;
            background: #f5f7f4;
            padding: 0;
        }

        .complaint-page {
            width: 100%;
            min-height: 100vh;
        }

        .complaint-header {
            background: #123c1b;
            color: #fff;
            padding: 22px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .complaint-header h1 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .complaint-header p {
            font-size: 13px;
            color: #dbe9d9;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header-actions a,
        .btn-secondary,
        .btn-add,
        .btn-remove {
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            text-decoration: none;
        }

        .header-actions a,
        .btn-secondary {
            color: #123c1b;
            background: #fff;
            padding: 10px 14px;
        }

        .complaint-wrap {
            max-width: 1080px;
            margin: 0 auto;
            padding: 24px;
        }

        .alert {
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .alert-error {
            border: 1px solid #dc3545;
            background: #fff5f5;
            color: #b42318;
        }

        .alert-success {
            border: 1px solid #1A9D00;
            background: #f0fdf0;
            color: #137500;
        }

        .complaint-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-section {
            background: #fff;
            border: 1px solid #dce5da;
            border-radius: 8px;
            padding: 20px;
        }

        .form-section h2 {
            font-size: 18px;
            color: #172017;
            margin-bottom: 16px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        [data-complainant-types][hidden] { display: none; }

        label {
            font-size: 13px;
            color: #536052;
            font-weight: 500;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #b9c7b7;
            border-radius: 8px;
            padding: 11px 12px;
            font-family: inherit;
            font-size: 14px;
            color: #172017;
            background: #fff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .dynamic-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .dynamic-item {
            border: 1px solid #dce5da;
            border-radius: 8px;
            padding: 14px;
            background: #fbfdfb;
        }

        .dynamic-actions,
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 14px;
        }

        .btn-add {
            background: #e9f5e7;
            color: #123c1b;
            padding: 10px 14px;
        }

        .btn-remove {
            background: #fff5f5;
            color: #b42318;
            padding: 9px 12px;
        }

        .btn-submit {
            background: #1A9D00;
            color: #fff;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 20px;
        }

        .file-note {
            color: #536052;
            font-size: 12px;
            margin-top: 6px;
        }

        @media (max-width: 760px) {
            .complaint-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="../layout/system.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/complaint-form.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="complaint-page app-content">
            <?php $pageTitle = 'Submit Complaint'; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="complaint-wrap complaint-submission-page">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <section class="submission-success" aria-live="polite">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <div><h2>Complaint Submitted Successfully</h2><p>Your complaint has been received by the Student Discipline and Reformation Unit (SDRU).</p><div class="success-meta"><span>Case Number <strong><?= h($successCaseNumber) ?></strong></span><span>Current Status <strong>Submitted</strong></span></div><a class="btn btn-primary" href="my_cases.php"><i class="bi bi-folder-check"></i> Track My Complaint</a></div>
                </section>
            <?php endif; ?>

            <form class="complaint-form" id="complaintForm" action="create.php" method="POST" enctype="multipart/form-data" novalidate>
                <?= Security::csrfField() ?>
                <section class="form-section" id="complainantSection">
                    <div class="complaint-section-heading"><span><i class="bi bi-person-badge"></i></span><div><h2>Complainant Information</h2><p id="complainantHelp">Student or private-individual details</p></div></div>
                    <div class="form-grid">
                        <div class="field full">
                            <label for="complainant_type">Complainant Type</label>
                            <select id="complainant_type" name="complainant_type" required>
                                <option value="">Select Complainant Type</option>
                                <option value="Student" <?= $complainantType === 'Student' ? 'selected' : '' ?>>Student</option>
                                <option value="Employee" <?= $complainantType === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                <option value="Private Individual" <?= $complainantType === 'Private Individual' ? 'selected' : '' ?>>Private Individual</option>
                                <option value="Others" <?= $complainantType === 'Others' ? 'selected' : '' ?>>Others</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="complainant_name">Full Name</label>
                            <input id="complainant_name" name="complainant_name" value="<?= h($complainantName) ?>" required>
                        </div>
                        <div class="field" data-complainant-types="Student">
                            <label for="complainant_student_no">Student Number</label>
                            <input id="complainant_student_no" name="complainant_student_no" value="<?= old_value($old, 'complainant_student_no') ?>" required>
                        </div>
                        <div class="field">
                            <label for="complainant_email">Email</label>
                            <input id="complainant_email" type="email" name="complainant_email" value="<?= h($old['complainant_email'] ?? $user['email']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="complainant_contact">Contact Number</label>
                            <input id="complainant_contact" name="complainant_contact" value="<?= old_value($old, 'complainant_contact') ?>" required>
                        </div>
                        <div class="field" data-complainant-types="Student">
                            <label for="complainant_college">College</label>
                            <select id="complainant_college" name="complainant_college" required>
                                <option value="">Select College</option>
                                <?php foreach (Colleges::all() as $college): ?>
                                    <option value="<?= h($college) ?>" <?= (($old['complainant_college'] ?? '') === $college) ? 'selected' : '' ?>><?= h($college) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" data-complainant-types="Student">
                            <label for="complainant_course">Course</label>
                            <select id="complainant_course" name="complainant_course" required>
                                <option value="">Select Course</option>
                                <?php foreach (Courses::all() as $course): ?>
                                    <option value="<?= h($course) ?>" <?= $complainantCourse === $course ? 'selected' : '' ?>><?= h($course) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" data-complainant-types="Student">
                            <label for="complainant_section">Section</label>
                            <select id="complainant_section" name="complainant_section" required>
                                <option value="">Select Section</option>
                                <?php foreach (Courses::sections() as $year => $sections): ?>
                                    <optgroup label="<?= h($year) ?>">
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= h($section) ?>" <?= $complainantSection === $section ? 'selected' : '' ?>><?= h($section) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <input id="complainant_course_year" type="hidden" name="complainant_course_year" value="<?= h(Courses::combine($complainantCourse, $complainantSection)) ?>">
                        </div>
                        <div class="field" data-complainant-types="Student">
                            <label for="complainant_year_level">Year Level</label>
                            <select id="complainant_year_level" name="complainant_year_level" required>
                                <option value="">Select Year Level</option>
                                <?php foreach (['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Fifth Year'] as $level): ?>
                                    <option value="<?= h($level) ?>" <?= (($old['complainant_year_level'] ?? '') === $level) ? 'selected' : '' ?>><?= h($level) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" data-complainant-types="Private Individual">
                            <label for="complainant_relationship">Relationship to CLSU <span class="optional">Optional</span></label>
                            <select id="complainant_relationship" name="complainant_relationship">
                                <option value="">Select Relationship</option>
                                <?php foreach (['Parent', 'Guardian', 'Visitor', 'Alumni', 'Community Member', 'Other'] as $relationship): ?>
                                    <option value="<?= h($relationship) ?>" <?= (($old['complainant_relationship'] ?? '') === $relationship) ? 'selected' : '' ?>><?= h($relationship) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" data-complainant-types="Employee">
                            <label for="complainant_employee_no">Employee Number</label>
                            <input id="complainant_employee_no" name="complainant_employee_no" value="<?= old_value($old, 'complainant_employee_no') ?>" required>
                        </div>
                        <div class="field" data-complainant-types="Employee">
                            <label for="complainant_department">College / Office / Department</label>
                            <input id="complainant_department" name="complainant_department" value="<?= old_value($old, 'complainant_department') ?>" required>
                        </div>
                        <div class="field" data-complainant-types="Employee">
                            <label for="complainant_position">Position</label>
                            <input id="complainant_position" name="complainant_position" value="<?= old_value($old, 'complainant_position') ?>" required>
                        </div>
                        <div class="field" data-complainant-types="Others">
                            <label for="complainant_affiliation">Affiliation / Organization <span class="optional">Optional</span></label>
                            <input id="complainant_affiliation" name="complainant_affiliation" value="<?= old_value($old, 'complainant_affiliation') ?>">
                        </div>
                        <div class="field" data-complainant-types="Others">
                            <label for="complainant_purpose">Relationship or Purpose <span class="optional">Optional</span></label>
                            <input id="complainant_purpose" name="complainant_purpose" value="<?= old_value($old, 'complainant_purpose') ?>">
                        </div>
                    </div>
                </section>

                <section class="form-section" id="complaintInformationSection">
                    <div class="complaint-section-heading"><span><i class="bi bi-file-earmark-text"></i></span><div><h2>Complaint Information</h2><p>Incident and complaint details</p></div></div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="complaint_title">Complaint Title</label>
                            <input id="complaint_title" name="complaint_title" maxlength="255" value="<?= old_value($old, 'complaint_title') ?>" required>
                        </div>
                        <div class="field">
                            <label for="case_classification">Case Classification</label>
                            <select id="case_classification" name="case_classification" required>
                                <option value="">Select classification</option>
                                <?php foreach ($classifications as $classification): ?>
                                    <option value="<?= htmlspecialchars($classification) ?>" <?= (($old['case_classification'] ?? '') === $classification) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($classification) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="incident_date">Date of Incident</label>
                            <input id="incident_date" type="date" value="<?= $oldIncident ? h(date('Y-m-d', $oldIncident)) : '' ?>" required>
                        </div>
                        <div class="field">
                            <label for="incident_time">Time of Incident</label>
                            <input id="incident_time" type="time" value="<?= $oldIncident ? h(date('H:i', $oldIncident)) : '' ?>" required>
                            <input id="incident_datetime" type="hidden" name="incident_datetime" value="<?= old_value($old, 'incident_datetime') ?>">
                        </div>
                        <div class="field full">
                            <label for="incident_location">Incident Location</label>
                            <input id="incident_location" name="incident_location" value="<?= old_value($old, 'incident_location') ?>" required>
                        </div>
                        <div class="field full">
                            <label for="complaint_details">Complaint Details</label>
                            <textarea id="complaint_details" name="complaint_details" maxlength="5000" aria-describedby="complaintCounter" required><?= old_value($old, 'complaint_details') ?></textarea>
                            <div class="character-counter" id="complaintCounter"><span id="complaintCharacterCount">0</span> / 5000 characters</div>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="complaint-section-heading"><span><i class="bi bi-people"></i></span><div><h2>Respondent Information</h2><p>At least one respondent</p></div></div>
                    <div id="respondent-list" class="dynamic-list">
                        <?php $respondentCount = max(1, count($old['respondent_name'] ?? [''])); ?>
                        <?php for ($index = 0; $index < $respondentCount; $index++): ?>
                            <?php
                                $respondentProgram = Courses::split($old['respondent_course_year'][$index] ?? '');
                                $respondentCourse = $old['respondent_course'][$index] ?? $respondentProgram['course'];
                                $respondentSection = $old['respondent_section'][$index] ?? $respondentProgram['section'];
                            ?>
                            <div class="dynamic-item respondent-item">
                                <div class="form-grid">
                                    <div class="field">
                                        <label>Full Name</label>
                                        <input name="respondent_name[]" aria-label="Respondent full name" value="<?= old_array_value($old, 'respondent_name', $index) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Student Number</label>
                                        <input name="respondent_student_no[]" aria-label="Respondent student number" value="<?= old_array_value($old, 'respondent_student_no', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>College</label>
                                        <select name="respondent_college[]" aria-label="Respondent college">
                                            <option value="">Select College</option>
                                            <?php foreach (Colleges::all() as $college): ?>
                                                <option value="<?= h($college) ?>" <?= (($old['respondent_college'][$index] ?? '') === $college) ? 'selected' : '' ?>><?= h($college) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Course</label>
                                        <select name="respondent_course[]" aria-label="Respondent course">
                                            <option value="">Select Course</option>
                                            <?php foreach (Courses::all() as $course): ?>
                                                <option value="<?= h($course) ?>" <?= $respondentCourse === $course ? 'selected' : '' ?>><?= h($course) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Section</label>
                                        <select name="respondent_section[]" aria-label="Respondent section">
                                            <option value="">Select Section</option>
                                            <?php foreach (Courses::sections() as $year => $sections): ?>
                                                <optgroup label="<?= h($year) ?>">
                                                    <?php foreach ($sections as $section): ?>
                                                        <option value="<?= h($section) ?>" <?= $respondentSection === $section ? 'selected' : '' ?>><?= h($section) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="respondent_course_year[]" value="<?= h(Courses::combine($respondentCourse, $respondentSection)) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Contact Information</label>
                                        <input name="respondent_contact[]" aria-label="Respondent contact information" value="<?= old_array_value($old, 'respondent_contact', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Details</label>
                                        <input name="respondent_details[]" aria-label="Respondent details" value="<?= old_array_value($old, 'respondent_details', $index) ?>">
                                    </div>
                                </div>
                                <div class="dynamic-actions">
                                    <button type="button" class="btn-remove" onclick="removeItem(this)"><i class="bi bi-trash"></i> Remove</button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="dynamic-actions">
                        <button type="button" class="btn-add" onclick="addRespondent()"><i class="bi bi-person-plus"></i> Add Respondent</button>
                    </div>
                </section>

                <section class="form-section">
                    <div class="complaint-section-heading"><span><i class="bi bi-person-lines-fill"></i></span><div><h2>Witness Information</h2><p>At least one witness</p></div></div>
                    <div id="witness-list" class="dynamic-list">
                        <?php $witnessCount = max(1, count($old['witness_name'] ?? [''])); ?>
                        <?php for ($index = 0; $index < $witnessCount; $index++): ?>
                            <div class="dynamic-item witness-item">
                                <div class="form-grid">
                                    <div class="field">
                                        <label>Full Name</label>
                                        <input name="witness_name[]" aria-label="Witness full name" value="<?= old_array_value($old, 'witness_name', $index) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Student Number</label>
                                        <input name="witness_student_no[]" aria-label="Witness student number" value="<?= old_array_value($old, 'witness_student_no', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Contact Information</label>
                                        <input name="witness_contact[]" aria-label="Witness contact information" value="<?= old_array_value($old, 'witness_contact', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Statement</label>
                                        <input name="witness_statement[]" aria-label="Witness statement" value="<?= old_array_value($old, 'witness_statement', $index) ?>">
                                    </div>
                                </div>
                                <div class="dynamic-actions">
                                    <button type="button" class="btn-remove" onclick="removeItem(this)"><i class="bi bi-trash"></i> Remove</button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="dynamic-actions">
                        <button type="button" class="btn-add" onclick="addWitness()"><i class="bi bi-person-plus"></i> Add Witness</button>
                    </div>
                </section>

                <section class="form-section">
                    <div class="complaint-section-heading"><span><i class="bi bi-paperclip"></i></span><div><h2>Supporting Evidence</h2><p>PDF, DOCX, JPG, JPEG, or PNG up to 5MB each</p></div></div>
                    <div class="field">
                        <label for="evidence">Upload Files</label>
                        <input id="evidence" type="file" name="evidence[]" accept=".pdf,.jpg,.jpeg,.png,.docx" multiple required>
                        <p class="file-note">Accepted formats: PDF, JPG, PNG, DOCX. Maximum size: 5MB per file.</p>
                        <div class="field-error" id="evidenceError" role="alert"></div>
                        <div class="evidence-list" id="evidenceList"></div>
                    </div>
                </section>

                <div class="form-actions">
                    <div class="submission-progress hidden" id="submissionProgress"><span></span> Submitting complaint...</div>
                    <button type="submit" class="btn-submit" id="reviewButton"><i class="bi bi-clipboard-check"></i> Review Complaint</button>
                    <a class="btn-secondary" href="../../../index.php"><i class="bi bi-x"></i> Cancel</a>
                </div>
            </form>

            <dialog class="review-dialog" id="reviewDialog" aria-labelledby="reviewTitle">
                <div class="review-dialog-header"><div><h2 id="reviewTitle">Review Complaint</h2><p>Confirm the information before submission.</p></div><button type="button" class="dialog-close" id="closeReview" aria-label="Close review"><i class="bi bi-x-lg"></i></button></div>
                <div class="review-content" id="reviewContent"></div>
                <label class="certification"><input id="certification" type="checkbox"> <span>I certify that all information provided is true and accurate.</span></label>
                <div class="review-actions"><button class="btn btn-primary" id="confirmSubmit" type="button" disabled><i class="bi bi-send-check"></i> Submit Complaint</button><button class="btn btn-secondary" id="editComplaint" type="button">Continue Editing</button></div>
            </dialog>
        </main>
        </div>
    </div>

    <script>
        const complaintForm = document.getElementById('complaintForm');
        complaintForm.insertBefore(document.getElementById('complaintInformationSection'), document.getElementById('complainantSection'));
        const complaintDetails = document.getElementById('complaint_details');
        const complaintCharacterCount = document.getElementById('complaintCharacterCount');
        const incidentDate = document.getElementById('incident_date');
        const incidentTime = document.getElementById('incident_time');
        const incidentDatetime = document.getElementById('incident_datetime');
        const evidenceInput = document.getElementById('evidence');
        const evidenceList = document.getElementById('evidenceList');
        const evidenceError = document.getElementById('evidenceError');
        const reviewDialog = document.getElementById('reviewDialog');
        const reviewContent = document.getElementById('reviewContent');
        const certification = document.getElementById('certification');
        const confirmSubmit = document.getElementById('confirmSubmit');
        const reviewButton = document.getElementById('reviewButton');
        const submissionProgress = document.getElementById('submissionProgress');
        const complainantTypeInput = document.getElementById('complainant_type');
        const complainantNameInput = document.getElementById('complainant_name');
        const complainantEmailInput = document.getElementById('complainant_email');
        const complainantContactInput = document.getElementById('complainant_contact');
        const studentAccountName = <?= json_encode(trim($user['first_name'] . ' ' . $user['last_name'])) ?>;
        const studentAccountEmail = <?= json_encode($user['email']) ?>;
        let activeComplainantType = complainantTypeInput.value;
        const complainantIdentityCache = {};
        let confirmed = false;

        function removeItem(button) {
            const list = button.closest('.dynamic-list');
            const item = button.closest('.dynamic-item');

            if (list && list.children.length > 1) {
                item.remove();
            }
        }

        function addRespondent() {
            const list = document.getElementById('respondent-list');
            const clone = list.querySelector('.respondent-item').cloneNode(true);
            clone.querySelectorAll('input, select').forEach(control => control.value = '');
            list.appendChild(clone);
        }

        function addWitness() {
            const list = document.getElementById('witness-list');
            const clone = list.querySelector('.witness-item').cloneNode(true);
            clone.querySelectorAll('input').forEach(input => input.value = '');
            list.appendChild(clone);
        }

        function composeIncidentDatetime() {
            incidentDatetime.value = incidentDate.value && incidentTime.value ? `${incidentDate.value}T${incidentTime.value}` : '';
        }

        function composeCourseSections() {
            const course = document.getElementById('complainant_course').value;
            const section = document.getElementById('complainant_section').value;
            document.getElementById('complainant_course_year').value = course && section ? `${course} | ${section}` : '';

            document.querySelectorAll('.respondent-item').forEach(item => {
                const respondentCourse = item.querySelector('[name="respondent_course[]"]').value;
                const respondentSection = item.querySelector('[name="respondent_section[]"]').value;
                item.querySelector('[name="respondent_course_year[]"]').value = respondentCourse && respondentSection
                    ? `${respondentCourse} | ${respondentSection}`
                    : '';
            });
        }

        function synchronizeAcademicFields(source) {
            const year = document.getElementById('complainant_year_level');
            const section = document.getElementById('complainant_section');
            const mapping = {'1':'First Year','2':'Second Year','3':'Third Year','4':'Fourth Year','5':'Fifth Year'};
            if (source === section && section.value) year.value = mapping[section.value.charAt(0)] || '';
            if (source === year && section.value) {
                const expected = Object.entries(mapping).find(([, label]) => label === year.value)?.[0];
                if (!expected || !section.value.startsWith(`${expected}-`)) section.value = '';
            }
            composeCourseSections();
        }

        function updateComplainantFields(initial = false) {
            const selectedType = complainantTypeInput.value;
            const isStudent = selectedType === 'Student';
            if (!initial && activeComplainantType && activeComplainantType !== 'Student') {
                complainantIdentityCache[activeComplainantType] = {name: complainantNameInput.value, email: complainantEmailInput.value, contact: complainantContactInput.value};
            }
            document.querySelectorAll('[data-complainant-types]').forEach(field => {
                const visible = field.dataset.complainantTypes.split(',').includes(selectedType);
                field.hidden = !visible;
                field.querySelectorAll('input, select').forEach(control => {
                    if (control.dataset.typeRequired === undefined) control.dataset.typeRequired = control.required ? '1' : '0';
                    control.disabled = !visible;
                    control.required = visible && control.dataset.typeRequired === '1';
                });
            });
            if (isStudent) {
                complainantNameInput.value = studentAccountName;
                complainantEmailInput.value = studentAccountEmail;
                complainantNameInput.readOnly = true;
                complainantEmailInput.readOnly = true;
            } else {
                complainantNameInput.readOnly = false;
                complainantEmailInput.readOnly = false;
                if (!initial) {
                    const cached = complainantIdentityCache[selectedType] || {};
                    complainantNameInput.value = cached.name || '';
                    complainantEmailInput.value = cached.email || '';
                    complainantContactInput.value = cached.contact || '';
                }
            }
            activeComplainantType = selectedType;
            document.getElementById('complainantHelp').textContent = isStudent ? 'Authenticated student information' : selectedType === 'Employee' ? 'Employee and department information' : 'Personal contact information';
            composeCourseSections();
        }

        function formatSize(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / 1048576).toFixed(1)} MB`;
        }

        function validateEvidence() {
            const allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
            const files = Array.from(evidenceInput.files);
            const invalid = files.find(file => file.size > 5 * 1024 * 1024 || !allowed.includes(file.name.split('.').pop().toLowerCase()));
            evidenceError.textContent = invalid ? `${invalid.name} must be an accepted file type and no larger than 5MB.` : '';
            evidenceInput.setCustomValidity(invalid ? 'Invalid evidence file.' : '');
            return !invalid && files.length > 0;
        }

        function renderEvidence() {
            const files = Array.from(evidenceInput.files);
            evidenceList.replaceChildren();
            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'evidence-item';
                const details = document.createElement('div');
                details.innerHTML = '<i class="bi bi-file-earmark"></i>';
                const text = document.createElement('span');
                text.textContent = file.name;
                const size = document.createElement('small');
                size.textContent = formatSize(file.size);
                details.append(text, size);
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'evidence-remove';
                remove.setAttribute('aria-label', `Remove ${file.name}`);
                remove.innerHTML = '<i class="bi bi-x-lg"></i>';
                remove.addEventListener('click', () => {
                    const transfer = new DataTransfer();
                    files.filter((unused, fileIndex) => fileIndex !== index).forEach(remaining => transfer.items.add(remaining));
                    evidenceInput.files = transfer.files;
                    renderEvidence();
                    validateEvidence();
                });
                item.append(details, remove);
                evidenceList.appendChild(item);
            });
            validateEvidence();
        }

        function valueOf(id, fallback = 'Not provided') {
            const control = document.getElementById(id);
            if (!control) return fallback;
            const value = control.tagName === 'SELECT' ? control.options[control.selectedIndex]?.text : control.value;
            return String(value || '').trim() || fallback;
        }

        function addReviewGroup(title, rows) {
            const section = document.createElement('section');
            const heading = document.createElement('h3');
            heading.textContent = title;
            section.appendChild(heading);
            rows.forEach(([label, value]) => {
                const row = document.createElement('div');
                row.className = 'review-row';
                const name = document.createElement('span');
                name.textContent = label;
                const content = document.createElement('strong');
                content.textContent = value;
                row.append(name, content);
                section.appendChild(row);
            });
            reviewContent.appendChild(section);
        }

        function buildReview() {
            composeCourseSections();
            reviewContent.replaceChildren();
            addReviewGroup('Complaint Information', [
                ['Title', valueOf('complaint_title')],
                ['Classification', valueOf('case_classification')],
                ['Incident', `${valueOf('incident_date')} ${valueOf('incident_time')}`],
                ['Location', valueOf('incident_location')],
                ['Description', valueOf('complaint_details')],
            ]);
            addReviewGroup('Complainant Information', [
                ['Complainant Type', valueOf('complainant_type')],
                ['Full Name', valueOf('complainant_name')],
                ...(complainantTypeInput.value === 'Student' ? [
                    ['Student Number', valueOf('complainant_student_no')],
                    ['College', valueOf('complainant_college')],
                    ['Course', valueOf('complainant_course')],
                    ['Year Level', valueOf('complainant_year_level')],
                    ['Section', valueOf('complainant_section')],
                ] : complainantTypeInput.value === 'Employee' ? [
                    ['Employee Number', valueOf('complainant_employee_no')],
                    ['College / Office / Department', valueOf('complainant_department')],
                    ['Position', valueOf('complainant_position')],
                ] : complainantTypeInput.value === 'Private Individual' ? [
                    ['Relationship to CLSU', valueOf('complainant_relationship')],
                ] : [
                    ['Affiliation / Organization', valueOf('complainant_affiliation')],
                    ['Relationship or Purpose', valueOf('complainant_purpose')],
                ]),
                ['Email', valueOf('complainant_email')],
                ['Contact', valueOf('complainant_contact')],
            ]);
            const respondentNames = Array.from(document.querySelectorAll('[name="respondent_name[]"]')).map(input => input.value.trim()).filter(Boolean);
            const witnessNames = Array.from(document.querySelectorAll('[name="witness_name[]"]')).map(input => input.value.trim()).filter(Boolean);
            addReviewGroup('People and Evidence', [
                ['Respondents', respondentNames.join(', ')],
                ['Witnesses', witnessNames.join(', ')],
                ['Evidence', Array.from(evidenceInput.files).map(file => file.name).join(', ')],
            ]);
        }

        complaintDetails.addEventListener('input', () => { complaintCharacterCount.textContent = complaintDetails.value.length; });
        incidentDate.addEventListener('change', composeIncidentDatetime);
        incidentTime.addEventListener('change', composeIncidentDatetime);
        complainantTypeInput.addEventListener('change', () => updateComplainantFields(false));
        document.getElementById('complainant_year_level').addEventListener('change', event => synchronizeAcademicFields(event.target));
        document.getElementById('complainant_section').addEventListener('change', event => synchronizeAcademicFields(event.target));
        complaintForm.addEventListener('change', event => {
            if (event.target.matches('[name="complainant_course"], [name="complainant_section"], [name="respondent_course[]"], [name="respondent_section[]"]')) {
                composeCourseSections();
            }
        });
        evidenceInput.addEventListener('change', renderEvidence);
        certification.addEventListener('change', () => { confirmSubmit.disabled = !certification.checked; });
        document.getElementById('closeReview').addEventListener('click', () => reviewDialog.close());
        document.getElementById('editComplaint').addEventListener('click', () => reviewDialog.close());

        complaintForm.addEventListener('submit', event => {
            composeIncidentDatetime();
            composeCourseSections();
            validateEvidence();
            if (!complaintForm.checkValidity()) {
                event.preventDefault();
                complaintForm.reportValidity();
                return;
            }
            if (!confirmed) {
                event.preventDefault();
                certification.checked = false;
                confirmSubmit.disabled = true;
                buildReview();
                reviewDialog.showModal();
                return;
            }
            reviewButton.disabled = true;
            confirmSubmit.disabled = true;
            submissionProgress.classList.remove('hidden');
        });

        confirmSubmit.addEventListener('click', () => {
            if (!certification.checked) return;
            confirmed = true;
            reviewDialog.close();
            complaintForm.requestSubmit(reviewButton);
        });

        complaintCharacterCount.textContent = complaintDetails.value.length;
        composeIncidentDatetime();
        composeCourseSections();
        updateComplainantFields(true);

        <?php if ($success): ?>
        setTimeout(() => { window.location.href = 'my_cases.php'; }, 7000);
        <?php endif; ?>
    </script>
</body>

</html>
