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
$filters = $viewData['filters'];
$statuses = $viewData['statuses'];
$classifications = $viewData['classifications'];

function h($value) {
    return htmlspecialchars((string) $value);
}

function role_key($role) {
    return strtolower(str_replace(['_', ' '], '-', (string) $role));
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
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-shell app-content">
            <?php $pageTitle = 'Case Management'; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="case-wrap">
            <section class="filter-panel">
                <form id="caseFilters" method="GET" action="index.php">
                    <div class="filter-grid">
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
                            <label for="case_number">Case Number</label>
                            <input id="case_number" name="case_number" value="<?= h($filters['case_number']) ?>">
                        </div>
                        <div class="field">
                            <label for="student_name">Student Name</label>
                            <input id="student_name" name="student_name" value="<?= h($filters['student_name']) ?>">
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-secondary" id="clearFilters" type="button">Clear</button>
                        <button class="btn btn-primary" type="submit">Apply Filters</button>
                    </div>
                </form>
            </section>

            <section class="table-panel">
                    <div class="empty-state" id="caseEmptyState" <?= empty($cases) ? '' : 'hidden' ?>>No complaints found.</div>
                    <table id="caseTable" <?= empty($cases) ? 'hidden' : '' ?>>
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant Name</th>
                                <th>Classification</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
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
                                    <td><?= h($case['case_classification']) ?></td>
                                    <td><span class="status"><?= h($case['status']) ?></span></td>
                                    <td><?= h(date('M d, Y h:i A', strtotime($case['submitted_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            const clearButton = document.getElementById('clearFilters');
            const textInputs = [form.elements.case_number, form.elements.student_name];
            const selects = [form.elements.status, form.elements.classification];
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

            const appendCell = (row, text) => {
                const cell = document.createElement('td');
                cell.textContent = text ?? '';
                row.appendChild(cell);
                return cell;
            };

            const renderCases = (cases) => {
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
                    appendCell(row, item.case_classification);
                    const statusCell = document.createElement('td');
                    const status = document.createElement('span');
                    status.className = 'status';
                    status.textContent = item.status;
                    statusCell.appendChild(status);
                    row.appendChild(statusCell);
                    appendCell(row, formatDate(item.submitted_at));
                    tableBody.appendChild(row);
                });

                const hasCases = cases.length > 0;
                table.hidden = !hasCases;
                emptyState.hidden = hasCases;
                emptyState.textContent = 'No complaints found.';
            };

            const updateCases = async () => {
                activeRequest?.abort();
                activeRequest = new AbortController();
                const params = new URLSearchParams(new FormData(form));
                params.set('ajax', '1');
                const requestUrl = `${form.action}?${params.toString()}`;
                form.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(requestUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: activeRequest.signal
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'Search failed.');
                    renderCases(data.cases);
                    params.delete('ajax');
                    const query = params.toString();
                    history.replaceState(null, '', query ? `${form.action}?${query}` : form.action);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        table.hidden = true;
                        emptyState.hidden = false;
                        emptyState.textContent = 'Unable to filter cases. Please try again.';
                    }
                } finally {
                    form.removeAttribute('aria-busy');
                }
            };

            const debounceSearch = () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(updateCases, 400);
            };

            textInputs.forEach((input) => input.addEventListener('input', debounceSearch));
            selects.forEach((select) => select.addEventListener('change', updateCases));
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                clearTimeout(debounceTimer);
                updateCases();
            });
            clearButton.addEventListener('click', () => {
                clearTimeout(debounceTimer);
                [...textInputs, ...selects].forEach((control) => control.value = '');
                updateCases();
            });
        })();
    </script>
</body>

</html>
