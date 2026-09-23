<?php
require_once __DIR__ . '/../../controllers/LegacyCaseController.php';
require_once __DIR__ . '/../../../routes.php';
require_once __DIR__ . '/../../helpers/Colleges.php';
require_once __DIR__ . '/../../helpers/Courses.php';
require_once __DIR__ . '/../../helpers/Security.php';

$controller = new LegacyCaseController();
$complaintId = (int) ($_GET['id'] ?? 0);
$viewData = $controller->edit($complaintId);

$user = $viewData['user'];
$case = $viewData['case'];
$respondents = $viewData['respondents'];
$witnesses = $viewData['witnesses'];
$evidence = $viewData['evidence'];
$hearings = $viewData['hearings'];
$statuses = $viewData['statuses'];
$classifications = $viewData['classifications'];
$colleges = $viewData['colleges'];
$errors = $viewData['errors'];
$old = $viewData['old'];

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('val')) {
    function val($case, $old, $key) {
        $v = $old[$key] ?? ($case[$key] ?? '');
        return htmlspecialchars((string) $v);
    }
}

if (!function_exists('ov')) {
    function ov($old, $key, $default = '') {
        return $old[$key] ?? $default;
    }
}

if (!function_exists('selected_if')) {
    function selected_if($value, $option) {
        return $value === $option ? 'selected' : '';
    }
}

$complainantType = ov($old, 'complainant_type', $case['complainant_type'] ?? 'Student');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Migrated Case | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/cases.css">
    <link rel="stylesheet" href="../layout/complaint-form.css">
    <link rel="stylesheet" href="../layout/legacy_cases.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-shell app-content">
            <?php $pageTitle = 'Edit Migrated Case ' . $case['case_number']; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="case-wrap">
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="legacyEditForm" method="POST" action="index.php" enctype="multipart/form-data" data-sicms-validate>
                <?= Security::csrfField() ?>
                <input type="hidden" name="legacy_action" value="update">
                <input type="hidden" name="complaint_id" value="<?= (int) $case['complaint_id'] ?>">

                <section class="form-section optional-ask-section">
                    <h2>Available Case Records</h2>
                    <p class="optional-hint">Select what this case has. Changing a section to "No" will remove its saved entries.</p>
                    <div class="optional-ask-grid">
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have a respondent?</span>
                            <label class="radio-inline"><input type="radio" name="has_respondents" value="no" <?= empty($respondents) ? 'checked' : '' ?>> No</label>
                            <label class="radio-inline"><input type="radio" name="has_respondents" value="yes" <?= !empty($respondents) ? 'checked' : '' ?>> Yes</label>
                        </div>
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have a witness?</span>
                            <label class="radio-inline"><input type="radio" name="has_witnesses" value="no" <?= empty($witnesses) ? 'checked' : '' ?>> No</label>
                            <label class="radio-inline"><input type="radio" name="has_witnesses" value="yes" <?= !empty($witnesses) ? 'checked' : '' ?>> Yes</label>
                        </div>
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have supporting evidence?</span>
                            <label class="radio-inline"><input type="radio" name="has_evidence" value="no" <?= empty($evidence) ? 'checked' : '' ?>> No</label>
                            <label class="radio-inline"><input type="radio" name="has_evidence" value="yes" <?= !empty($evidence) ? 'checked' : '' ?>> Yes</label>
                        </div>
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have hearing records?</span>
                            <label class="radio-inline"><input type="radio" name="has_hearings" value="no" <?= empty($hearings) ? 'checked' : '' ?>> No</label>
                            <label class="radio-inline"><input type="radio" name="has_hearings" value="yes" <?= !empty($hearings) ? 'checked' : '' ?>> Yes</label>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Case Identification</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label>Original Case Number <span class="required">*</span></label>
                            <input name="case_number" value="<?= val($case, $old, 'case_number') ?>" required maxlength="50">
                        </div>
                        <div class="field">
                            <label>Original Case Date <span class="optional">(optional)</span></label>
                            <input type="date" name="original_case_date" value="<?= val($case, $old, 'original_case_date') ?>">
                        </div>
                        <div class="field">
                            <label>Source Document</label>
                            <input name="legacy_entry_source" value="<?= val($case, $old, 'legacy_entry_source') ?>">
                        </div>
                        <div class="field">
                            <label>Case Classification <span class="required">*</span></label>
                            <select name="case_classification" required>
                                <option value="">Select Classification</option>
                                <?php foreach ($classifications as $classification): ?>
                                    <option value="<?= h($classification) ?>" <?= selected_if($case['case_classification'], $classification) ?>><?= h($classification) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= h($status) ?>" <?= selected_if($case['status'], $status) ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Incident Date &amp; Time</label>
                            <input type="datetime-local" name="incident_datetime" value="<?= h($case['incident_datetime']) ?>">
                        </div>
                        <div class="field">
                            <label>Incident Location</label>
                            <input name="incident_location" value="<?= val($case, $old, 'incident_location') ?>">
                        </div>
                        <div class="field full">
                            <label>Case Description <span class="optional">(optional)</span></label>
                            <textarea name="complaint_details"><?= val($case, $old, 'complaint_details') ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Complainant Information</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label>Complainant Type</label>
                            <select name="complainant_type" id="complainantType">
                                <option value="Student" <?= selected_if($complainantType, 'Student') ?>>Student</option>
                                <option value="Employee" <?= selected_if($complainantType, 'Employee') ?>>Employee</option>
                                <option value="Private Individual" <?= selected_if($complainantType, 'Private Individual') ?>>Private Individual</option>
                                <option value="Others" <?= selected_if($complainantType, 'Others') ?>>Others</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Complainant Name <span class="required">*</span></label>
                            <input name="complainant_name" value="<?= val($case, $old, 'complainant_name') ?>" required>
                        </div>
                        <div class="field">
                            <label>Gender</label>
                            <select name="complainant_gender">
                                <option value="">Select</option>
                                <option value="Male" <?= selected_if($case['complainant_gender'], 'Male') ?>>Male</option>
                                <option value="Female" <?= selected_if($case['complainant_gender'], 'Female') ?>>Female</option>
                            </select>
                        </div>
                        <div class="field"><label>Email</label><input name="complainant_email" value="<?= val($case, $old, 'complainant_email') ?>"></div>
                        <div class="field"><label>Contact Number</label><input name="complainant_contact" value="<?= val($case, $old, 'complainant_contact') ?>"></div>
                        <div class="field complainant-student"><label>Student Number</label><input name="complainant_student_no" value="<?= val($case, $old, 'complainant_student_no') ?>"></div>
                        <div class="field complainant-student">
                            <label>College</label>
                            <select name="complainant_college">
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $college): ?>
                                    <option value="<?= h($college) ?>" <?= selected_if($case['complainant_college'], $college) ?>><?= h($college) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field complainant-student">
                            <label>Course</label>
                            <select name="complainant_course">
                                <option value="">Select Course</option>
                                <?php foreach (Courses::all() as $course): ?>
                                    <option value="<?= h($course) ?>" <?= selected_if($case['complainant_course'], $course) ?>><?= h($course) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field complainant-student">
                            <label>Section</label>
                            <select name="complainant_section">
                                <option value="">Select Section</option>
                                <?php foreach (Courses::sections() as $year => $sections): ?>
                                    <optgroup label="<?= h($year) ?>">
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= h($section) ?>" <?= selected_if($case['complainant_section'], $section) ?>><?= h($section) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="complainant_course_year" value="<?= val($case, $old, 'complainant_course_year') ?>">
                        </div>
                        <div class="field complainant-employee"><label>Employee Number</label><input name="complainant_employee_no" value="<?= val($case, $old, 'complainant_employee_no') ?>"></div>
                        <div class="field complainant-employee"><label>Department</label><input name="complainant_department" value="<?= val($case, $old, 'complainant_department') ?>"></div>
                        <div class="field complainant-employee"><label>Position</label><input name="complainant_position" value="<?= val($case, $old, 'complainant_position') ?>"></div>
                        <div class="field complainant-others"><label>Affiliation</label><input name="complainant_affiliation" value="<?= val($case, $old, 'complainant_affiliation') ?>"></div>
                        <div class="field complainant-others"><label>Purpose</label><input name="complainant_purpose" value="<?= val($case, $old, 'complainant_purpose') ?>"></div>
                        <div class="field complainant-individual"><label>Relationship</label><input name="complainant_relationship" value="<?= val($case, $old, 'complainant_relationship') ?>"></div>
                    </div>
                </section>

                <section class="form-section" data-has="has_respondents" hidden>
                    <h2>Respondents</h2>
                    <div id="respondentList">
                        <?php foreach ($respondents as $index => $respondent): ?>
                            <div class="dynamic-item respondent-item">
                                <div class="form-grid">
                                    <div class="field">
                                        <label>Respondent Type <span class="required">*</span></label>
                                        <select name="respondent_type[]" required>
                                            <option value="Student" <?= selected_if($respondent['respondent_type'], 'Student') ?>>Student</option>
                                            <option value="Employee" <?= selected_if($respondent['respondent_type'], 'Employee') ?>>Employee</option>
                                            <option value="Private Individual" <?= selected_if($respondent['respondent_type'], 'Private Individual') ?>>Private Individual</option>
                                            <option value="Other" <?= selected_if($respondent['respondent_type'], 'Other') ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="field"><label>Full Name <span class="required">*</span></label><input name="respondent_name[]" value="<?= h($respondent['full_name']) ?>" required></div>
                                    <div class="field"><label>Gender</label><select name="respondent_gender[]"><option value="">Select Gender</option><option value="Male" <?= selected_if($respondent['gender'], 'Male') ?>>Male</option><option value="Female" <?= selected_if($respondent['gender'], 'Female') ?>>Female</option></select></div>
                                    <div class="field" data-types="Student"><label>Student No.</label><input name="respondent_student_no[]" value="<?= h($respondent['student_no']) ?>"></div>
                                    <div class="field" data-types="Student">
                                        <label>College</label>
                                        <select name="respondent_college[]">
                                            <option value="">Select College</option>
                                            <?php foreach ($colleges as $college): ?>
                                                <option value="<?= h($college) ?>" <?= selected_if($respondent['college'], $college) ?>><?= h($college) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field" data-types="Student">
                                        <label>Course</label>
                                        <select name="respondent_course[]">
                                            <option value="">Select Course</option>
                                            <?php foreach (Courses::all() as $course): ?>
                                                <option value="<?= h($course) ?>" <?= selected_if(Courses::split($respondent['course_year'])['course'], $course) ?>><?= h($course) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field" data-types="Student">
                                        <label>Section</label>
                                        <select name="respondent_section[]">
                                            <option value="">Select Section</option>
                                            <?php foreach (Courses::sections() as $year => $sections): ?>
                                                <optgroup label="<?= h($year) ?>">
                                                    <?php foreach ($sections as $section): ?>
                                                        <option value="<?= h($section) ?>" <?= selected_if(Courses::split($respondent['course_year'])['section'], $section) ?>><?= h($section) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="respondent_course_year[]" value="<?= h($respondent['course_year']) ?>">
                                    </div>
                                    <div class="field" data-types="Employee"><label>Employee No.</label><input name="respondent_employee_no[]" value="<?= h($respondent['employee_no']) ?>"></div>
                                    <div class="field" data-types="Employee"><label>Department</label><input name="respondent_department[]" value="<?= h($respondent['office_department']) ?>"></div>
                                    <div class="field" data-types="Employee"><label>Position</label><input name="respondent_position[]" value="<?= h($respondent['position']) ?>"></div>
                                    <div class="field" data-types="Private Individual Other"><label>Affiliation</label><input name="respondent_affiliation[]" value="<?= h($respondent['affiliation']) ?>"></div>
                                    <div class="field"><label>Contact Info</label><input name="respondent_contact[]" value="<?= h($respondent['contact_info']) ?>"></div>
                                    <div class="field full"><label>Details</label><textarea name="respondent_details[]"><?= h($respondent['details']) ?></textarea></div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary add-respondent">Add Respondent</button>
                </section>

                <section class="form-section" data-has="has_witnesses" hidden>
                    <h2>Witnesses</h2>
                    <div id="witnessList">
                        <?php foreach ($witnesses as $index => $witness): ?>
                            <div class="dynamic-item witness-item">
                                <div class="form-grid">
                                    <div class="field">
                                        <label>Person Type</label>
                                        <select name="witness_type[]">
                                            <option value="Student" <?= selected_if($witness['person_type'], 'Student') ?>>Student</option>
                                            <option value="Employee" <?= selected_if($witness['person_type'], 'Employee') ?>>Employee</option>
                                            <option value="Private Individual" <?= selected_if($witness['person_type'], 'Private Individual') ?>>Private Individual</option>
                                            <option value="Other" <?= selected_if($witness['person_type'], 'Other') ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="field"><label>Full Name</label><input name="witness_name[]" value="<?= h($witness['full_name']) ?>"></div>
                                    <div class="field"><label>Contact</label><input name="witness_contact[]" value="<?= h($witness['contact_info']) ?>"></div>
                                    <div class="field full"><label>Statement</label><textarea name="witness_statement[]"><?= h($witness['statement']) ?></textarea></div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary add-witness">Add Witness</button>
                </section>

                <section class="form-section" data-has="has_evidence" hidden>
                    <h2>Existing Evidence</h2>
                    <div class="evidence-manage-list">
                        <?php foreach ($evidence as $file): ?>
                            <div class="evidence-manage-item">
                                <label>
                                    <input type="checkbox" name="remove_evidence[]" value="<?= (int) $file['evidence_id'] ?>">
                                    Remove
                                </label>
                                <span><?= h($file['original_filename']) ?></span>
                                <span class="muted">(<?= h(ucfirst($file['doc_type'] ?: 'supporting')) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($evidence)): ?>
                            <p class="muted">No existing evidence.</p>
                        <?php endif; ?>
                    </div>
                    <h2 style="margin-top:18px">Add New Evidence</h2>
                    <div id="newEvidenceList"></div>
                    <button type="button" class="btn btn-secondary" id="addEvidenceBtn">Add Evidence File</button>
                    <input type="file" name="evidence[]" id="evidenceInput" multiple accept=".pdf,.jpg,.jpeg,.png,.docx" data-sicms-size-mb="5" data-sicms-accept-ext="pdf,jpg,jpeg,png,docx" style="display:none">
                </section>

                <section class="form-section" data-has="has_hearings" hidden>
                    <h2>Hearings</h2>
                    <div id="hearingList">
                        <?php foreach ($hearings as $index => $hearing): ?>
                            <div class="dynamic-item hearing-item">
                                <div class="form-grid">
                                    <div class="field"><label>Hearing Date</label><input type="datetime-local" name="hearing_datetime[]" value="<?= h(date('Y-m-d\TH:i', strtotime($hearing['hearing_datetime']))) ?>"></div>
                                    <div class="field"><label>Venue</label><input name="hearing_venue[]" value="<?= h($hearing['venue']) ?>"></div>
                                    <div class="field">
                                        <label>Status</label>
                                        <select name="hearing_status[]">
                                            <option value="Scheduled" <?= selected_if($hearing['status'], 'Scheduled') ?>>Scheduled</option>
                                            <option value="Completed" <?= selected_if($hearing['status'], 'Completed') ?>>Completed</option>
                                            <option value="Cancelled" <?= selected_if($hearing['status'], 'Cancelled') ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="field full"><label>Remarks</label><textarea name="hearing_remarks[]"><?= h($hearing['remarks']) ?></textarea></div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary add-hearing">Add Hearing</button>
                </section>

                <section class="form-section">
                    <h2>Outcome &amp; Resolution</h2>
                    <div class="form-grid">
                        <div class="field"><label>Legacy Outcome</label><input name="legacy_outcome" value="<?= val($case, $old, 'legacy_outcome') ?>"></div>
                        <div class="field"><label>Resolution Date</label><input type="date" name="resolution_date" value="<?= val($case, $old, 'resolution_date') ?>"></div>
                        <div class="field full"><label>Action Taken</label><textarea name="action_taken"><?= val($case, $old, 'action_taken') ?></textarea></div>
                        <div class="field full"><label>Remarks / Notes</label><textarea name="remarks_notes"><?= val($case, $old, 'remarks_notes') ?></textarea></div>
                        <div class="field full"><label>Update Remarks (recorded in timeline)</label><textarea name="update_remarks"></textarea></div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="btn btn-secondary" href="show.php?id=<?= (int) $case['complaint_id'] ?>">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </main>
        </div>
    </div>

    <script>
        const respondentList = document.getElementById('respondentList');
        const witnessList = document.getElementById('witnessList');
        const hearingList = document.getElementById('hearingList');

        const blankRespondent = () => {
            const div = document.createElement('div');
            div.className = 'dynamic-item respondent-item';
            div.innerHTML = `<div class="form-grid">
                <div class="field"><label>Respondent Type *</label><select name="respondent_type[]"><option value="">Select Type</option><option value="Student">Student</option><option value="Employee">Employee</option><option value="Private Individual">Private Individual</option><option value="Other">Other</option></select></div>
                <div class="field"><label>Full Name *</label><input name="respondent_name[]" required></div>
                <div class="field"><label>Gender</label><select name="respondent_gender[]"><option value="">Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                <div class="field"><label>Contact</label><input name="respondent_contact[]"></div>
                <div class="field full"><label>Details</label><textarea name="respondent_details[]"></textarea></div>
            </div><button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>`;
            div.querySelector('.remove-item').addEventListener('click', () => div.remove());
            return div;
        };

        const blankWitness = () => {
            const div = document.createElement('div');
            div.className = 'dynamic-item witness-item';
            div.innerHTML = `<div class="form-grid">
                <div class="field"><label>Person Type</label><select name="witness_type[]"><option value="Student">Student</option><option value="Employee">Employee</option><option value="Private Individual">Private Individual</option><option value="Other">Other</option></select></div>
                <div class="field"><label>Full Name</label><input name="witness_name[]"></div>
                <div class="field"><label>Contact</label><input name="witness_contact[]"></div>
                <div class="field full"><label>Statement</label><textarea name="witness_statement[]"></textarea></div>
            </div><button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>`;
            div.querySelector('.remove-item').addEventListener('click', () => div.remove());
            return div;
        };

        const blankHearing = () => {
            const div = document.createElement('div');
            div.className = 'dynamic-item hearing-item';
            div.innerHTML = `<div class="form-grid">
                <div class="field"><label>Hearing Date</label><input type="datetime-local" name="hearing_datetime[]"></div>
                <div class="field"><label>Venue</label><input name="hearing_venue[]"></div>
                <div class="field"><label>Status</label><select name="hearing_status[]"><option value="Scheduled">Scheduled</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
                <div class="field full"><label>Remarks</label><textarea name="hearing_remarks[]"></textarea></div>
            </div><button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>`;
            div.querySelector('.remove-item').addEventListener('click', () => div.remove());
            return div;
        };

        document.querySelectorAll('.remove-item').forEach((btn) => btn.addEventListener('click', () => btn.closest('.dynamic-item').remove()));
        document.querySelector('.add-respondent').addEventListener('click', () => respondentList.appendChild(blankRespondent()));
        document.querySelector('.add-witness').addEventListener('click', () => witnessList.appendChild(blankWitness()));
        document.querySelector('.add-hearing').addEventListener('click', () => hearingList.appendChild(blankHearing()));

        const evidenceInput = document.getElementById('evidenceInput');
        const newEvidenceList = document.getElementById('newEvidenceList');
        document.getElementById('addEvidenceBtn').addEventListener('click', (e) => { e.preventDefault(); evidenceInput.click(); });
        evidenceInput.addEventListener('change', () => {
            newEvidenceList.replaceChildren();
            Array.from(evidenceInput.files).forEach((file, index) => {
                const row = document.createElement('div');
                row.className = 'evidence-manage-item';
                row.innerHTML = `<span>${file.name}</span>
                    <select name="evidence_doc_type[${index}]">
                        <option value="">Document type</option>
                        <option value="supporting">Supporting</option>
                        <option value="resolution">Resolution</option>
                    </select>`;
                newEvidenceList.appendChild(row);
            });
        });

        const complainantType = document.getElementById('complainantType');
        const applyComplainantType = () => {
            const type = complainantType.value;
            document.querySelectorAll('.complainant-student, .complainant-employee, .complainant-others, .complainant-individual').forEach((f) => {
                const show = f.classList.contains('complainant-' + type.toLowerCase());
                f.style.display = show ? '' : 'none';
                f.querySelectorAll('input, select, textarea').forEach((ctrl) => {
                    ctrl.disabled = !show;
                });
            });
        };
        complainantType.addEventListener('change', applyComplainantType);
        applyComplainantType();

        function syncOptionalSections() {
            document.querySelectorAll('[data-has]').forEach((section) => {
                const key = section.dataset.has;
                const selected = document.querySelector(`input[name="${key}"]:checked`);
                section.hidden = !(selected && selected.value === 'yes');
            });
        }
        function sectionHasContent(key) {
            if (key === 'has_evidence') return document.querySelector('#newEvidenceList .evidence-manage-item') !== null
                || document.querySelector('[data-has="has_evidence"] input[type="checkbox"]') !== null;
            const section = document.querySelector(`[data-has="${key}"]`);
            return section ? section.querySelectorAll('.dynamic-item').length > 0 : false;
        }
        document.querySelectorAll('[name="has_respondents"],[name="has_witnesses"],[name="has_evidence"],[name="has_hearings"]')
            .forEach((radio) => radio.addEventListener('change', (event) => {
                if (radio.value === 'no' && sectionHasContent(radio.name)) {
                    const ok = window.confirm('Switching this section to "No" will remove its saved entries. Continue?');
                    if (!ok) {
                        document.querySelector(`input[name="${radio.name}"][value="yes"]`).checked = true;
                    }
                }
                syncOptionalSections();
            }));
        syncOptionalSections();
    </script>
</body>

</html>
