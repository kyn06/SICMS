<?php
require_once __DIR__ . '/../../controllers/ComplaintController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new ComplaintController();
$viewData = $controller->handleTrackingRequest();
$user = $viewData['user'];
$statuses = ['Submitted', 'Verified', 'Returned for Revision', 'Rejected', 'Resolved', 'Archived'];
$allCases = Complaint::forStudent((int) $user['account_id'], 10000, ['sort' => 'newest'], 0);

function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function status_class($status) { return strtolower(str_replace(' ', '-', $status)); }
function filtered_student_cases(array $cases, array $input) {
    $search = trim((string) ($input['search'] ?? ''));
    $status = in_array(($input['status'] ?? ''), ['Submitted', 'Verified', 'Returned for Revision', 'Rejected', 'Resolved', 'Archived'], true) ? $input['status'] : '';
    $year = preg_match('/^\d{4}$/', (string) ($input['year'] ?? '')) ? (string) $input['year'] : '';

    return array_values(array_filter($cases, function ($case) use ($search, $status, $year) {
        $matchesSearch = $search === '' || stripos($case['case_number'], $search) !== false || stripos($case['case_classification'], $search) !== false;
        $matchesStatus = $status === '' || $case['status'] === $status;
        $matchesYear = $year === '' || date('Y', strtotime($case['submitted_at'])) === $year;
        return $matchesSearch && $matchesStatus && $matchesYear;
    }));
}
function case_payload(array $case) {
    return [
        'id' => (int) $case['complaint_id'],
        'case_number' => $case['case_number'],
        'complainant_type' => $case['complainant_type'] ?? 'Student',
        'classification' => $case['case_classification'],
        'submitted' => date('M d, Y', strtotime($case['submitted_at'])),
        'updated' => date('M d, Y h:i A', strtotime($case['updated_at'])),
        'status' => $case['status'],
    ];
}
function summary_payload(array $cases) {
    $summary = ['total' => count($cases), 'submitted' => 0, 'verified' => 0, 'resolved' => 0];
    foreach ($cases as $case) {
        $key = strtolower($case['status']);
        if (isset($summary[$key])) $summary[$key]++;
    }
    return $summary;
}

$filteredCases = filtered_student_cases($allCases, $_GET);
if (($_GET['ajax'] ?? '') === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['summary' => summary_payload($filteredCases), 'cases' => array_map('case_payload', $filteredCases)]);
    exit;
}
$summary = summary_payload($filteredCases);
$successMessage = $_SESSION['complaint_success'] ?? '';
unset($_SESSION['complaint_success']);
$years = array_values(array_unique(array_map(fn($case) => date('Y', strtotime($case['submitted_at'])), $allCases)));
rsort($years);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track My Cases | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; display: block; justify-content: flex-start; padding: 0; }
        .track-wrap { max-width: 100%; margin: 0 auto; padding: 24px; }
        .track-intro { align-items: center; display: flex; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .track-intro h1 { color: #172017; font-size: 24px; margin: 0 0 4px; }
        .track-intro p { color: #637060; font-size: 13px; margin: 0; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
        .summary-card { align-items: center; background: #fff; border: 1px solid #dce5da; border-radius: 8px; display: flex; gap: 12px; min-height: 88px; padding: 16px; box-shadow: 0 4px 14px rgba(18,60,27,.06); }
        .summary-icon { align-items: center; background: #eaf5e8; border-radius: 8px; color: #167322; display: flex; font-size: 20px; height: 42px; justify-content: center; width: 42px; }
        .summary-label { color: #637060; font-size: 12px; font-weight: 700; }
        .summary-value { color: #172017; font-size: 24px; font-weight: 800; line-height: 1.1; margin-top: 3px; }
        .filter-panel { background: #fff; border: 1px solid #dce5da; border-radius: 8px; display: grid; grid-template-columns: minmax(220px, 1fr) 190px 150px auto; gap: 12px; padding: 16px; margin-bottom: 18px; }
        .search-field { position: relative; }
        .search-field i { color: #71806e; left: 13px; position: absolute; top: 50%; transform: translateY(-50%); }
        .search-field input { padding-left: 38px; }
        .filter-actions { align-items: end; display: flex; gap: 8px; }
        .table-panel { background: #fff; border: 1px solid #dce5da; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 14px rgba(18,60,27,.06); }
        .table-scroll { overflow-x: auto; }
        .cases-table { border-collapse: collapse; min-width: 900px; width: 100%; }
        .cases-table th { background: #123c1b; color: #fff; font-size: 12px; padding: 13px 14px; text-align: left; }
        .cases-table td { border-bottom: 1px solid #e6ece4; color: #263225; font-size: 13px; padding: 14px; vertical-align: middle; }
        .cases-table tbody tr:hover { background: #f8fbf7; }
        .case-link { color: #146d20; font-weight: 800; text-decoration: none; }
        .status-pill { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 800; padding: 6px 9px; white-space: nowrap; }
        .status-submitted, .status-returned-for-revision { background: #fff5d8; color: #825e00; }
        .status-verified { background: #e7f0ff; color: #275ca8; }
        .status-resolved { background: #e5f6e3; color: #157000; }
        .status-rejected { background: #fff0ef; color: #a92c23; }
        .status-archived { background: #edf0ed; color: #59635a; }
        .row-actions { display: flex; flex-wrap: wrap; gap: 7px; }
        .row-actions .btn { padding: 7px 10px; font-size: 12px; }
        .empty-state { padding: 52px 20px; text-align: center; }
        .empty-state i { color: #8eaa8a; font-size: 38px; }
        .empty-state h2 { color: #243123; font-size: 18px; margin: 10px 0 5px; }
        .empty-state p { color: #657164; font-size: 13px; margin: 0 0 16px; }
        .pagination { align-items: center; display: flex; justify-content: space-between; padding: 13px 16px; }
        .pagination-info { color: #657164; font-size: 12px; }
        .pagination-buttons { display: flex; gap: 5px; }
        .pagination-buttons button { min-width: 34px; }
        .loading { opacity: .55; pointer-events: none; }
        @media (max-width: 900px) { .summary-grid { grid-template-columns: repeat(2, 1fr); } .filter-panel { grid-template-columns: 1fr 1fr; } .search-field { grid-column: 1 / -1; } }
        @media (max-width: 560px) { .track-wrap { padding: 16px; } .track-intro { align-items: flex-start; flex-direction: column; } .summary-grid, .filter-panel { grid-template-columns: 1fr; } .search-field { grid-column: auto; } .filter-actions { align-items: stretch; } }
    </style>
</head>
<body>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="app-content">
        <?php $pageTitle = 'Track My Cases'; require __DIR__ . '/../layout/topbar.php'; ?>
        <main class="track-wrap">
            <?php if ($successMessage): ?><div class="alert alert-success" role="status"><?= h($successMessage) ?></div><?php endif; ?>
            <header class="track-intro">
                <div><h1>My Complaints</h1><p>Monitor the current status of complaints you have submitted.</p></div>
                <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> Submit Complaint</a>
            </header>

            <section class="summary-grid" aria-label="Complaint summary">
                <?php foreach ([['total','Total Complaints','bi-folder2-open'],['submitted','Submitted','bi-send'],['verified','Verified','bi-patch-check'],['resolved','Resolved','bi-check2-circle']] as [$key,$label,$icon]): ?>
                    <article class="summary-card"><span class="summary-icon"><i class="bi <?= h($icon) ?>"></i></span><div><div class="summary-label"><?= h($label) ?></div><div class="summary-value" data-summary="<?= h($key) ?>"><?= (int) $summary[$key] ?></div></div></article>
                <?php endforeach; ?>
            </section>

            <form class="filter-panel" id="caseFilters">
                <div class="field search-field"><label for="search">Search complaints</label><div><i class="bi bi-search"></i><input id="search" name="search" autocomplete="off" placeholder="Case number or classification"></div></div>
                <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All Statuses</option><?php foreach ($statuses as $status): ?><option value="<?= h($status) ?>"><?= h($status) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="year">Year</label><select id="year" name="year"><option value="">All Years</option><?php foreach ($years as $year): ?><option value="<?= h($year) ?>"><?= h($year) ?></option><?php endforeach; ?></select></div>
                <div class="filter-actions"><button class="btn btn-secondary" id="resetFilters" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button></div>
            </form>

            <section class="table-panel" id="caseResults" aria-live="polite">
                <div class="table-scroll"><table class="cases-table"><thead><tr><th>Case Number</th><th>Complainant Type</th><th>Classification</th><th>Date Submitted</th><th>Current Status</th><th>Last Updated</th><th>Actions</th></tr></thead><tbody id="caseRows"></tbody></table></div>
                <div class="empty-state" id="emptyState" hidden><i class="bi bi-folder2-open"></i><h2>No complaints found.</h2><p id="emptyMessage">You have not submitted any complaints yet. Click Submit Complaint to file your first complaint.</p><a class="btn btn-primary" href="create.php">Submit Complaint</a></div>
                <div class="pagination" id="pagination"><span class="pagination-info" id="paginationInfo"></span><div class="pagination-buttons" id="paginationButtons"></div></div>
            </section>
        </main>
    </div>
</div>
<script>
    const initialCases = <?= json_encode(array_map('case_payload', $filteredCases), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
    const form = document.getElementById('caseFilters');
    const results = document.getElementById('caseResults');
    const rows = document.getElementById('caseRows');
    const emptyState = document.getElementById('emptyState');
    const pagination = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    const perPage = 10;
    let cases = initialCases;
    let currentPage = 1;
    let debounceTimer;

    const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
    const statusClass = status => status.toLowerCase().replaceAll(' ', '-');

    function render() {
        const totalPages = Math.max(1, Math.ceil(cases.length / perPage));
        currentPage = Math.min(currentPage, totalPages);
        const pageCases = cases.slice((currentPage - 1) * perPage, currentPage * perPage);
        rows.innerHTML = pageCases.map(item => `<tr><td><a class="case-link" href="case_details.php?id=${item.id}">${escapeHtml(item.case_number)}</a></td><td>${escapeHtml(item.complainant_type)}</td><td>${escapeHtml(item.classification)}</td><td>${escapeHtml(item.submitted)}</td><td><span class="status-pill status-${statusClass(item.status)}">${escapeHtml(item.status)}</span></td><td>${escapeHtml(item.updated)}</td><td><div class="row-actions">${item.status === 'Returned for Revision' ? '<a class="btn btn-secondary" href="revise.php?id=' + item.id + '"><i class="bi bi-pencil-square"></i> Revise Complaint</a>' : ''}<a class="btn btn-primary" href="case_details.php?id=${item.id}"><i class="bi bi-eye"></i> View Details</a></div></td></tr>`).join('');
        emptyState.hidden = cases.length > 0;
        const filtersActive = Boolean(document.getElementById('search').value || document.getElementById('status').value || document.getElementById('year').value);
        document.getElementById('emptyMessage').textContent = filtersActive
            ? 'No complaints match your current search or filters.'
            : 'You have not submitted any complaints yet. Click Submit Complaint to file your first complaint.';
        document.querySelector('.table-scroll').hidden = cases.length === 0;
        pagination.hidden = cases.length === 0;
        paginationInfo.textContent = cases.length ? `Page ${currentPage} of ${totalPages} · ${cases.length} complaint${cases.length === 1 ? '' : 's'}` : '';
        paginationButtons.replaceChildren();
        for (let page = 1; page <= totalPages; page++) {
            const button = document.createElement('button'); button.type = 'button'; button.className = `btn ${page === currentPage ? 'btn-primary' : 'btn-secondary'}`; button.textContent = page;
            button.addEventListener('click', () => { currentPage = page; render(); }); paginationButtons.appendChild(button);
        }
    }

    async function loadCases() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            results.classList.add('loading');
            try {
                const query = new URLSearchParams(new FormData(form)); query.set('ajax', '1');
                const response = await fetch(`my_cases.php?${query}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok) throw new Error('Unable to load complaints.');
                const data = await response.json(); cases = data.cases; currentPage = 1;
                Object.entries(data.summary).forEach(([key, value]) => { const target = document.querySelector(`[data-summary="${key}"]`); if (target) target.textContent = value; });
                render();
            } catch (error) { console.error(error); }
            finally { results.classList.remove('loading'); }
        }, 350);
    }

    form.addEventListener('input', loadCases);
    form.addEventListener('change', loadCases);
    document.getElementById('resetFilters').addEventListener('click', () => { form.reset(); loadCases(); });
    render();
</script>
</body>
</html>
