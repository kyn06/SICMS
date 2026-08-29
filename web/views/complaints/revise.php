<?php

require_once __DIR__ . '/../../controllers/ComplaintController.php';

$controller = new ComplaintController();
$viewData = $controller->handleRevisionRequest((int) ($_GET['id'] ?? 0));

$user = $viewData['user'];
$case = $viewData['case'];
$revision = $viewData['revision'];
$respondents = $viewData['respondents'];
$witnesses = $viewData['witnesses'];
$evidence = $viewData['evidence'];
$errors = $viewData['errors'];
$old = $viewData['old'];

$allowed = $revision['revision_fields'];

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function editable($field, $allowed)
{
    return in_array($field, $allowed, true);
}

function field_class($field, $allowed)
{
    return editable($field, $allowed)
        ? 'revision-required'
        : 'revision-readonly';
}

$incident = strtotime($case['incident_datetime']);

$labels = [
    'complaint_title' => 'Complaint Title',
    'complaint_details' => 'Complaint Description',
    'incident_date' => 'Incident Date',
    'incident_time' => 'Incident Time',
    'incident_location' => 'Incident Location',
    'respondents' => 'Respondent Information',
    'witnesses' => 'Witness Information',
    'evidence' => 'Supporting Evidence',
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Revise Complaint | SICMS</title>

    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        body {
            align-items: stretch;
            display: block;
            justify-content: flex-start;
            padding: 0;
        }

        .revision-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }

        .revision-intro {
            margin-bottom: 18px;
        }

        .revision-intro h1 {
            color: #172017;
            font-size: 24px;
            margin: 0 0 6px;
        }

        .revision-intro p {
            color: #5f6d5d;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        .revision-card {
            background: #fff;
            border: 1px solid #dce7d9;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(18, 60, 27, 0.07);
            margin-bottom: 16px;
            padding: 20px;
        }

        .revision-card h2 {
            color: #123c1b;
            font-size: 17px;
            margin: 0 0 14px;
        }

        .remarks-card {
            background: #fff9e8;
            border-color: #e7c966;
        }

        .remarks-grid,
        .meta-grid,
        .form-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .remarks-text {
            grid-column: 1 / -1;
            border-top: 1px solid #eadba7;
            padding-top: 13px;
        }

        .label {
            color: #687565;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .value {
            color: #263225;
            font-size: 14px;
            line-height: 1.55;
            margin-top: 4px;
        }

        .editable-sections {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 12px;
        }

        .editable-badge,
        .needs-badge {
            background: #e8f5e6;
            border-radius: 999px;
            color: #187325;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 9px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field input,
        .field textarea {
            width: 100%;
        }

        .field textarea {
            min-height: 120px;
        }

        .revision-required {
            background: #f7fcf5;
            border: 1px solid #8bc783;
            border-radius: 8px;
            padding: 14px;
        }

        .revision-readonly {
            background: #f6f7f5;
            border: 1px solid #dfe5dd;
            border-radius: 8px;
            padding: 14px;
        }

        .field-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .repeat-list {
            display: grid;
            gap: 12px;
        }

        .unknown-toggle {
            align-items: center;
            color: #4a5544;
            cursor: pointer;
            display: flex;
            font-size: 13px;
            gap: 8px;
            margin: 4px 0 12px;
        }

        .unknown-toggle input {
            accent-color: #1f6f43;
            height: 16px;
            width: 16px;
        }

        .unknown-toggle span {
            user-select: none;
        }

        .repeat-item {
            border: 1px solid #dce7d9;
            border-radius: 8px;
            padding: 14px;
        }

        .evidence-row {
            align-items: center;
            background: #f8faf7;
            border: 1px solid #e0e7de;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 12px;
        }

        .evidence-actions {
            display: flex;
            gap: 7px;
        }

        .confirm-row {
            align-items: flex-start;
            display: flex;
            gap: 10px;
        }

        .confirm-row input {
            height: 17px;
            margin-top: 2px;
            width: 17px;
        }

        .submit-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .alert {
            margin-bottom: 16px;
        }

        @media (max-width: 720px) {
            .remarks-grid,
            .meta-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .remarks-text,
            .field.full {
                grid-column: auto;
            }

            .evidence-row {
                grid-template-columns: 1fr;
            }

            .revision-wrap {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-shell">

        <?php require __DIR__ . '/../layout/sidebar.php'; ?>

        <div class="app-content">

            <?php
            $pageTitle = 'Revise Complaint';
            require __DIR__ . '/../layout/topbar.php';
            ?>

            <main class="revision-wrap">

                <header class="revision-intro">
                    <h1>Revise Complaint</h1>
                    <p>
                        Your complaint has been returned for revision.
                        Please review the SDRU remarks and update only the
                        highlighted sections before resubmitting your complaint.
                    </p>
                </header>

                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= h($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <section class="revision-card remarks-card">

                    <h2>
                        <i class="bi bi-exclamation-circle"></i>
                        SDRU Remarks
                    </h2>

                    <div class="remarks-grid">

                        <div>
                            <div class="label">Returned By</div>

                            <div class="value">
                                <?= h(
                                    trim(
                                        ($revision['actor_first_name'] ?? '') .
                                        ' ' .
                                        ($revision['actor_last_name'] ?? '')
                                    )
                                ) ?: 'SDRU Reviewer' ?>

                                (<?= h($revision['actor_role'] ?? 'Staff') ?>)
                            </div>
                        </div>

                        <div>
                            <div class="label">Date Returned</div>

                            <div class="value">
                                <?= h(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime($revision['created_at'])
                                    )
                                ) ?>
                            </div>
                        </div>

                        <div class="remarks-text">

                            <div class="label">
                                Revision Reason and Instructions
                            </div>

                            <div class="value">
                                <?= nl2br(h($revision['remarks'])) ?>
                            </div>

                        </div>

                    </div>

                    <div class="editable-sections">

                        <?php foreach ($allowed as $field): ?>

                            <span class="editable-badge">
                                <?= h($labels[$field] ?? $field) ?>
                            </span>

                        <?php endforeach; ?>

                    </div>

                </section>

                <section class="revision-card">

                    <h2>Complaint Information</h2>

                    <div class="meta-grid">

                        <div>
                            <div class="label">Case Number</div>
                            <div class="value">
                                <?= h($case['case_number']) ?>
                            </div>
                        </div>

                        <div>
                            <div class="label">Current Status</div>
                            <div class="value">
                                <?= h($case['status']) ?>
                            </div>
                        </div>

                        <div>
                            <div class="label">Date Submitted</div>
                            <div class="value">
                                <?= h(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime($case['submitted_at'])
                                    )
                                ) ?>
                            </div>
                        </div>

                        <div>
                            <div class="label">Last Updated</div>
                            <div class="value">
                                <?= h(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime($case['updated_at'])
                                    )
                                ) ?>
                            </div>
                        </div>

                    </div>

                </section>

                <form
                    id="revisionForm"
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= h(Security::csrfToken()) ?>"
                    >

                    <section class="revision-card">

                        <h2>Complaint Details</h2>

                        <div class="form-grid">

                            <?php
                            foreach (
                                [
                                    [
                                        'complaint_title',
                                        'Complaint Title',
                                        $case['complaint_title'] ?: $case['case_classification'],
                                        'text',
                                    ],
                                    [
                                        'incident_date',
                                        'Incident Date',
                                        date('Y-m-d', $incident),
                                        'date',
                                    ],
                                    [
                                        'incident_time',
                                        'Incident Time',
                                        date('H:i', $incident),
                                        'time',
                                    ],
                                    [
                                        'incident_location',
                                        'Incident Location',
                                        $case['incident_location'],
                                        'text',
                                    ],
                                ] as [$field, $label, $value, $type]
                            ):
                            ?>

                                <div class="field <?= field_class($field, $allowed) ?>">

                                    <div class="field-head">

                                        <label for="<?= $field ?>">
                                            <?= h($label) ?>
                                        </label>

                                        <?php if (editable($field, $allowed)): ?>
                                            <span class="needs-badge">
                                                Needs Revision
                                            </span>
                                        <?php endif; ?>

                                    </div>

                                    <input
                                        id="<?= $field ?>"
                                        type="<?= $type ?>"
                                        name="<?= $field ?>"
                                        value="<?= h($old[$field] ?? $value) ?>"
                                        <?= editable($field, $allowed) ? 'required' : 'readonly' ?>
                                    >

                                </div>

                            <?php endforeach; ?>

                            <div class="field full <?= field_class('complaint_details', $allowed) ?>">

                                <div class="field-head">

                                    <label for="complaint_details">
                                        Complaint Description
                                    </label>

                                    <?php if (editable('complaint_details', $allowed)): ?>
                                        <span class="needs-badge">
                                            Needs Revision
                                        </span>
                                    <?php endif; ?>

                                </div>

                                <textarea
                                    id="complaint_details"
                                    name="complaint_details"
                                    <?= editable('complaint_details', $allowed) ? 'required' : 'readonly' ?>
                                ><?= h($old['complaint_details'] ?? $case['complaint_details']) ?></textarea>

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

                                <div class="repeat-item">

                                    <div class="form-grid">

                                        <?php
                                        foreach (
                                            [
                                                'full_name' => 'Full Name',
                                                'student_no' => 'Student Number',
                                                'college' => 'College',
                                                'course_year' => 'Course and Section',
                                                'contact_info' => 'Contact Information',
                                                'details' => 'Details',
                                            ] as $key => $label
                                        ):
                                        ?>

                                            <div class="field">

                                                <label>
                                                    <?= h($label) ?>
                                                </label>

                                                <input
                                                    name="respondent_<?= $key === 'contact_info' ? 'contact' : ($key === 'full_name' ? 'name' : $key) ?>[]"
                                                    value="<?= h($person[$key] ?? '') ?>"
                                                    <?= editable('respondents', $allowed) ? '' : 'readonly' ?>
                                                    <?= ($key === 'full_name' && editable('respondents', $allowed)) ? 'required' : '' ?>
                                                >

                                            </div>

                                        <?php endforeach; ?>

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

                                <span>I do not have a witness</span>

                            </label>

                        <?php endif; ?>

                        <div class="repeat-list" id="witnesses">

                            <?php foreach ($witnesses as $person): ?>

                                <div class="repeat-item">

                                    <div class="form-grid">

                                        <?php
                                        foreach (
                                            [
                                                'full_name' => 'Full Name',
                                                'student_no' => 'Student Number',
                                                'contact_info' => 'Contact Information',
                                                'statement' => 'Statement',
                                            ] as $key => $label
                                        ):
                                        ?>

                                            <div class="field">

                                                <label>
                                                    <?= h($label) ?>
                                                </label>

                                                <input
                                                    name="witness_<?= $key === 'contact_info' ? 'contact' : ($key === 'full_name' ? 'name' : $key) ?>[]"
                                                    value="<?= h($person[$key] ?? '') ?>"
                                                    <?= editable('witnesses', $allowed) ? '' : 'readonly' ?>
                                                    <?= ($key === 'full_name' && editable('witnesses', $allowed)) ? 'required' : '' ?>
                                                >

                                            </div>

                                        <?php endforeach; ?>

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

                        <div class="repeat-list">

                            <?php foreach ($evidence as $file): ?>

                                <div class="evidence-row">

                                    <div>

                                        <strong>
                                            <?= h($file['original_filename']) ?>
                                        </strong>

                                        <div class="value">
                                            <?= h($file['mime_type']) ?>
                                            ·
                                            <?= h(number_format($file['file_size'] / 1024, 1)) ?> KB
                                            ·
                                            <?= h(date('M d, Y', strtotime($file['uploaded_at']))) ?>
                                        </div>

                                    </div>

                                    <div class="evidence-actions">

                                        <a
                                            class="btn btn-secondary"
                                            target="_blank"
                                            href="attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=view"
                                        >
                                            View
                                        </a>

                                        <a
                                            class="btn btn-secondary"
                                            href="attachment.php?id=<?= (int) $file['evidence_id'] ?>&amp;mode=download"
                                        >
                                            Download
                                        </a>

                                        <?php if (editable('evidence', $allowed)): ?>

                                            <label class="btn btn-secondary">

                                                <input
                                                    type="checkbox"
                                                    name="remove_evidence[]"
                                                    value="<?= (int) $file['evidence_id'] ?>"
                                                >

                                                Remove

                                            </label>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <?php if (editable('evidence', $allowed)): ?>

                            <div class="field" style="margin-top: 14px">

                                <label for="evidence">
                                    Upload Additional Evidence
                                </label>

                                <input
                                    id="evidence"
                                    type="file"
                                    name="evidence[]"
                                    accept=".pdf,.jpg,.jpeg,.png,.docx"
                                    multiple
                                >

                                <div class="value">
                                    PDF, JPG, PNG, or DOCX.
                                    Maximum 5MB per file.
                                </div>

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
                ['name', 'Full Name', 'required'],
                ['student_no', 'Student Number', ''],
                ['college', 'College', ''],
                ['course_year', 'Course and Section', ''],
                ['contact', 'Contact Information', ''],
                ['details', 'Details', '']
            ],

            witnesses: [
                ['name', 'Full Name', 'required'],
                ['student_no', 'Student Number', ''],
                ['contact', 'Contact Information', ''],
                ['statement', 'Statement', '']
            ]
        };

        const personRemoveLabels = {
            respondents: 'Remove Respondent',
            witnesses: 'Remove Witness'
        };

        function buildPersonItem(type) {
            const fields = personFieldSets[type] || [];

            return `
                <div class="repeat-item">
                    <div class="form-grid">
                        ${fields
                            .map(([key, label, rule]) => {
                                return `
                                    <div class="field">
                                        <label>${label}</label>
                                        <input
                                            name="${type}_${key}[]"
                                            ${rule === 'required' ? 'required' : ''}
                                        >
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

                    item.querySelectorAll('input').forEach(input => {
                        input.value = '';
                    });
                } else {
                    item = document.createElement('div');
                    item.innerHTML = buildPersonItem(button.dataset.list);
                    item = item.firstElementChild;
                }

                list.appendChild(item);
            });
        });

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
        }

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