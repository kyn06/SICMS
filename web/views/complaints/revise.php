<?php
require_once __DIR__ . '/../../controllers/ComplaintController.php';
require_once __DIR__ . '/../../helpers/Courses.php';

$controller = new ComplaintController();
$viewData = $controller->handleRevisionRequest((int) ($_GET['id'] ?? 0));

$user = $viewData['user'];
$case = $viewData['case'];
$revision = $viewData['revision'];
$respondents = $viewData['respondents'];
$witnesses = $viewData['witnesses'];
$evidence = $viewData['evidence'];
$errors = $viewData['errors'];
$fieldErrors = $viewData['fieldErrors'] ?? [];
$old = $viewData['old'];

$allowed = array_values(array_intersect(
    ['complaint_details', 'incident_date', 'incident_time', 'incident_location', 'respondents', 'witnesses', 'evidence'],
    (array) ($revision['revision_fields'] ?? [])
));

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function field_error_html($fieldErrors, $field) {
    $message = $fieldErrors[$field] ?? '';
    return $message !== '' ? '<div class="field-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';
}

function editable($field, $allowed) {
    return in_array($field, $allowed, true);
}

function field_class($field, $allowed) {
    return editable($field, $allowed) ? 'needs-revision' : '';
}

$oldIncident = !empty($old['incident_datetime']) ? strtotime($old['incident_datetime']) : strtotime($case['incident_datetime'] ?? '');
$oldDate = $oldIncident ? date('Y-m-d', $oldIncident) : '';
$oldTime = $oldIncident ? date('H:i', $oldIncident) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revise Complaint | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
</head>

<body>
    <div class="app-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>

        <div class="main-area">
            <?php $pageTitle = 'Revise Complaint'; require __DIR__ . '/../layout/topbar.php'; ?>

            <main class="content">

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($revision['remarks'])): ?>
                    <div class="revision-remarks">
                        <h3>SDRU Remarks</h3>
                        <p><?= nl2br(h($revision['remarks'])) ?></p>
                    </div>
                <?php endif; ?>

                <form id="revisionForm" method="post" enctype="multipart/form-data" data-sicms-validate>
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="revision_fields" value="<?= h(implode(',', $allowed)) ?>">

                    <section class="revision-card <?= field_class('complaint_details', $allowed) ?>">
                        <div class="field-head">
                            <h2>Complaint Details</h2>
                            <?php if (editable('complaint_details', $allowed)): ?>
                                <span class="needs-badge">Needs Revision</span>
                            <?php endif; ?>
                        </div>
                        <div class="field">
                            <label>Complaint Description <span class="required">*</span></label>
                            <textarea
                                name="complaint_details"
                                rows="6"
                                <?= editable('complaint_details', $allowed) ? '' : 'readonly' ?>
                                <?= editable('complaint_details', $allowed) ? 'required' : '' ?>
                            ><?= h($old['complaint_details'] ?? $case['complaint_details'] ?? '') ?></textarea>
                            <?= field_error_html($fieldErrors, 'complaint_details') ?>
                        </div>
                    </section>

                    <section class="revision-card <?= field_class('incident_date', $allowed) ?> <?= field_class('incident_time', $allowed) ?> <?= field_class('incident_location', $allowed) ?>">
                        <div class="field-head">
                            <h2>Incident Information</h2>
                            <?php if (editable('incident_date', $allowed) || editable('incident_time', $allowed) || editable('incident_location', $allowed)): ?>
                                <span class="needs-badge">Needs Revision</span>
                            <?php endif; ?>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label>Incident Date <span class="required">*</span></label>
                                <input
                                    type="date"
                                    name="incident_date"
                                    value="<?= h($old['incident_date'] ?? $oldDate) ?>"
                                    <?= editable('incident_date', $allowed) ? '' : 'readonly' ?>
                                    <?= editable('incident_date', $allowed) ? 'required' : '' ?>
                                    <?= editable('incident_date', $allowed) ? ' max="' . h(date('Y-m-d')) . '" data-sicms-future="0"' : '' ?>
                                >
                                <?= field_error_html($fieldErrors, 'incident_date') ?>
                            </div>
                            <div class="field">
                                <label>Incident Time <span class="required">*</span></label>
                                <input
                                    type="time"
                                    name="incident_time"
                                    value="<?= h($old['incident_time'] ?? $oldTime) ?>"
                                    <?= editable('incident_time', $allowed) ? '' : 'readonly' ?>
                                    <?= editable('incident_time', $allowed) ? 'required' : '' ?>
                                >
                                <?= field_error_html($fieldErrors, 'incident_time') ?>
                            </div>
                            <div class="field full">
                                <label>Incident Location <span class="required">*</span></label>
                                <input
                                    name="incident_location"
                                    value="<?= h($old['incident_location'] ?? $case['incident_location'] ?? '') ?>"
                                    <?= editable('incident_location', $allowed) ? '' : 'readonly' ?>
                                    <?= editable('incident_location', $allowed) ? 'required' : '' ?>
                                >
                                <?= field_error_html($fieldErrors, 'incident_location') ?>
                            </div>
                        </div>
                    </section>

                    <section class="revision-card <?= field_class('respondents', $allowed) ?>">

                        <div class="field-head">

                            <h2>Respondent Information</h2>

                            <?php if (editable('respondents', $allowed)): ?>
                                <span class="needs-badge">
                                    Needs Revision
                                </span>
                            <?php endif; ?>

                        </div>

                        <?php if (editable('respondents', $allowed)): ?>

                            <label class="unknown-toggle">

                                <input
                                    type="checkbox"
                                    name="respondent_unknown"
                                    value="1"
                                    onchange="toggleUnknown('respondents', this)"
                                    <?= !empty($old['respondent_unknown']) ? 'checked' : '' ?>
                                >

                                <span>I don't know the respondent</span>

                            </label>

                        <?php endif; ?>

                        <div class="repeat-list" id="respondents">

                            <?php foreach ($respondents as $i => $person): ?>
                                <?php
                                $personType = trim((string) ($person['respondent_type'] ?? ''));
                                if (!in_array($personType, ['Student', 'Employee', 'Private Individual', 'Other'], true)) $personType = 'Student';
                                $program = Courses::split($person['course_year'] ?? '');
                                $rCourse = trim((string) ($person['course'] ?? $program['course']));
                                $rSection = trim((string) ($person['section'] ?? $program['section']));
                                ?>

                                <div class="repeat-item respondent-repeat-item">

                                    <div class="form-grid">

                                        <div class="field full">
                                            <label>Respondent Type <span class="required">*</span></label>
                                            <select
                                                name="respondent_type[]"
                                                <?= editable('respondents', $allowed) ? '' : 'disabled' ?>
                                                <?= editable('respondents', $allowed) ? 'required' : '' ?>
                                            >
                                                <option value="">Select Respondent Type</option>
                                                <option value="Student" <?= $personType === 'Student' ? 'selected' : '' ?>>Student</option>
                                                <option value="Employee" <?= $personType === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                                <option value="Private Individual" <?= $personType === 'Private Individual' ? 'selected' : '' ?>>Private Individual</option>
                                                <option value="Other" <?= $personType === 'Other' ? 'selected' : '' ?>>Other</option>
                                            </select>
                                        </div>

                                        <div class="field">
                                            <label>Full Name <span class="required">*</span></label>
                                            <input
                                                name="respondent_name[]"
                                                value="<?= h($person['full_name'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? 'required' : 'readonly' ?>
                                            >
                                        </div>

                                        <div class="field">
                                            <label>Age <span class="optional">if applicable</span></label>
                                            <input
                                                type="number"
                                                name="respondent_age[]"
                                                min="1"
                                                max="120"
                                                value="<?= h($person['age'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                            >
                                        </div>

                                        <div class="field">
                                            <label>Gender <span class="optional">if applicable</span></label>
                                            <select
                                                name="respondent_gender[]"
                                                <?= editable('respondents', $allowed) ? '' : 'disabled' ?>
                                            >
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?= ($person['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                <option value="Female" <?= ($person['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                            </select>
                                        </div>

                                        <div class="field" data-respondent-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>
                                            <label>Student Number <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_student_no[]"
                                                value="<?= h($person['student_no'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field" data-respondent-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>
                                            <label>College <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_college[]"
                                                value="<?= h($person['college'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field" data-respondent-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>
                                            <label>Course/Program <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_course[]"
                                                value="<?= h($rCourse) ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field" data-respondent-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>
                                            <label>Section <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_section[]"
                                                value="<?= h($rSection) ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >
                                            <input type="hidden" name="respondent_course_year[]" value="<?= h($person['course_year'] ?? '') ?>">
                                        </div>

                                        <div class="field" data-respondent-types="Employee" <?= $personType === 'Employee' ? '' : 'hidden' ?>>
                                            <label>Employee Number <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_employee_no[]"
                                                value="<?= h($person['employee_no'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Employee' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field" data-respondent-types="Employee" <?= $personType === 'Employee' ? '' : 'hidden' ?>>
                                            <label>Position <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_position[]"
                                                value="<?= h($person['position'] ?? '') ?>"
                                                placeholder="Example: Instructor, Administrative Assistant, Security Officer"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Employee' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field" data-respondent-types="Employee" <?= $personType === 'Employee' ? '' : 'hidden' ?>>
                                            <label>College/Office/Department <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_department[]"
                                                value="<?= h($person['office_department'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Employee' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field" data-respondent-types="Other" <?= $personType === 'Other' ? '' : 'hidden' ?>>
                                            <label>Affiliation/Organization <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_affiliation[]"
                                                value="<?= h($person['affiliation'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Other' ? '' : 'disabled' ?>
                                            >
                                        </div>

                                        <div class="field">
                                            <label>Contact Number <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_contact[]"
                                                value="<?= h($person['contact_info'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                            >
                                        </div>

                                        <div class="field">
                                            <label>Email <span class="optional">if applicable</span></label>
                                            <input
                                                type="email"
                                                name="respondent_email[]"
                                                value="<?= h($person['email'] ?? '') ?>"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                            >
                                        </div>

                                        <div class="field">
                                            <label>Address <span class="optional">if applicable</span></label>
                                            <input
                                                name="respondent_address[]"
                                                value="<?= h($person['address'] ?? '') ?>"
                                                placeholder="Example: Barangay, City/Municipality"
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                            >
                                        </div>

                                        <div class="field">
                                            <label>Details</label>
                                            <input
                                                name="respondent_details[]"
                                                value="<?= h($person['details'] ?? '') ?>"
                                                placeholder="Example: relationship, social media, or other relevant details."
                                                <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                            >
                                        </div>

                                    </div>

                                    <?php if (editable('respondents', $allowed)): ?>

                                        <button
                                            class="btn btn-secondary remove-person"
                                            type="button"
                                        >
                                            Remove Respondent
                                        </button>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <?php if (editable('respondents', $allowed)): ?>

                            <button
                                class="btn btn-secondary add-person"
                                data-list="respondents"
                                type="button"
                            >
                                Add Respondent
                            </button>

                        <?php endif; ?>

                    </section>

                    <section class="revision-card <?= field_class('witnesses', $allowed) ?>">

                        <div class="field-head">

                            <h2>Witness Information</h2>

                            <?php if (editable('witnesses', $allowed)): ?>
                                <span class="needs-badge">
                                    Needs Revision
                                </span>
                            <?php endif; ?>

                        </div>

                        <?php if (editable('witnesses', $allowed)): ?>

                            <label class="unknown-toggle">

                                <input
                                    type="checkbox"
                                    name="witness_none"
                                    value="1"
                                    onchange="toggleUnknown('witnesses', this)"
                                    <?= !empty($old['witness_none']) ? 'checked' : '' ?>
                                >

                                <span>There is no witness / I don't know the witness</span>

                            </label>

                        <?php endif; ?>

                        <div class="repeat-list" id="witnesses">

                            <?php foreach ($witnesses as $person): ?>

                                <?php
                                $personType = ($person['person_type'] ?? '') ?: 'Student';
                                $program = Courses::split($person['course_year'] ?? '');
                                $witnessCourse = trim((string) ($person['course'] ?? $program['course']));
                                $witnessSection = trim((string) ($person['section'] ?? $program['section']));
                                ?>

                                <div class="repeat-item witness-repeat-item">

                                    <div class="form-grid">

                                        <div class="field full">

                                            <label>Witness Type <span class="required">*</span></label>

                                            <select
                                                name="witness_type[]"
                                                <?= editable('witnesses', $allowed) ? 'required' : 'disabled' ?>
                                            >
                                                <option value="">Select Witness Type</option>
                                                <option value="Student" <?= $personType === 'Student' ? 'selected' : '' ?>>Student</option>
                                                <option value="Employee" <?= $personType === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                                <option value="Private Individual" <?= $personType === 'Private Individual' ? 'selected' : '' ?>>Private Individual</option>
                                                <option value="Other" <?= $personType === 'Other' ? 'selected' : '' ?>>Other</option>
                                            </select>

                                        </div>

                                        <div class="field">

                                            <label>Full Name <span class="required">*</span></label>

                                            <input
                                                name="witness_name[]"
                                                value="<?= h($person['full_name'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? 'required' : 'readonly' ?>
                                            >

                                        </div>

                                        <div class="field">

                                            <label>Age <span class="optional">if applicable</span></label>

                                            <input
                                                type="number"
                                                name="witness_age[]"
                                                min="1"
                                                max="120"
                                                value="<?= h($person['age'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                            >

                                        </div>

                                        <div class="field">

                                            <label>Gender <span class="optional">if applicable</span></label>

                                            <select
                                                name="witness_gender[]"
                                                <?= editable('witnesses', $allowed) ? '' : 'disabled' ?>
                                            >
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?= ($person['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                <option value="Female" <?= ($person['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                            </select>

                                        </div>

                                        <div class="field" data-witness-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>

                                            <label>Student Number <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_student_no[]"
                                                value="<?= h($person['student_no'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>

                                            <label>College <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_college[]"
                                                value="<?= h($person['college'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>

                                            <label>Course/Program <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_course[]"
                                                value="<?= h($witnessCourse) ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Student" <?= $personType === 'Student' ? '' : 'hidden' ?>>

                                            <label>Section <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_section[]"
                                                value="<?= h($witnessSection) ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Student' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Employee" <?= $personType === 'Employee' ? '' : 'hidden' ?>>

                                            <label>Employee Number <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_employee_no[]"
                                                value="<?= h($person['employee_no'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Employee' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Employee" <?= $personType === 'Employee' ? '' : 'hidden' ?>>

                                            <label>Position <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_position[]"
                                                value="<?= h($person['position'] ?? '') ?>"
                                                placeholder="Example: Instructor, Administrative Assistant, Security Officer"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Employee' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Employee" <?= $personType === 'Employee' ? '' : 'hidden' ?>>

                                            <label>College/Office or Department <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_department[]"
                                                value="<?= h($person['office_department'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Employee' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field" data-witness-types="Other" <?= $personType === 'Other' ? '' : 'hidden' ?>>

                                            <label>Affiliation/Organization <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_affiliation[]"
                                                value="<?= h($person['affiliation'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                <?= $personType === 'Other' ? '' : 'disabled' ?>
                                            >

                                        </div>

                                        <div class="field">

                                            <label>Contact Number <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_contact[]"
                                                value="<?= h($person['contact_info'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                            >

                                        </div>

                                        <div class="field">

                                            <label>Email <span class="optional">if applicable</span></label>

                                            <input
                                                type="email"
                                                name="witness_email[]"
                                                value="<?= h($person['email'] ?? '') ?>"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                            >

                                        </div>

                                        <div class="field">

                                            <label>Address <span class="optional">if applicable</span></label>

                                            <input
                                                name="witness_address[]"
                                                value="<?= h($person['address'] ?? '') ?>"
                                                placeholder="Example: Barangay, City/Municipality"
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                            >

                                        </div>

                                        <div class="field">

                                            <label>Statement</label>

                                            <input
                                                name="witness_statement[]"
                                                value="<?= h($person['statement'] ?? '') ?>"
                                                placeholder="Example: explain what the witness saw/heard during the incident."
                                                <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                            >

                                        </div>

                                    </div>

                                    <?php if (editable('witnesses', $allowed)): ?>

                                        <button
                                            class="btn btn-secondary remove-person"
                                            type="button"
                                        >
                                            Remove Witness
                                        </button>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <?php if (editable('witnesses', $allowed)): ?>

                            <button
                                class="btn btn-secondary add-person"
                                data-list="witnesses"
                                type="button"
                            >
                                Add Witness
                            </button>

                        <?php endif; ?>

                    </section>

                    <section class="revision-card <?= field_class('evidence', $allowed) ?>">

                        <div class="field-head">

                            <h2>Supporting Evidence</h2>

                            <?php if (editable('evidence', $allowed)): ?>
                                <span class="needs-badge">
                                    Needs Revision
                                </span>
                            <?php endif; ?>

                        </div>

                        <?php if (editable('evidence', $allowed)): ?>

                            <div class="existing-evidence">

                                <?php foreach ($evidence as $file): ?>

                                    <div class="evidence-row">

                                        <span>
                                            <?= h($file['original_filename']) ?> (<?= h(number_format($file['file_size'] / 1024, 1)) ?> KB)
                                        </span>

                                        <label class="remove-evidence">

                                            <input
                                                type="checkbox"
                                                name="remove_evidence[]"
                                                value="<?= (int) $file['evidence_id'] ?>"
                                            >

                                            <span>Remove</span>

                                        </label>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <div class="field">

                                <label>Add More Files</label>

                                <input
                                    type="file"
                                    name="evidence[]"
                                    accept=".pdf,.jpg,.jpeg,.png,.docx"
                                    multiple
                                >

                            </div>

                        <?php else: ?>

                            <div class="field">

                                <label>Files</label>

                                <ul>

                                    <?php foreach ($evidence as $file): ?>

                                        <li><?= h($file['original_filename']) ?></li>

                                    <?php endforeach; ?>

                                </ul>

                            </div>

                        <?php endif; ?>

                    </section>

                    <section class="revision-card">

                        <label class="confirm-row">

                            <input
                                type="checkbox"
                                name="revision_confirmation"
                                value="1"
                                required
                            >

                            <span>
                                I have reviewed the SDRU remarks and updated
                                all required sections.
                            </span>

                        </label>

                        <div class="submit-row">

                            <button
                                class="btn btn-primary"
                                id="submitRevision"
                                type="submit"
                            >
                                <i class="bi bi-send-check"></i>
                                Submit Revised Complaint
                            </button>

                        </div>

                    </section>

                </form>

            </main>

        </div>

    </div>

    <script>
        const form = document.getElementById('revisionForm');
        const submit = document.getElementById('submitRevision');

        const personFieldSets = {
            respondents: [
                ['type', 'Respondent Type', 'required', 'respondentType'],
                ['name', 'Full Name', 'required', 'text'],
                ['age', 'Age', '', 'number'],
                ['gender', 'Gender', '', 'select'],
                ['student_no', 'Student Number', '', 'text', 'Student'],
                ['college', 'College', '', 'text', 'Student'],
                ['course', 'Course/Program', '', 'text', 'Student'],
                ['section', 'Section', '', 'text', 'Student'],
                ['employee_no', 'Employee Number', '', 'text', 'Employee'],
                ['position', 'Position', '', 'text', 'Employee'],
                ['department', 'College/Office/Department', '', 'text', 'Employee'],
                ['affiliation', 'Affiliation/Organization', '', 'text', 'Other'],
                ['contact', 'Contact Number', '', 'text'],
                ['email', 'Email', '', 'email'],
                ['address', 'Address', '', 'text'],
                ['details', 'Details', '']
            ],

            witnesses: [
                ['type', 'Witness Type', 'required', 'witnessType'],
                ['name', 'Full Name', 'required', 'text'],
                ['age', 'Age', '', 'number'],
                ['gender', 'Gender', '', 'select'],
                ['student_no', 'Student Number', '', 'text', 'Student'],
                ['college', 'College', '', 'text', 'Student'],
                ['course', 'Course/Program', '', 'text', 'Student'],
                ['section', 'Section', '', 'text', 'Student'],
                ['employee_no', 'Employee Number', '', 'text', 'Employee'],
                ['position', 'Position', '', 'text', 'Employee'],
                ['department', 'College/Office or Department', '', 'text', 'Employee'],
                ['affiliation', 'Affiliation/Organization', '', 'text', 'Other'],
                ['contact', 'Contact Number', '', 'text'],
                ['email', 'Email', '', 'email'],
                ['address', 'Address', '', 'text'],
                ['statement', 'Statement', '']
            ]
        };

        const personRemoveLabels = {
            respondents: 'Remove Respondent',
            witnesses: 'Remove Witness'
        };

        function buildPersonItem(type) {
            const fields = personFieldSets[type] || [];
            const isWitness = type === 'witnesses';
            const prefix = isWitness ? 'witness' : 'respondent';

            return `
                <div class="repeat-item ${isWitness ? 'witness-repeat-item' : ''}">
                    <div class="form-grid">
                        ${fields
                            .map(([key, label, rule, controlType, typeNames]) => {
                                const typeAttr = typeNames
                                    ? (isWitness ? ` data-witness-types="${typeNames}"` : ` data-respondent-types="${typeNames}"`)
                                    : '';
                                let control;

                                if (controlType === 'select') {
                                    control = `
                                        <select name="${prefix}_${key}[]">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    `;
                                } else if (controlType === 'respondentType') {
                                    control = `
                                        <select name="respondent_type[]" required>
                                            <option value="">Select Respondent Type</option>
                                            <option value="Student">Student</option>
                                            <option value="Employee">Employee</option>
                                            <option value="Private Individual">Private Individual</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    `;
                                } else if (controlType === 'witnessType') {
                                    control = `
                                        <select name="witness_type[]" required>
                                            <option value="">Select Witness Type</option>
                                            <option value="Student" selected>Student</option>
                                            <option value="Employee">Employee</option>
                                            <option value="Private Individual">Private Individual</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    `;
                                } else {
                                    const inputType = ['number', 'date', 'email'].includes(controlType) ? controlType : 'text';
                                    const placeholder = (type === 'respondents' && key === 'details')
                                        ? ' placeholder="Example: relationship, social media, or other relevant details."'
                                        : (type === 'witnesses' && key === 'statement')
                                            ? ' placeholder="Example: explain what the witness saw/heard during the incident."'
                                            : (key === 'address')
                                                ? ' placeholder="Example: Barangay, City/Municipality"'
                                                : (key === 'position')
                                                    ? ' placeholder="Example: Instructor, Administrative Assistant, Security Officer"'
                                                    : '';
                                    const constraints = inputType === 'number'
                                        ? ' min="1" max="120"'
                                        : inputType === 'date'
                                            ? ` max="${new Date().toISOString().slice(0, 10)}"`
                                            : '';
                                    control = `<input type="${inputType}" name="${prefix}_${key}[]"${placeholder}${constraints} ${rule === 'required' ? 'required' : ''}>`;
                                }

                                return `
                                    <div class="field${key === 'type' ? ' full' : ''}"${typeAttr}>
                                        <label>${label}</label>
                                        ${control}
                                    </div>
                                `;
                            })
                            .join('')}

                        <button
                            class="btn btn-secondary remove-person"
                            type="button"
                        >
                            ${personRemoveLabels[type]}
                        </button>
                    </div>
                </div>
            `;
        }

        document.querySelectorAll('.add-person').forEach(button => {
            button.addEventListener('click', () => {
                const list = document.getElementById(button.dataset.list);
                const template = list.querySelector('.repeat-item');

                let item;

                if (template) {
                    item = template.cloneNode(true);

                    item.querySelectorAll('input, select').forEach(control => {
                        control.value = '';
                    });
                } else {
                    item = document.createElement('div');
                    item.innerHTML = buildPersonItem(button.dataset.list);
                    item = item.firstElementChild;
                }

                list.appendChild(item);

                if (button.dataset.list === 'witnesses') {
                    updateWitnessFields(item);
                } else if (button.dataset.list === 'respondents') {
                    updateRespondentFields(item);
                }
            });
        });

        function updateWitnessFields(item) {
            if (!item) return;
            const typeSelect = item.querySelector('[name="witness_type[]"]');
            const type = (typeSelect && typeSelect.value) ? typeSelect.value : 'Student';
            const requiredByType = {
                Student: [],
                Employee: [],
                'Private Individual': [],
                Other: []
            };
            const requiredNames = requiredByType[type] || [];
            item.querySelectorAll('[data-witness-types]').forEach(field => {
                const visible = field.dataset.witnessTypes.split(',').includes(type);
                field.hidden = !visible;
                field.querySelectorAll('input, select').forEach(control => {
                    if (control.dataset.reviseTypeRequired === undefined) {
                        control.dataset.reviseTypeRequired = control.required ? '1' : '0';
                    }
                    control.disabled = !visible;
                    control.required = visible && requiredNames.includes(control.name);
                });
            });
        }

        document.querySelectorAll('#witnesses').forEach(list => {
            list.addEventListener('change', event => {
                if (event.target.matches('[name="witness_type[]"]')) {
                    updateWitnessFields(event.target.closest('.repeat-item'));
                }
            });
        });

        document.querySelectorAll('#witnesses .repeat-item').forEach(updateWitnessFields);

        function updateRespondentFields(item) {
            if (!item) return;
            const typeSelect = item.querySelector('[name="respondent_type[]"]');
            const type = (typeSelect && typeSelect.value) ? typeSelect.value : 'Student';
            const requiredByType = {
                Student: [],
                Employee: [],
                'Private Individual': [],
                Other: []
            };
            const requiredNames = requiredByType[type] || [];
            item.querySelectorAll('[data-respondent-types]').forEach(field => {
                const visible = field.dataset.respondentTypes.split(',').includes(type);
                field.hidden = !visible;
                field.querySelectorAll('input, select').forEach(control => {
                    if (control.dataset.reviseTypeRequired === undefined) {
                        control.dataset.reviseTypeRequired = control.required ? '1' : '0';
                    }
                    control.disabled = !visible;
                    control.required = visible && requiredNames.includes(control.name);
                });
            });
        }

        document.querySelectorAll('#respondents').forEach(list => {
            list.addEventListener('change', event => {
                if (event.target.matches('[name="respondent_type[]"]')) {
                    updateRespondentFields(event.target.closest('.repeat-item'));
                }
            });
        });

        document.querySelectorAll('#respondents .repeat-item').forEach(updateRespondentFields);

        document.addEventListener('click', event => {
            const button = event.target.closest('.remove-person');

            if (!button) {
                return;
            }

            button.closest('.repeat-item').remove();
        });

        function toggleUnknown(kind, checkbox) {
            const section = checkbox.closest('.revision-card');
            const list = section.querySelector('.repeat-list');
            const addButton = section.querySelector('.add-person');
            const controls = section.querySelectorAll('.repeat-item input, .repeat-item select, .repeat-item button');

            [list, addButton].filter(Boolean).forEach(element => {
                element.hidden = checkbox.checked;
            });

            controls.forEach(control => {
                control.disabled = checkbox.checked;
            });

            if (!checkbox.checked) {
                list.querySelectorAll('.respondent-repeat-item').forEach(updateRespondentFields);
                list.querySelectorAll('.witness-repeat-item').forEach(updateWitnessFields);
            }
        }

        document.querySelectorAll('.unknown-toggle input').forEach(checkbox => {
            toggleUnknown(checkbox.name === 'respondent_unknown' ? 'respondents' : 'witnesses', checkbox);
        });

        form.addEventListener('submit', () => {
            submit.disabled = true;
            submit.innerHTML = `
                <i class="bi bi-hourglass-split"></i>
                Submitting...
            `;
        });
    </script>

</body>

</html>
