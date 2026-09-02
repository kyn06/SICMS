<?php
require_once __DIR__ . '/../../controllers/LegacyCaseController.php';
require_once __DIR__ . '/../../../routes.php';
require_once __DIR__ . '/../../helpers/Colleges.php';
require_once __DIR__ . '/../../helpers/Courses.php';
require_once __DIR__ . '/../../helpers/Security.php';

$controller = new LegacyCaseController();
$viewData = $controller->createPage();

$user = $viewData['user'];
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

if (!function_exists('ov')) {
    function ov($old, $key, $default = '') {
        return $old[$key] ?? $default;
    }
}

if (!function_exists('ova')) {
    function ova($old, $key, $index) {
        return $old[$key][$index] ?? '';
    }
}

if (!function_exists('selected_if')) {
    function selected_if($value, $option) {
        return $value === $option ? 'selected' : '';
    }
}

$complainantType = ov($old, 'complainant_type', 'Student');

function type_hidden($types, $current) {
    return in_array($current, array_map('trim', explode(',', (string) $types)), true) ? '' : 'hidden';
}

function type_disabled($types, $current) {
    return in_array($current, array_map('trim', explode(',', (string) $types)), true) ? '' : 'disabled';
}

$respondentTemplate = function () {
    ob_start();
    ?>
    <div class="dynamic-item respondent-item">
        <div class="form-grid">
            <div class="field">
                <label>Respondent Type <span class="required">*</span></label>
                <select name="respondent_type[]" aria-label="Respondent type" required>
                    <option value="">Select Type</option>
                    <option value="Student">Student</option>
                    <option value="Employee">Employee</option>
                    <option value="Private Individual">Private Individual</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="field">
                <label>Full Name <span class="required">*</span></label>
                <input name="respondent_name[]" value="" required>
            </div>
            <div class="field">
                <label>Gender</label>
                <select name="respondent_gender[]">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="field" data-types="Student">
                <label>Student No.</label>
                <input name="respondent_student_no[]" value="">
            </div>
            <div class="field" data-types="Student">
                <label>College</label>
                <select name="respondent_college[]">
                    <option value="">Select College</option>
                    <?php foreach (Colleges::all() as $college): ?>
                        <option value="<?= h($college) ?>"><?= h($college) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-types="Student">
                <label>Course</label>
                <select name="respondent_course[]">
                    <option value="">Select Course</option>
                    <?php foreach (Courses::all() as $course): ?>
                        <option value="<?= h($course) ?>"><?= h($course) ?></option>
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
                                <option value="<?= h($section) ?>"><?= h($section) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="respondent_course_year[]" value="">
            </div>
            <div class="field" data-types="Employee">
                <label>Employee No.</label>
                <input name="respondent_employee_no[]" value="">
            </div>
            <div class="field" data-types="Employee">
                <label>Department</label>
                <input name="respondent_department[]" value="">
            </div>
            <div class="field" data-types="Employee">
                <label>Position</label>
                <input name="respondent_position[]" value="">
            </div>
            <div class="field" data-types="Private Individual Other">
                <label>Affiliation</label>
                <input name="respondent_affiliation[]" value="">
            </div>
            <div class="field">
                <label>Contact Info</label>
                <input name="respondent_contact[]" value="">
            </div>
            <div class="field full">
                <label>Details / Relation to Case</label>
                <textarea name="respondent_details[]"></textarea>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
    </div>
    <?php
    return ob_get_clean();
};

$witnessTemplate = function () {
    ob_start();
    ?>
    <div class="dynamic-item witness-item">
        <div class="form-grid">
            <div class="field">
                <label>Person Type</label>
                <select name="witness_type[]">
                    <option value="Student">Student</option>
                    <option value="Employee">Employee</option>
                    <option value="Private Individual">Private Individual</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="field">
                <label>Full Name</label>
                <input name="witness_name[]" value="">
            </div>
            <div class="field" data-types="Student">
                <label>Student No.</label>
                <input name="witness_student_no[]" value="">
            </div>
            <div class="field" data-types="Student">
                <label>College</label>
                <select name="witness_college[]">
                    <option value="">Select College</option>
                    <?php foreach (Colleges::all() as $college): ?>
                        <option value="<?= h($college) ?>"><?= h($college) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-types="Student">
                <label>Course</label>
                <select name="witness_course[]">
                    <option value="">Select Course</option>
                    <?php foreach (Courses::all() as $course): ?>
                        <option value="<?= h($course) ?>"><?= h($course) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-types="Student">
                <label>Section</label>
                <select name="witness_section[]">
                    <option value="">Select Section</option>
                    <?php foreach (Courses::sections() as $year => $sections): ?>
                        <optgroup label="<?= h($year) ?>">
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= h($section) ?>"><?= h($section) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="witness_course_year[]" value="">
            </div>
            <div class="field">
                <label>Contact Info</label>
                <input name="witness_contact[]" value="">
            </div>
            <div class="field full">
                <label>Statement</label>
                <textarea name="witness_statement[]"></textarea>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
    </div>
    <?php
    return ob_get_clean();
};

$hearingTemplate = function () {
    ob_start();
    ?>
    <div class="dynamic-item hearing-item">
        <div class="form-grid">
            <div class="field">
                <label>Hearing Date &amp; Time</label>
                <input type="datetime-local" name="hearing_datetime[]">
            </div>
            <div class="field">
                <label>Venue</label>
                <input name="hearing_venue[]" value="">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="hearing_status[]">
                    <option value="Scheduled">Scheduled</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            <div class="field full">
                <label>Remarks</label>
                <textarea name="hearing_remarks[]"></textarea>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
    </div>
    <?php
    return ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digitize Migrated Case | SICMS</title>
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
            <?php $pageTitle = 'Digitize Migrated Case'; require __DIR__ . '/../layout/topbar.php'; ?>

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

            <form id="legacyCreateForm" method="POST" action="index.php" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <input type="hidden" name="legacy_action" value="create">

                <section class="form-section optional-ask-section">
                    <h2>Available Case Records</h2>
                    <p class="optional-hint">Some historical records may be incomplete. Select what this case has so we only show the parts you need to fill in.</p>
                    <div class="optional-ask-grid">
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have a respondent?</span>
                            <label class="radio-inline"><input type="radio" name="has_respondents" value="no" checked> No</label>
                            <label class="radio-inline"><input type="radio" name="has_respondents" value="yes"> Yes</label>
                        </div>
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have a witness?</span>
                            <label class="radio-inline"><input type="radio" name="has_witnesses" value="no" checked> No</label>
                            <label class="radio-inline"><input type="radio" name="has_witnesses" value="yes"> Yes</label>
                        </div>
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have supporting evidence?</span>
                            <label class="radio-inline"><input type="radio" name="has_evidence" value="no" checked> No</label>
                            <label class="radio-inline"><input type="radio" name="has_evidence" value="yes"> Yes</label>
                        </div>
                        <div class="optional-ask">
                            <span class="optional-ask-label">Does this case have hearing records?</span>
                            <label class="radio-inline"><input type="radio" name="has_hearings" value="no" checked> No</label>
                            <label class="radio-inline"><input type="radio" name="has_hearings" value="yes"> Yes</label>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2>Case Identification</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label>Original Case Number <span class="required">*</span></label>
                            <input name="case_number" value="<?= h(ov($old, 'case_number')) ?>" required maxlength="50">
                        </div>
                        <div class="field">
                            <label>Original Case Date <span class="optional">(optional)</span></label>
                            <input type="date" name="original_case_date" value="<?= h(ov($old, 'original_case_date')) ?>" placeholder="Defaults to today if unknown">
                        </div>
                        <div class="field">
                            <label>Source Document</label>
                            <input name="legacy_entry_source" value="<?= h(ov($old, 'legacy_entry_source')) ?>" placeholder="e.g. Physical case folder">
                        </div>
                        <div class="field">
                            <label>Case Classification <span class="required">*</span></label>
                            <select name="case_classification" required>
                                <option value="">Select Classification</option>
                                <?php foreach ($classifications as $classification): ?>
                                    <option value="<?= h($classification) ?>" <?= selected_if(ov($old, 'case_classification'), $classification) ?>><?= h($classification) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= h($status) ?>" <?= selected_if(ov($old, 'status', 'Verified'), $status) ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Incident Date &amp; Time</label>
                            <input type="datetime-local" name="incident_datetime" value="<?= h(ov($old, 'incident_datetime')) ?>">
                        </div>
                        <div class="field">
                            <label>Incident Location</label>
                            <input name="incident_location" value="<?= h(ov($old, 'incident_location')) ?>">
                        </div>
                        <div class="field full">
                            <label>Case Description <span class="optional">(optional)</span></label>
                            <textarea name="complaint_details"><?= h(ov($old, 'complaint_details')) ?></textarea>
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
                            <input name="complainant_name" value="<?= h(ov($old, 'complainant_name')) ?>" required>
                        </div>
                        <div class="field">
                            <label>Gender</label>
                            <select name="complainant_gender">
                                <option value="">Select</option>
                                <option value="Male" <?= selected_if(ov($old, 'complainant_gender'), 'Male') ?>>Male</option>
                                <option value="Female" <?= selected_if(ov($old, 'complainant_gender'), 'Female') ?>>Female</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input name="complainant_email" value="<?= h(ov($old, 'complainant_email')) ?>">
                        </div>
                        <div class="field">
                            <label>Contact Number</label>
                            <input name="complainant_contact" value="<?= h(ov($old, 'complainant_contact')) ?>">
                        </div>
                        <div class="field complainant-student">
                            <label>Student Number</label>
                            <input name="complainant_student_no" value="<?= h(ov($old, 'complainant_student_no')) ?>">
                        </div>
                        <div class="field complainant-student">
                            <label>College</label>
                            <select name="complainant_college">
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $college): ?>
                                    <option value="<?= h($college) ?>" <?= selected_if(ov($old, 'complainant_college'), $college) ?>><?= h($college) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field complainant-student">
                            <label>Course</label>
                            <select name="complainant_course" id="complainantCourse">
                                <option value="">Select Course</option>
                                <?php foreach (Courses::all() as $course): ?>
                                    <option value="<?= h($course) ?>" <?= selected_if(ov($old, 'complainant_course'), $course) ?>><?= h($course) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field complainant-student">
                            <label>Section</label>
                            <select name="complainant_section" id="complainantSection">
                                <option value="">Select Section</option>
                                <?php foreach (Courses::sections() as $year => $sections): ?>
                                    <optgroup label="<?= h($year) ?>">
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= h($section) ?>" <?= selected_if(ov($old, 'complainant_section'), $section) ?>><?= h($section) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="complainant_course_year" value="<?= h(ov($old, 'complainant_course_year')) ?>">
                        </div>
                        <div class="field complainant-employee">
                            <label>Employee Number</label>
                            <input name="complainant_employee_no" value="<?= h(ov($old, 'complainant_employee_no')) ?>">
                        </div>
                        <div class="field complainant-employee">
                            <label>Department</label>
                            <input name="complainant_department" value="<?= h(ov($old, 'complainant_department')) ?>">
                        </div>
                        <div class="field complainant-employee">
                            <label>Position</label>
                            <input name="complainant_position" value="<?= h(ov($old, 'complainant_position')) ?>">
                        </div>
                        <div class="field complainant-others">
                            <label>Affiliation</label>
                            <input name="complainant_affiliation" value="<?= h(ov($old, 'complainant_affiliation')) ?>">
                        </div>
                        <div class="field complainant-others">
                            <label>Purpose</label>
                            <input name="complainant_purpose" value="<?= h(ov($old, 'complainant_purpose')) ?>">
                        </div>
                        <div class="field complainant-individual">
                            <label>Relationship</label>
                            <input name="complainant_relationship" value="<?= h(ov($old, 'complainant_relationship')) ?>">
                        </div>
                    </div>
                </section>

                <section class="form-section" data-has="has_respondents" hidden>
                    <h2>Respondents</h2>
                    <div id="respondentList"></div>
                    <button type="button" class="btn btn-secondary add-item" data-template="respondent">Add Respondent</button>
                </section>

                <section class="form-section" data-has="has_witnesses" hidden>
                    <h2>Witnesses</h2>
                    <div id="witnessList"></div>
                    <button type="button" class="btn btn-secondary add-item" data-template="witness">Add Witness</button>
                </section>

                <section class="form-section" data-has="has_evidence" hidden>
                    <h2>Evidence / Attachments</h2>
                    <div id="evidenceList" class="evidence-list"></div>
                    <div class="evidence-file-row">
                        <input type="file" name="evidence[]" id="evidenceInput" multiple>
                        <span id="evidenceFileNames"></span>
                        <button type="button" class="btn btn-secondary add-evidence" id="addEvidenceBtn">Add Evidence File</button>
                    </div>
                </section>

                <section class="form-section" data-has="has_hearings" hidden>
                    <h2>Hearings</h2>
                    <div id="hearingList"></div>
                    <button type="button" class="btn btn-secondary add-item" data-template="hearing">Add Hearing</button>
                </section>

                <section class="form-section">
                    <h2>Outcome &amp; Resolution</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label>Legacy Outcome</label>
                            <input name="legacy_outcome" value="<?= h(ov($old, 'legacy_outcome')) ?>">
                        </div>
                        <div class="field">
                            <label>Resolution Date</label>
                            <input type="date" name="resolution_date" value="<?= h(ov($old, 'resolution_date')) ?>">
                        </div>
                        <div class="field full">
                            <label>Action Taken</label>
                            <textarea name="action_taken"><?= h(ov($old, 'action_taken')) ?></textarea>
                        </div>
                        <div class="field full">
                            <label>Remarks / Notes</label>
                            <textarea name="remarks_notes"><?= h(ov($old, 'remarks_notes')) ?></textarea>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a class="btn btn-secondary" href="<?= h(app_route('cases.index')) ?>">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save Migrated Case</button>
                </div>
            </form>
        </main>
        </div>
    </div>

    <script>
        const templates = {
            respondent: <?= json_encode($respondentTemplate()) ?>,
            witness: <?= json_encode($witnessTemplate()) ?>,
            hearing: <?= json_encode($hearingTemplate()) ?>
        };

        const respondentList = document.getElementById('respondentList');
        const witnessList = document.getElementById('witnessList');
        const hearingList = document.getElementById('hearingList');

        document.querySelectorAll('.add-item').forEach((btn) => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.template === 'respondent' ? respondentList
                    : btn.dataset.template === 'witness' ? witnessList : hearingList;
                const wrapper = document.createElement('div');
                wrapper.innerHTML = templates[btn.dataset.template];
                target.appendChild(wrapper.firstElementChild);
                bindTypeVisibility(wrapper.firstElementChild);
            });
        });

        function bindTypeVisibility(item) {
            item.querySelectorAll('[data-types]').forEach((field) => {
                const typeSelect = findTypeSelect(item);
                const apply = () => {
                    const types = field.dataset.types.split(' ');
                    const visible = types.includes(typeSelect.value);
                    field.style.display = visible ? '' : 'none';
                    const controls = field.querySelectorAll('input, select, textarea');
                    controls.forEach((ctrl) => {
                        ctrl.disabled = !visible;
                        if (!visible) ctrl.value = '';
                    });
                    if (field.querySelector('input[type="hidden"]')) field.querySelector('input[type="hidden"]').disabled = false;
                };
                typeSelect.addEventListener('change', apply);
                if (typeSelect.value) apply();
            });
            item.querySelector('.remove-item')?.addEventListener('click', () => item.remove());
        }

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-item');
            if (btn) btn.closest('.dynamic-item')?.remove();
        });

        function findTypeSelect(item) {
            return item.querySelector('select[name="respondent_type[]"], select[name="witness_type[]"]');
        }

        const evidenceInput = document.getElementById('evidenceInput');
        const evidenceFileNames = document.getElementById('evidenceFileNames');
        const evidenceList = document.getElementById('evidenceList');
        let evidenceCount = 0;

        function renderEvidence() {
            const files = Array.from(evidenceInput.files);
            evidenceList.replaceChildren();
            evidenceFileNames.textContent = files.map((f) => f.name).join(', ');
            files.forEach((file, index) => {
                const row = document.createElement('div');
                row.className = 'evidence-item';
                row.innerHTML = `
                    <span class="evidence-name">${file.name}</span>
                    <input type="hidden" name="evidence_doc_type[${index}]" value="">
                    <select data-idx="${index}" class="evidence-doc-type">
                        <option value="">Document type</option>
                        <option value="supporting">Supporting</option>
                        <option value="resolution">Resolution</option>
                    </select>
                `;
                evidenceList.appendChild(row);
            });
        }

        evidenceList.addEventListener('change', (e) => {
            if (e.target.classList.contains('evidence-doc-type')) {
                const row = e.target.closest('.evidence-item');
                const idx = e.target.dataset.idx;
                row.querySelector('input[type="hidden"]').value = e.target.value;
            }
        });

        document.getElementById('addEvidenceBtn').addEventListener('click', (e) => {
            e.preventDefault();
            evidenceInput.click();
        });
        evidenceInput.addEventListener('change', renderEvidence);
        renderEvidence();

        const complainantType = document.getElementById('complainantType');
        const courseSelect = document.getElementById('complainantCourse');
        const sectionSelect = document.getElementById('complainantSection');
        const courseYearInput = document.querySelector('input[name="complainant_course_year"]');

        function syncCourseYear() {
            if (courseSelect.value && sectionSelect.value) courseYearInput.value = courseSelect.value + ' | ' + sectionSelect.value;
        }
        courseSelect.addEventListener('change', syncCourseYear);
        sectionSelect.addEventListener('change', syncCourseYear);

        function applyComplainantType() {
            const type = complainantType.value;
            document.querySelectorAll('.complainant-student, .complainant-employee, .complainant-others, .complainant-individual').forEach((f) => {
                const show = f.classList.contains('complainant-' + type.toLowerCase());
                f.style.display = show ? '' : 'none';
                f.querySelectorAll('input, select, textarea').forEach((ctrl) => {
                    if (ctrl.name === 'complainant_course_year') return;
                    ctrl.disabled = !show;
                    if (!show) ctrl.value = '';
                });
            });
        }
        complainantType.addEventListener('change', applyComplainantType);
        applyComplainantType();

        function syncOptionalSections() {
            document.querySelectorAll('[data-has]').forEach((section) => {
                const key = section.dataset.has;
                const selected = document.querySelector(`input[name="${key}"]:checked`);
                const show = selected && selected.value === 'yes';
                section.hidden = !show;
            });
        }
        document.querySelectorAll('[name="has_respondents"],[name="has_witnesses"],[name="has_evidence"],[name="has_hearings"]')
            .forEach((radio) => radio.addEventListener('change', syncOptionalSections));
        syncOptionalSections();
    </script>
</body>

</html>
