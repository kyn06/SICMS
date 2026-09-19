<?php
require_once __DIR__ . '/../../controllers/CaseController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new CaseController();

if (($_GET['ajax'] ?? '') === '1') {
    $controller->search();
}

$viewData = $controller->index();

$user = $viewData['user'];
$cases = $viewData['cases'];
$assignedCases = $viewData['assignedCases'];
$filters = $viewData['filters'];
$statuses = $viewData['statuses'];
$classifications = $viewData['classifications'];
$migratedCases = $viewData['migratedCases'];
$canEditMigrated = $viewData['canEditMigrated'];
$viewerRoleKey = role_key($user['role'] ?? '');

function h($value) {
    return htmlspecialchars((string) $value);
}

function role_key($role) {
    return strtolower(str_replace(['_', ' '], '-', (string) $role));
}

function respondents_label(array $case) {
    $names = array_values(array_filter(
        array_map('trim', explode(',', (string) ($case['respondent_names'] ?? ''))),
        fn($name) => $name !== ''
    ));

    if (count($names) === 0) {
        return 'Not provided';
    }

    if (count($names) === 1) {
        return $names[0];
    }

    $surnames = array_map(function ($fullName) {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        return $parts ? end($parts) : trim($fullName);
    }, $names);

    return implode(', ', $surnames);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/cases.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <style>
    #caseFilters {
        align-items: center;
        background: none;
        border: none;
        box-shadow: none;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        padding: 0;
        position: relative;
        width: 100%;
    }

    .case-search-box {
        align-items: center;
        background: #fff;
        border: 1px solid #bfd0bc;
        border-radius: 6px;
        display: inline-flex;
        flex: 0 1 auto;
        gap: 8px;
        min-height: 40px;
        padding: 0 12px;
        width: min(460px, calc(100vw - 170px));
    }

    .case-search-box i {
        color: #5f6f5c;
        font-size: 14px;
    }

    .case-search-box input {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        min-height: 0 !important;
        outline: none !important;
        padding: 8px 0 !important;
        width: 100%;
    }

    .filters-toggle-btn {
        align-items: center;
        background: #fff;
        border: 1px solid #bfd0bc;
        border-radius: 6px;
        color: var(--text);
        cursor: pointer;
        display: inline-flex;
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        gap: 7px;
        padding: 7px 13px;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .filters-toggle-btn:hover {
        border-color: var(--sicms-green-600);
        color: var(--sicms-green-700);
    }

    .filters-count {
        align-items: center;
        background: #e6f3ea;
        border-radius: 999px;
        color: #1a8c2b;
        display: inline-flex;
        font-size: 11px;
        justify-content: center;
        min-width: 20px;
        padding: 0 6px;
        height: 20px;
    }

    .filters-count[hidden] {
        display: none;
    }

    #caseFilters.is-active .filters-toggle-btn,
    #caseFilters.is-active .filters-toggle-btn:hover {
        background: var(--sicms-green-600);
        border-color: var(--sicms-green-600);
        color: #fff;
    }

    #caseFilters.is-active .filters-count {
        background: #fff;
    }

    .filters-popover {
        background: #fff;
        border: 1px solid rgba(191, 208, 188, .75);
        border-radius: 14px;
        box-shadow: 0 18px 45px rgba(15, 40, 21, .22);
        display: none;
        left: auto;
        padding: 12px 14px;
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        width: min(620px, calc(100vw - 56px));
        z-index: 500;
    }

    .filters-popover.open {
        animation: sicmsFilterPop .18s ease-out;
        display: block;
    }

    @keyframes sicmsFilterPop {
        from {
            opacity: 0;
            transform: translateY(-6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #caseFilters .filter-grid {
        gap: 9px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #caseFilters .filter-grid .field input,
    #caseFilters .filter-grid .field select {
        font-size: 13px;
        min-height: 0;
        padding: 7px 10px;
    }

    #caseFilters .filter-grid .field label {
        font-size: 11.5px;
    }

    #caseFilters .filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 12px;
    }

    .filter-popover-title {
        align-items: center;
        color: var(--sicms-green-900);
        display: flex;
        font-size: 13.5px;
        font-weight: 800;
        gap: 8px;
        margin-bottom: 8px;
    }

    .filter-popover-title i {
        color: #167a22;
    }

    .filter-group-title {
        align-items: center;
        border-bottom: 1px solid #edf3ec;
        color: var(--sicms-green-900);
        display: flex;
        font-size: 11px;
        font-weight: 800;
        gap: 6px;
        grid-column: 1 / -1;
        letter-spacing: .05em;
        padding-bottom: 5px;
        text-transform: uppercase;
    }

    .filter-group-title i {
        color: #167a22;
        font-size: 12px;
    }

    #caseFilterStatus {
        color: #7c8b78;
        font-size: 12px;
        margin-right: auto;
    }

    @media (max-width: 640px) {
        .filters-popover {
            width: calc(100vw - 32px);
        }

        #caseFilters .filter-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-shell app-content">
            <?php $pageTitle = 'Case Management'; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="case-wrap">
            <section class="filter-panel">
                <form id="caseFilters" method="GET" action="index.php">
                    <div class="case-search-box">
                        <i class="bi bi-search"></i>
                        <input id="caseSearch" name="search" type="text" placeholder="Search case number, complainant, or respondent..." value="<?= h($filters['search']) ?>">
                    </div>
                    <button type="button" class="filters-toggle-btn" id="caseFiltersToggle" aria-expanded="false" aria-controls="caseFiltersPanel">
                        <i class="bi bi-funnel"></i> Filters
                        <span class="filters-count" id="caseFiltersCount" hidden></span>
                    </button>
                    <div class="filters-popover" id="caseFiltersPanel">
                        <div class="filter-popover-title"><i class="bi bi-sliders"></i> Case Filters</div>
                        <div class="filter-grid">
                            <div class="filter-group-title"><i class="bi bi-calendar-range"></i> Date</div>
                            <div class="field">
                                <label for="date_from">Date From</label>
                                <input id="date_from" type="date" name="date_from" value="<?= h($filters['date_from']) ?>">
                            </div>
                            <div class="field">
                                <label for="date_to">Date To</label>
                                <input id="date_to" type="date" name="date_to" value="<?= h($filters['date_to']) ?>">
                            </div>
                            <div class="field">
                                <label for="month">Month</label>
                                <select id="month" name="month">
                                    <option value="">All months</option>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= (int) ($filters['month'] ?? 0) === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="year">Year</label>
                                <input id="year" type="number" name="year" min="2000" max="2100" value="<?= h($filters['year']) ?>" placeholder="<?= h(date('Y')) ?>">
                            </div>

                            <div class="filter-group-title"><i class="bi bi-briefcase"></i> Case</div>
                            <div class="field">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">All statuses</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= h($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="classification">Classification</label>
                                <select id="classification" name="classification">
                                    <option value="">All classifications</option>
                                    <?php foreach ($classifications as $classification): ?>
                                        <option value="<?= h($classification) ?>" <?= $filters['classification'] === $classification ? 'selected' : '' ?>><?= h($classification) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="case_source">Case Source</label>
                                <select id="case_source" name="case_source">
                                    <option value="">All case sources</option>
                                    <option value="online" <?= ($filters['case_source'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                                    <option value="migrated" <?= in_array($filters['case_source'] ?? '', ['migrated', 'legacy'], true) ? 'selected' : '' ?>>Migrated</option>
                                </select>
                            </div>

                            <div class="filter-group-title"><i class="bi bi-person-check"></i> Personnel</div>
                            <div class="field">
                                <label for="coordinator">Discipline Coordinator</label>
                                <select id="coordinator" name="coordinator">
                                    <option value="">All Discipline Coordinators</option>
                                    <?php foreach ($coordinators as $coordinator): ?>
                                        <option value="<?= (int) $coordinator['account_id'] ?>" <?= (int) $filters['coordinator'] === (int) $coordinator['account_id'] ? 'selected' : '' ?>><?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <span id="caseFilterStatus" class="muted" role="status" aria-live="polite"></span>
                            <a class="btn btn-secondary" id="resetCaseFilters" href="index.php"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Apply Filters</button>
                        </div>
                    </div>
                </form>
            </section>

            <?php if (in_array($viewerRoleKey, ['coordinator', 'reformation-coordinator'], true)): ?>
            <section class="table-panel">
                    <div class="table-heading table-heading-row">
                        <h2><i class="bi bi-folder-check"></i> <?= $viewerRoleKey === 'reformation-coordinator' ? 'My Reformation Cases' : 'Assigned Cases' ?></h2>
                    </div>
                    <div class="empty-state" id="assignedEmptyState" <?= empty($assignedCases) ? '' : 'hidden' ?>>No cases are assigned to you.</div>
                    <table id="assignedCaseTable" <?= empty($assignedCases) ? 'hidden' : '' ?>>
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant Name</th>
                                <th>Respondent</th>
                                <th>Classification</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assignedCaseTableBody">
                            <?php foreach ($assignedCases as $case): ?>
                                <tr>
                                    <td>
                                        <a class="case-link" href="show.php?id=<?= (int) $case['complaint_id'] ?>">
                                            <?= h($case['case_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= h($case['complainant_name']) ?></td>
                                    <td><?= h(respondents_label($case)) ?></td>
                                    <td><?= h($case['case_classification']) ?></td>
                                    <td><span class="status"><?= h($case['status']) ?></span></td>
                                    <td><?= h(date('M d, Y h:i A', strtotime($case['submitted_at']))) ?></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="btn btn-primary" href="show.php?id=<?= (int) $case['complaint_id'] ?>"><i class="bi bi-eye"></i> View Details</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </section>
            <?php endif; ?>

            <?php if (!in_array($viewerRoleKey, ['coordinator', 'reformation-coordinator'], true)): ?>
            <section class="table-panel">
                    <div class="table-heading table-heading-row">
                        <h2><i class="bi bi-globe2"></i> Online Cases</h2>
                    </div>
                    <div class="empty-state" id="caseEmptyState" <?= empty($cases) ? '' : 'hidden' ?>>No online cases found.</div>
                    <table id="caseTable" <?= empty($cases) ? 'hidden' : '' ?>>
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant Name</th>
                                <th>Respondent</th>
                                <th>Classification</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="caseTableBody">
                            <?php foreach ($cases as $case): ?>
                                <tr>
                                    <td>
                                        <a class="case-link" href="show.php?id=<?= (int) $case['complaint_id'] ?>">
                                            <?= h($case['case_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= h($case['complainant_name']) ?></td>
                                    <td><?= h(respondents_label($case)) ?></td>
                                    <td><?= h($case['case_classification']) ?></td>
                                    <td><span class="status"><?= h($case['status']) ?></span></td>
                                    <td><?= h(date('M d, Y h:i A', strtotime($case['submitted_at']))) ?></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="btn btn-primary" href="show.php?id=<?= (int) $case['complaint_id'] ?>"><i class="bi bi-eye"></i> View Details</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </section>

            <section class="table-panel">
                <div class="table-heading table-heading-row">
                    <h2><i class="bi bi-archive"></i> Migrated Cases</h2>
                    <?php if ($canEditMigrated): ?>
                        <a class="btn btn-primary" href="../legacy_cases/create.php"><i class="bi bi-plus-lg"></i> Digitize Migrated Case</a>
                    <?php endif; ?>
                </div>
                <div class="empty-state" id="migratedEmptyState" <?= empty($migratedCases) ? '' : 'hidden' ?>>No migrated cases found.</div>
                <table id="migratedCaseTable" <?= empty($migratedCases) ? 'hidden' : '' ?>>
                    <thead>
                        <tr>
                            <th>Original Case No.</th>
                            <th>Complainant</th>
                            <th>Classification</th>
                            <th>Original Case Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="migratedCaseTableBody">
                        <?php foreach ($migratedCases as $case): ?>
                            <tr>
                                <td>
                                    <a class="case-link" href="../legacy_cases/show.php?id=<?= (int) $case['complaint_id'] ?>">
                                        <?= h($case['case_number']) ?>
                                    </a>
                                </td>
                                <td><?= h($case['complainant_name']) ?></td>
                                <td><?= h($case['case_classification']) ?></td>
                                <td><?= h(date('M d, Y', strtotime($case['original_case_date'] ?: $case['submitted_at']))) ?></td>
                                <td><span class="status"><?= h($case['status']) ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-primary" href="../legacy_cases/show.php?id=<?= (int) $case['complaint_id'] ?>"><i class="bi bi-eye"></i> View Details</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <?php endif; ?>

            <section class="table-panel">
                <div class="table-heading" style="margin-bottom:2px">
                    <h2><i class="bi bi-archive"></i> Archived Cases</h2>
                </div>
                <div class="table-heading table-heading-row">
                    <p class="muted" style="margin:0">View cases that have been archived. Archived cases are still counted in statistics and reports.</p>
                    <a class="btn btn-primary" href="<?= h(app_route('archived_cases.index')) ?>"><i class="bi bi-archive-fill"></i> Open Archived Cases</a>
                </div>
            </section>
        </main>
        </div>
    </div>
    <script>
        (() => {
            const form = document.getElementById('caseFilters');
            const table = document.getElementById('caseTable');
            const tableBody = document.getElementById('caseTableBody');
            const emptyState = document.getElementById('caseEmptyState');
            const assignedTable = document.getElementById('assignedCaseTable');
            const assignedBody = document.getElementById('assignedCaseTableBody');
            const assignedEmptyState = document.getElementById('assignedEmptyState');
            const migratedTable = document.getElementById('migratedCaseTable');
            const migratedBody = document.getElementById('migratedCaseTableBody');
            const migratedEmptyState = document.getElementById('migratedEmptyState');
            const filterStatus = document.getElementById('caseFilterStatus');
            const resetFilters = document.getElementById('resetCaseFilters');
            const filtersToggle = document.getElementById('caseFiltersToggle');
            const filtersPanel = document.getElementById('caseFiltersPanel');
            const filtersCount = document.getElementById('caseFiltersCount');
            const textInputs = [form.elements.date_from, form.elements.date_to, form.elements.year, form.elements.search];
            const selects = [form.elements.month, form.elements.status, form.elements.classification, form.elements.case_source, form.elements.coordinator];
            let debounceTimer;
            let activeRequest;

            const formatDate = (value) => {
                const date = new Date(String(value).replace(' ', 'T'));
                if (Number.isNaN(date.getTime())) return value || '';
                return new Intl.DateTimeFormat('en-US', {
                    month: 'short', day: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                }).format(date);
            };

            const formatDateShort = (value) => {
                const date = new Date(String(value).replace(' ', 'T'));
                if (Number.isNaN(date.getTime())) return value || '';
                return new Intl.DateTimeFormat('en-US', {
                    month: 'short', day: '2-digit', year: 'numeric'
                }).format(date);
            };

            const respondentsLabel = (item) => {
                const names = String(item.respondent_names || '')
                    .split(',')
                    .map((name) => name.trim())
                    .filter(Boolean);

                if (names.length === 0) return 'Not provided';
                if (names.length === 1) return names[0];

                const surnames = names.map((fullName) => {
                    const parts = fullName.trim().split(/\s+/).filter(Boolean);
                    return parts.length ? parts[parts.length - 1] : fullName.trim();
                });

                return surnames.join(', ');
            };

            const appendCell = (row, text) => {
                const cell = document.createElement('td');
                cell.textContent = text ?? '';
                row.appendChild(cell);
                return cell;
            };

            const renderCases = (cases) => {
                if (!table || !tableBody || !emptyState) return;
                tableBody.replaceChildren();

                cases.forEach((item) => {
                    const row = document.createElement('tr');
                    const numberCell = document.createElement('td');
                    const link = document.createElement('a');
                    link.className = 'case-link';
                    link.href = `show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    link.textContent = item.case_number;
                    numberCell.appendChild(link);
                    row.appendChild(numberCell);
                    appendCell(row, item.complainant_name);
                    appendCell(row, respondentsLabel(item));
                    appendCell(row, item.case_classification);
                    const statusCell = document.createElement('td');
                    const status = document.createElement('span');
                    status.className = 'status';
                    status.textContent = item.status;
                    statusCell.appendChild(status);
                    row.appendChild(statusCell);
                    appendCell(row, formatDate(item.submitted_at));
                    const actionsCell = document.createElement('td');
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'row-actions';
                    const viewLink = document.createElement('a');
                    viewLink.className = 'btn btn-primary';
                    viewLink.href = `show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    viewLink.innerHTML = '<i class="bi bi-eye"></i> View Details';
                    actionsDiv.appendChild(viewLink);
                    actionsCell.appendChild(actionsDiv);
                    row.appendChild(actionsCell);
                    tableBody.appendChild(row);
                });

                const hasCases = cases.length > 0;
                table.hidden = !hasCases;
                emptyState.hidden = hasCases;
                emptyState.textContent = 'No online cases found.';
            };

            const renderAssigned = (cases) => {
                if (!assignedBody) return;
                assignedBody.replaceChildren();

                cases.forEach((item) => {
                    const row = document.createElement('tr');
                    const numberCell = document.createElement('td');
                    const link = document.createElement('a');
                    link.className = 'case-link';
                    link.href = `show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    link.textContent = item.case_number;
                    numberCell.appendChild(link);
                    row.appendChild(numberCell);
                    appendCell(row, item.complainant_name);
                    appendCell(row, respondentsLabel(item));
                    appendCell(row, item.case_classification);
                    const statusCell = document.createElement('td');
                    const status = document.createElement('span');
                    status.className = 'status';
                    status.textContent = item.status;
                    statusCell.appendChild(status);
                    row.appendChild(statusCell);
                    appendCell(row, formatDate(item.submitted_at));
                    const actionsCell = document.createElement('td');
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'row-actions';
                    const viewLink = document.createElement('a');
                    viewLink.className = 'btn btn-primary';
                    viewLink.href = `show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    viewLink.innerHTML = '<i class="bi bi-eye"></i> View Details';
                    actionsDiv.appendChild(viewLink);
                    actionsCell.appendChild(actionsDiv);
                    row.appendChild(actionsCell);
                    assignedBody.appendChild(row);
                });

                const hasCases = cases.length > 0;
                assignedTable.hidden = !hasCases;
                assignedEmptyState.hidden = hasCases;
            };

            const renderMigrated = (cases) => {
                if (!migratedTable || !migratedBody || !migratedEmptyState) return;
                migratedBody.replaceChildren();

                cases.forEach((item) => {
                    const row = document.createElement('tr');
                    const numberCell = document.createElement('td');
                    const link = document.createElement('a');
                    link.className = 'case-link';
                    link.href = `../legacy_cases/show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    link.textContent = item.case_number;
                    numberCell.appendChild(link);
                    row.appendChild(numberCell);
                    appendCell(row, item.complainant_name);
                    appendCell(row, item.case_classification);
                    appendCell(row, formatDateShort(item.original_case_date || item.submitted_at));
                    const statusCell = document.createElement('td');
                    const status = document.createElement('span');
                    status.className = 'status';
                    status.textContent = item.status;
                    statusCell.appendChild(status);
                    row.appendChild(statusCell);
                    const actionsCell = document.createElement('td');
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'row-actions';
                    const viewLink = document.createElement('a');
                    viewLink.className = 'btn btn-primary';
                    viewLink.href = `../legacy_cases/show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    viewLink.innerHTML = '<i class="bi bi-eye"></i> View';
                    actionsDiv.appendChild(viewLink);
                    actionsCell.appendChild(actionsDiv);
                    row.appendChild(actionsCell);
                    migratedBody.appendChild(row);
                });

                const hasCases = cases.length > 0;
                migratedTable.hidden = !hasCases;
                migratedEmptyState.hidden = hasCases;
            };

            const updateCases = async () => {
                activeRequest?.abort();
                activeRequest = new AbortController();
                const params = new URLSearchParams(new FormData(form));
                params.set('ajax', '1');
                const requestUrl = `${form.action}?${params.toString()}`;
                form.setAttribute('aria-busy', 'true');
                filterStatus.textContent = 'Updating cases...';

                try {
                    const response = await fetch(requestUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: activeRequest.signal
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'Search failed.');
                    renderCases(data.cases);
                    renderMigrated(data.migratedCases || []);
                    renderAssigned(data.assignedCases || []);
                    params.delete('ajax');
                    const query = params.toString();
                    history.replaceState(null, '', query ? `${form.action}?${query}` : form.action);
                    filterStatus.textContent = 'Cases updated.';
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        if (table) table.hidden = true;
                        if (emptyState) {
                            emptyState.hidden = false;
                            emptyState.textContent = 'Unable to filter cases. Please try again.';
                        }
                        filterStatus.textContent = 'Unable to filter cases. Please try again.';
                        if (assignedTable) assignedTable.hidden = true;
                        if (assignedEmptyState) {
                            assignedEmptyState.hidden = false;
                            assignedEmptyState.textContent = 'Unable to filter cases. Please try again.';
                        }
                        if (migratedTable) migratedTable.hidden = true;
                        if (migratedEmptyState) {
                            migratedEmptyState.hidden = false;
                            migratedEmptyState.textContent = 'Unable to filter cases. Please try again.';
                        }
                    }
                } finally {
                    form.removeAttribute('aria-busy');
                    updateCaseFilterButton();
                }
            };

            const debounceSearch = () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(updateCases, 400);
            };

            const closeCaseFilters = () => {
                if (!filtersPanel || !filtersToggle) return;
                filtersPanel.classList.remove('open');
                filtersToggle.setAttribute('aria-expanded', 'false');
            };

            const updateCaseFilterButton = () => {
                const activeCount = [...new FormData(form).entries()]
                    .filter(([name, value]) => name !== 'search' && String(value).trim() !== '')
                    .length;
                form.classList.toggle('is-active', activeCount > 0);
                if (filtersCount) {
                    filtersCount.hidden = activeCount === 0;
                    filtersCount.textContent = activeCount;
                }
            };

            filtersToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = filtersPanel.classList.toggle('open');
                filtersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
            document.addEventListener('click', (event) => {
                if (!filtersPanel.classList.contains('open')) return;
                if (!filtersPanel.contains(event.target)) closeCaseFilters();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeCaseFilters();
            });
            window.addEventListener('pageshow', updateCaseFilterButton);
            updateCaseFilterButton();

            textInputs.forEach((input) => input.addEventListener('input', debounceSearch));
            selects.forEach((select) => select.addEventListener('change', updateCases));
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                clearTimeout(debounceTimer);
                updateCases();
            });
            resetFilters.addEventListener('click', (event) => {
                event.preventDefault();
                clearTimeout(debounceTimer);
                form.reset();
                const controls = [...form.elements].filter((control) => control.name);
                controls.forEach((control) => control.value = '');
                updateCases();
            });
        })();
    </script>
</body>

</html>
