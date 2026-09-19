<?php
require_once __DIR__ . '/../../controllers/CaseController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new CaseController();

if (($_GET['ajax'] ?? '') === '1') {
    $controller->archivedSearch();
}

$viewData = $controller->archivedIndex();

$user = $viewData['user'];
$cases = $viewData['cases'];
$filters = $viewData['filters'];

function h($value) {
    return htmlspecialchars((string) $value);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Cases | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/cases.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="case-shell app-content">
            <?php $pageTitle = 'Archived Cases'; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="case-wrap">
            <section class="filter-panel">
                <form id="caseFilters" method="GET" action="index.php">
                    <div class="filter-grid">
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
                    <div class="empty-state" id="caseEmptyState" <?= empty($cases) ? '' : 'hidden' ?>>No archived cases found.</div>
                    <table id="caseTable" <?= empty($cases) ? 'hidden' : '' ?>>
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant Name</th>
                                <th>Classification</th>
                                <th>Status</th>
                                <th>Date Archived</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="caseTableBody">
                            <?php foreach ($cases as $case): ?>
                                <tr>
                                    <td>
                                        <a class="case-link" href="../cases/show.php?id=<?= (int) $case['complaint_id'] ?>">
                                            <?= h($case['case_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= h($case['complainant_name']) ?></td>
                                    <td><?= h($case['case_classification']) ?></td>
                                    <td><span class="status"><?= h($case['status']) ?></span></td>
                                    <td><?= h(date('M d, Y h:i A', strtotime($case['updated_at']))) ?></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="btn btn-primary" href="../cases/show.php?id=<?= (int) $case['complaint_id'] ?>"><i class="bi bi-eye"></i> View Details</a>
                                        </div>
                                    </td>
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
            const CSRF_TOKEN = <?= json_encode(Security::csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const form = document.getElementById('caseFilters');
            const table = document.getElementById('caseTable');
            const tableBody = document.getElementById('caseTableBody');
            const emptyState = document.getElementById('caseEmptyState');
            const clearButton = document.getElementById('clearFilters');
            const textInputs = [form.elements.case_number, form.elements.student_name];
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
                    link.href = `../cases/show.php?id=${encodeURIComponent(item.complaint_id)}`;
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
                    appendCell(row, formatDate(item.updated_at));
                    const actionsCell = document.createElement('td');
                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'row-actions';
                    const viewLink = document.createElement('a');
                    viewLink.className = 'btn btn-primary';
                    viewLink.href = `../cases/show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    viewLink.innerHTML = '<i class="bi bi-eye"></i> View Details';
                    actionsDiv.appendChild(viewLink);

                    const unarchiveForm = document.createElement('form');
                    unarchiveForm.method = 'POST';
                    unarchiveForm.action = `../cases/show.php?id=${encodeURIComponent(item.complaint_id)}`;
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = CSRF_TOKEN;
                    unarchiveForm.appendChild(csrfInput);
                    const remarksInput = document.createElement('input');
                    remarksInput.type = 'hidden';
                    remarksInput.name = 'remarks';
                    remarksInput.value = 'Case restored from the archive.';
                    unarchiveForm.appendChild(remarksInput);
                    const actionButton = document.createElement('button');
                    actionButton.className = 'btn btn-secondary';
                    actionButton.type = 'submit';
                    actionButton.name = 'case_action';
                    actionButton.value = 'unarchive';
                    actionButton.dataset.swalConfirm = 'Return this case to active cases? Its previous status will be restored.';
                    actionButton.innerHTML = '<i class="bi bi-arrow-up-square"></i> Unarchive';
                    unarchiveForm.appendChild(actionButton);
                    actionsDiv.appendChild(unarchiveForm);
                    actionsCell.appendChild(actionsDiv);
                    row.appendChild(actionsCell);
                    tableBody.appendChild(row);
                });

                const hasCases = cases.length > 0;
                table.hidden = !hasCases;
                emptyState.hidden = hasCases;
                emptyState.textContent = 'No archived cases found.';
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
                        emptyState.textContent = 'Unable to filter archived cases. Please try again.';
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
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                clearTimeout(debounceTimer);
                updateCases();
            });
            clearButton.addEventListener('click', () => {
                clearTimeout(debounceTimer);
                textInputs.forEach((control) => control.value = '');
                updateCases();
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-swal-confirm]');
                if (!button) return;
                event.preventDefault();
                const confirmText = button.dataset.swalConfirm || 'Unarchive this case?';
                Swal.fire({
                    icon: 'question',
                    title: confirmText,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, unarchive',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.form.requestSubmit(button);
                    }
                });
            });
        })();
    </script>
</body>

</html>
