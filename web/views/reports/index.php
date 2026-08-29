<?php
require_once __DIR__ . '/../../controllers/ReportController.php';

$controller = new ReportController();
$viewData = $controller->index();

$user = $viewData['user'];
$filters = $viewData['filters'];
$summary = $viewData['summary'];
$charts = $viewData['charts'];
$rows = $viewData['rows'];
$options = $viewData['options'];
$isPrint = ($_GET['export'] ?? '') === 'print';
$errors = $viewData['errors'] ?? [];

function h($value) {
    return htmlspecialchars((string) $value);
}

function selected($left, $right) {
    return (string) $left === (string) $right ? 'selected' : '';
}

function query_with(array $extra) {
    $params = array_merge($_GET, $extra);

    return '?' . http_build_query($params);
}

function chart_payload(array $rows) {
    return [
        'labels' => array_map(fn($row) => $row['label'], $rows),
        'values' => array_map(fn($row) => (int) $row['total'], $rows),
    ];
}

function applied_filter_labels(array $filters, array $options) {
    $labels = [];
    foreach (['date_from' => 'Date From', 'date_to' => 'Date To', 'year' => 'Year', 'status' => 'Status', 'classification' => 'Classification', 'college' => 'College'] as $key => $label) {
        if (($filters[$key] ?? '') !== '') $labels[] = $label . ': ' . $filters[$key];
    }
    if (!empty($filters['month'])) $labels[] = 'Month: ' . date('F', mktime(0, 0, 0, (int) $filters['month'], 1));
    if (!empty($filters['coordinator'])) {
        $name = 'Account #' . (int) $filters['coordinator'];
        foreach ($options['coordinators'] as $coordinator) {
            if ((int) $coordinator['account_id'] === (int) $filters['coordinator']) $name = trim($coordinator['first_name'] . ' ' . $coordinator['last_name']);
        }
        $labels[] = 'Coordinator: ' . $name;
    }
    return $labels;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | SICMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; background: #f5f7f4; display: block; justify-content: flex-start; padding: 0; }
        .header { align-items: center; background: #123c1b; color: #fff; display: flex; justify-content: space-between; gap: 16px; padding: 22px 30px; }
        .header h1 { font-size: 24px; margin-bottom: 4px; }
        .header p { color: #dbe9d9; font-size: 13px; }
        .header a { background: #fff; border-radius: 8px; color: #123c1b; padding: 10px 14px; text-decoration: none; }
        .wrap { max-width: 1320px; margin: 0 auto; padding: 28px; }
        .report-section { margin-bottom: 28px; }
        .section-heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 14px; }
        .section-heading h2 { color: #123c1b; font-size: 18px; margin: 0; }
        .section-heading p { color: #637060; font-size: 12px; margin: 3px 0 0; }
        .cards { display: grid; gap: 12px; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .card, .panel, .filters { background: #fff; border: 1px solid #dce5da; border-radius: 8px; box-shadow: 0 3px 12px rgba(18, 60, 27, 0.06); }
        .card { min-height: 108px; padding: 16px; }
        .card span { color: #637060; display: block; font-size: 12px; line-height: 1.35; margin-bottom: 12px; min-height: 32px; }
        .card strong { color: #123c1b; font-size: 28px; line-height: 1; }
        .filters { margin-bottom: 28px; padding: 20px; }
        .filter-groups { display: grid; gap: 16px; }
        .filter-group { border: 0; border-top: 1px solid #e4ece2; margin: 0; padding: 16px 0 0; }
        .filter-group:first-child { border-top: 0; padding-top: 0; }
        .filter-group legend { color: #123c1b; font-size: 13px; font-weight: 700; padding: 0 10px 0 0; }
        .filter-grid { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .filter-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .field { display: flex; flex-direction: column; gap: 6px; }
        label { color: #536052; font-size: 13px; }
        input, select { border: 1px solid #b9c7b7; border-radius: 8px; font: inherit; padding: 10px 12px; }
        .actions { align-items: center; border-top: 1px solid #e4ece2; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-top: 18px; padding-top: 18px; }
        .btn { align-items: center; border: 0; border-radius: 8px; cursor: pointer; display: inline-flex; font: inherit; gap: 7px; min-height: 40px; padding: 10px 14px; text-decoration: none; }
        .btn-primary { background: #1A9D00; color: #fff; }
        .btn-secondary { background: #eef5ed; color: #123c1b; }
        .charts { display: grid; gap: 18px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 28px; }
        .panel { padding: 18px; }
        .panel h2 { color: #123c1b; font-size: 17px; margin-bottom: 12px; }
        .chart-box { height: 300px; position: relative; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; min-width: 980px; width: 100%; }
        th, td { border-bottom: 1px solid #edf4eb; padding: 11px 10px; text-align: left; }
        th { color: #536052; font-size: 12px; text-transform: uppercase; }
        td { color: #172017; font-size: 13px; }
        @media (max-width: 1100px) {
            .cards { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .charts { grid-template-columns: 1fr; }
        }
        @media print {
            .sidebar, .app-topbar, .filters, .actions, .reports-toolbar, .sicms-pagination, script { display: none !important; }
            .dashboard-shell { display: block; }
            body { background: #fff; }
            .wrap { max-width: none; padding: 0; }
            .print-report-header { display: block !important; margin-bottom: 18px; }
            .charts, .panel { break-inside: avoid; }
            .table-wrap { overflow: visible; }
        }
        .print-report-header { display: none; }
        .print-brand { align-items: center; display: flex; gap: 12px; }
        .print-brand img { height: 54px; width: 54px; }
        .print-filters { color: #536052; font-size: 12px; margin-top: 8px; }
        .filter-error { background: #fff5f5; border: 1px solid #dc3545; border-radius: 8px; color: #b42318; margin-bottom: 16px; padding: 12px 14px; }
        .chart-empty { align-items: center; color: #637060; display: none; inset: 0; justify-content: center; position: absolute; }
        .hearing-section { background: #edf5ec; border: 1px solid #dce8da; border-radius: 8px; padding: 18px; }
        .hearing-stats { display: grid; gap: 14px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .hearing-stat { border-top: 3px solid #1A9D00; min-height: 92px; padding: 15px 16px; }
        .hearing-stat span { color: #637060; display: block; font-size: 12px; margin-bottom: 10px; }
        .hearing-stat strong { color: #123c1b; font-size: 25px; }
        @media (max-width: 700px) {
            .wrap { padding: 18px 14px; }
            .cards, .filter-grid, .filter-grid.two, .hearing-stats { grid-template-columns: 1fr; }
            .actions { align-items: stretch; flex-direction: column; }
            .btn { justify-content: center; width: 100%; }
            .reports-toolbar { align-items: stretch; flex-direction: column; }
        }

        /* ===== Modernized reports (matches dashboard) ===== */
        .wrap .panel,
        .wrap .card {
            background: #fff !important;
            border: 1px solid rgba(219, 231, 216, .9) !important;
            border-radius: 14px !important;
            box-shadow: 0 4px 16px rgba(18, 60, 27, .06) !important;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .wrap .panel:hover,
        .wrap .card:hover {
            box-shadow: 0 12px 28px rgba(18, 60, 27, .13) !important;
            transform: translateY(-2px);
        }

        .section-heading h2 {
            align-items: center;
            display: flex;
            font-size: 15.5px;
            gap: 9px;
            margin: 0;
        }

        /* .section-heading h2::before {
            background: #1A9D00;
            border-radius: 999px;
            content: "";
            display: inline-block;
            height: 8px;
            width: 8px;
        } */

        .cards .card span {
            color: #5f6f5c;
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: .03em;
            margin-bottom: 10px;
            min-height: 0;
            text-transform: uppercase;
        }

        .cards .card strong {
            color: #123c1b;
            font-size: 30px;
        }

        .cards .card[data-summary="total_cases"] {
            background: linear-gradient(135deg, #1a9d00 0%, #123c1b 78%) !important;
            border-color: transparent !important;
        }

        .cards .card[data-summary="total_cases"]:hover {
            box-shadow: 0 16px 34px rgba(18, 60, 27, .38) !important;
        }

        .cards .card[data-summary="total_cases"] strong {
            color: #fff;
        }

        .cards .card[data-summary="total_cases"] span {
            color: rgba(255, 255, 255, .88);
        }

        .hearing-section {
            background: transparent;
            border: none;
            margin-bottom: 28px;
            padding: 0;
        }

        .hearing-stat span {
            color: #5f6f5c;
            font-size: 12px;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .wrap input,
        .wrap select {
            border-radius: 10px;
        }

        .wrap input:focus,
        .wrap select:focus {
            border-color: #1A9D00;
            outline: 2px solid rgba(26, 157, 0, .18);
        }

        .wrap table th {
            background: #f4f8f3;
        }

        .wrap table td {
            vertical-align: middle;
        }

        .wrap table tbody tr:hover td {
            background: #f7faf6;
        }

        .reports-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .filters-host {
            position: relative;
        }

        .filters-toggle-btn {
            align-items: center;
            background: #fff;
            border: 1px solid #bfd0bc;
            border-radius: 6px;
            color: #172017;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 13.5px;
            font-weight: 700;
            gap: 8px;
            padding: 9px 16px;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .filters-toggle-btn:hover {
            border-color: #1A9D00;
            color: #167a22;
        }

        .filters-count {
            align-items: center;
            background: #e6f3ea;
            border-radius: 999px;
            color: #1a8c2b;
            display: inline-flex;
            font-size: 11px;
            height: 20px;
            justify-content: center;
            min-width: 20px;
            padding: 0 6px;
        }

        #reportFilters.is-active .filters-toggle-btn,
        #reportFilters.is-active .filters-toggle-btn:hover {
            background: #1A9D00;
            border-color: #1A9D00;
            color: #fff;
        }

        #reportFilters.is-active .filters-count {
            background: #fff;
        }

        .filters-popover {
            background: #fff;
            border: 1px solid rgba(191, 208, 188, .75);
            border-radius: 14px;
            box-shadow: 0 18px 45px rgba(15, 40, 21, .22);
            display: none;
            left: 0;
            padding: 16px 18px;
            position: absolute;
            top: calc(100% + 10px);
            width: min(720px, calc(100vw - 56px));
            z-index: 500;
        }

        .filters-popover.open {
            animation: sicmsReportPop .18s ease-out;
            display: block;
        }

        @keyframes sicmsReportPop {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filters-popover .actions {
            border-top: 1px solid #e4ece2;
            margin-top: 16px;
        }

        .export-group {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .export-label {
            color: #536052;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            margin-right: 2px;
            text-transform: uppercase;
        }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=2">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Reports and Dashboard Analytics'; require __DIR__ . '/../layout/topbar.php'; ?>

    <main class="wrap">
        <section class="print-report-header">
            <div class="print-brand">
                <img src="<?= h(app_url('public/assets/clsulogo.png')) ?>" alt="SICMS logo">
                <div><strong>Student Information and Case Management System</strong><h1>SICMS Reports and Dashboard Analytics</h1></div>
            </div>
            <p>Generated <?= h(date('F d, Y h:i A')) ?> by <?= h(trim($user['first_name'] . ' ' . $user['last_name'])) ?></p>
            <p class="print-filters">Applied filters: <?= h(implode('; ', applied_filter_labels($filters, $options)) ?: 'All records') ?></p>
        </section>

        <div class="reports-toolbar">
            <form id="reportFilters" class="filters-host" method="GET" action="index.php">
                <button type="button" class="filters-toggle-btn" id="reportFiltersToggle" aria-expanded="false" aria-controls="reportFiltersPanel">
                    <i class="bi bi-funnel"></i> Filters
                    <span class="filters-count" id="reportFiltersCount" hidden></span>
                </button>
                <div class="filters-popover" id="reportFiltersPanel">
                    <div class="section-heading">
                        <h2>Report Filters</h2>
                    </div>
                    <div class="filter-groups">
            <fieldset class="filter-group">
                <legend>Date Filters</legend>
                <div class="filter-grid">
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
                        <option value="">All Months</option>
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                            <option value="<?= $month ?>" <?= selected($filters['month'], $month) ?>><?= h(date('F', mktime(0, 0, 0, $month, 1))) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="year">Year</label>
                    <input id="year" type="number" name="year" min="2000" max="2100" value="<?= h($filters['year']) ?>">
                </div>
                </div>
            </fieldset>
            <fieldset class="filter-group">
                <legend>Case Filters</legend>
                <div class="filter-grid two">
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($options['statuses'] as $status): ?>
                            <option value="<?= h($status) ?>" <?= selected($filters['status'], $status) ?>><?= h($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="classification">Classification</label>
                    <select id="classification" name="classification">
                        <option value="">All Classifications</option>
                        <?php foreach ($options['classifications'] as $classification): ?>
                            <option value="<?= h($classification) ?>" <?= selected($filters['classification'], $classification) ?>><?= h($classification) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                </div>
            </fieldset>
            <fieldset class="filter-group">
                <legend>Personnel and College</legend>
                <div class="filter-grid two">
                <div class="field">
                    <label for="coordinator">Coordinator</label>
                    <select id="coordinator" name="coordinator">
                        <option value="">All Coordinators</option>
                        <?php foreach ($options['coordinators'] as $coordinator): ?>
                            <option value="<?= (int) $coordinator['account_id'] ?>" <?= selected($filters['coordinator'], $coordinator['account_id']) ?>>
                                <?= h(trim($coordinator['first_name'] . ' ' . $coordinator['last_name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="college">College</label>
                    <select id="college" name="college">
                        <option value="">All Colleges</option>
                        <?php foreach ($options['colleges'] as $college): ?>
                            <option value="<?= h($college) ?>" <?= selected($filters['college'], $college) ?>><?= h($college) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                </div>
            </fieldset>
            </div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Apply Filters</button>
                        <button class="btn btn-secondary" id="resetFilters" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                    </div>
                </div>
            </form>
            <div class="export-group">
                <span class="export-label"><i class="bi bi-download"></i> Export</span>
                <a class="btn btn-primary" data-export="pdf" href="<?= h(query_with(['export' => 'pdf', 'csrf_token' => Security::csrfToken()])) ?>"><i class="bi bi-file-earmark-pdf"></i> Generate PDF</a>
                <a class="btn btn-primary" data-export="excel" href="<?= h(query_with(['export' => 'excel', 'csrf_token' => Security::csrfToken()])) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Generate Excel</a>
                <a class="btn btn-primary" data-export="print" href="<?= h(query_with(['export' => 'print', 'csrf_token' => Security::csrfToken()])) ?>" target="_blank"><i class="bi bi-printer"></i> Print Report</a>
            </div>
        </div>

        <section class="report-section" aria-labelledby="case-summary-title">
            <div class="section-heading">
                <h2 id="case-summary-title">Case Summary</h2>
            </div>
            <div class="cards">
            <?php foreach ([
                'Total Cases' => 'total_cases',
                'Submitted' => 'submitted_cases',
                'Verified' => 'verified_cases',
                'Returned for Revision' => 'returned_for_revision_cases',
                'Rejected' => 'rejected_cases',
                'Resolved' => 'resolved_cases',
                'Archived' => 'archived_cases',
            ] as $label => $key): ?>
                <article class="card">
                    <span><?= h($label) ?></span>
                    <strong data-summary="<?= h($key) ?>"><?= (int) $summary[$key] ?></strong>
                </article>
            <?php endforeach; ?>
            </div>
        </section>

        <section class="report-section hearing-section" aria-labelledby="hearing-summary-title">
            <div class="section-heading">
                <h2 id="hearing-summary-title">Hearing Summary</h2>
            </div>
            <div class="hearing-stats">
                <?php foreach (['Scheduled Hearings' => 'scheduled_hearings', 'Hearings Today' => 'hearings_today', 'Completed Hearings' => 'completed_hearings'] as $label => $key): ?>
                    <article class="panel hearing-stat">
                        <span><?= h($label) ?></span>
                        <strong data-summary="<?= h($key) ?>"><?= (int) $summary[$key] ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="filter-error" id="reportFilterError" <?= empty($errors) ? 'hidden' : '' ?>><?= h(implode(' ', $errors)) ?></div>

        <div class="section-heading"><h2>Case Analytics</h2></div>
        <section class="charts" aria-label="Report charts">
            <?php foreach ([
                'casesByMonth' => 'Cases by Month',
                'casesByClassification' => 'Cases by Classification',
                'casesByStatus' => 'Cases by Status',
                'casesByCollege' => 'Cases by College',
                'casesByCoordinator' => 'Cases by Coordinator',
            ] as $key => $title): ?>
                <article class="panel">
                    <h2><?= h($title) ?></h2>
                    <div class="chart-box">
                        <canvas id="<?= h($key) ?>"></canvas>
                        <div class="chart-empty" data-chart-empty="<?= h($key) ?>">No records found for the selected filters.</div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="panel">
            <h2>Report Details</h2>
            <div class="table-wrap">
                <table <?= $isPrint ? 'data-no-pagination="true"' : '' ?>>
                    <thead>
                        <tr>
                            <th>Case Number</th>
                            <th>Complainant</th>
                            <th>Type</th>
                            <th>College</th>
                            <th>Classification</th>
                            <th>Status</th>
                            <th>Coordinator</th>
                            <th>Hearings</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="9">No records found for the selected filters.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= h($row['case_number']) ?></td>
                                <td><?= h($row['complainant_name']) ?></td>
                                <td><?= h($row['complainant_type'] ?? 'Student') ?></td>
                                <td><?= h($row['complainant_college']) ?></td>
                                <td><?= h($row['case_classification']) ?></td>
                                <td><span class="status"><?= h($row['status']) ?></span></td>
                                <td><?= h(trim($row['coordinator_name']) ?: 'Unassigned') ?></td>
                                <td><?= (int) $row['hearing_count'] ?></td>
                                <td><?= h(date('M d, Y h:i A', strtotime($row['submitted_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let chartData = <?= json_encode([
            'casesByMonth' => chart_payload($charts['casesByMonth']),
            'casesByClassification' => chart_payload($charts['casesByClassification']),
            'casesByStatus' => chart_payload($charts['casesByStatus']),
            'casesByCollege' => chart_payload($charts['casesByCollege']),
            'casesByCoordinator' => chart_payload($charts['casesByCoordinator']),
            'hearingsByMonth' => chart_payload($charts['hearingsByMonth']),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const palette = ['#1A9D00', '#0d7b66', '#4338ca', '#e0a800', '#be123c', '#557a95', '#7e22ce'];
        const chartInstances = {};

        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6b7a67';
        }

        const reportTooltipStyle = {
            backgroundColor: '#123c1b',
            titleColor: '#ffffff',
            bodyColor: 'rgba(255, 255, 255, .86)',
            cornerRadius: 10,
            padding: 10,
            displayColors: false
        };

        const lineGradientFill = (context) => {
            const { ctx, chartArea } = context.chart;
            if (!chartArea) return 'rgba(26, 157, 0, .16)';
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, 'rgba(26, 157, 0, .30)');
            gradient.addColorStop(1, 'rgba(26, 157, 0, .01)');
            return gradient;
        };

        const renderCharts = (dataSet) => Object.entries(dataSet).forEach(([id, data]) => {
            const canvas = document.getElementById(id);

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            const emptyState = document.querySelector(`[data-chart-empty="${id}"]`);
            const hasData = data.values.some((value) => Number(value) > 0);
            canvas.hidden = !hasData;
            if (emptyState) emptyState.style.display = hasData ? 'none' : 'flex';

            try {
                chartInstances[id]?.destroy();
                Chart.getChart(canvas)?.destroy();
                if (!hasData) return;

                const isLine = id.includes('Month');
                const dataset = isLine ? {
                    label: 'Total',
                    data: data.values,
                    borderColor: '#1A9D00',
                    backgroundColor: lineGradientFill,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#1A9D00',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                } : {
                    label: 'Total',
                    data: data.values,
                    backgroundColor: palette,
                    borderWidth: 0,
                    borderRadius: 8,
                    maxBarThickness: 36
                };

                const config = {
                    type: isLine ? 'line' : 'bar',
                    data: { labels: data.labels, datasets: [dataset] },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: reportTooltipStyle
                        },
                        scales: {
                            x: { grid: { display: false }, border: { display: false } },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: 'rgba(219, 231, 216, .55)', drawTicks: false },
                                ticks: { precision: 0, padding: 8 }
                            }
                        }
                    }
                };
                if (isLine) config.options.interaction = { mode: 'index', intersect: false };

                chartInstances[id] = new Chart(canvas, config);
            } catch (error) {
                console.error(`Unable to render chart ${id}:`, error);
            }
        });

        const reportForm = document.getElementById('reportFilters');
        const resetFilters = document.getElementById('resetFilters');
        const reportTableBody = document.getElementById('reportTableBody');
        const reportFilterError = document.getElementById('reportFilterError');
        const csrfToken = <?= json_encode(Security::csrfToken()) ?>;

        const addCell = (row, value) => {
            const cell = document.createElement('td');
            cell.textContent = value ?? '';
            row.appendChild(cell);
        };

        const formatDate = (value) => {
            const date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('en-US', {
                month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }).format(date);
        };

        const renderRows = (rows) => {
            reportTableBody.replaceChildren();
            if (!rows.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 9;
                cell.textContent = 'No records found for the selected filters.';
                row.appendChild(cell);
                reportTableBody.appendChild(row);
                return;
            }
            rows.forEach((item) => {
                const row = document.createElement('tr');
                addCell(row, item.case_number);
                addCell(row, item.complainant_name);
                addCell(row, item.complainant_type || 'Student');
                addCell(row, item.complainant_college);
                addCell(row, item.case_classification);
                const statusCell = document.createElement('td');
                const statusBadge = document.createElement('span');
                statusBadge.className = 'status';
                statusBadge.textContent = item.status;
                statusCell.appendChild(statusBadge);
                row.appendChild(statusCell);
                addCell(row, (item.coordinator_name || '').trim() || 'Unassigned');
                addCell(row, item.hearing_count);
                addCell(row, formatDate(item.submitted_at));
                reportTableBody.appendChild(row);
            });
        };

        const updateExportLinks = (params) => {
            document.querySelectorAll('[data-export]').forEach((link) => {
                const exportParams = new URLSearchParams(params);
                exportParams.set('export', link.dataset.export);
                exportParams.set('csrf_token', csrfToken);
                link.href = `${reportForm.action}?${exportParams.toString()}`;
            });
        };

        const updateReport = async () => {
            const params = new URLSearchParams(new FormData(reportForm));
            const requestParams = new URLSearchParams(params);
            requestParams.set('ajax', '1');
            reportForm.setAttribute('aria-busy', 'true');
            reportFilterError.hidden = true;
            try {
                const response = await fetch(`${reportForm.action}?${requestParams.toString()}`, {
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error((data.errors || [data.message || 'Unable to update report.']).join(' '));
                Object.entries(data.summary).forEach(([key, value]) => {
                    const card = document.querySelector(`[data-summary="${key}"]`);
                    if (card) card.textContent = value;
                });
                chartData = data.charts;
                renderRows(data.rows);
                renderCharts(chartData);
                updateExportLinks(params);
                const query = params.toString();
                history.replaceState(null, '', query ? `${reportForm.action}?${query}` : reportForm.action);
            } catch (error) {
                reportFilterError.textContent = error.message || 'Unable to update the report. Please try again.';
                reportFilterError.hidden = false;
            } finally {
                reportForm.removeAttribute('aria-busy');
                updateReportFilterButton();
            }
        };

        reportForm.addEventListener('submit', (event) => {
            event.preventDefault();
            updateReport();
        });

        resetFilters.addEventListener('click', () => {
            Array.from(reportForm.elements).forEach((control) => {
                if (control.name) control.value = '';
            });
            updateReport();
        });

        const reportFiltersToggle = document.getElementById('reportFiltersToggle');
        const reportFiltersPanel = document.getElementById('reportFiltersPanel');
        const reportFiltersCount = document.getElementById('reportFiltersCount');

        const closeReportFilters = () => {
            if (!reportFiltersPanel || !reportFiltersToggle) return;
            reportFiltersPanel.classList.remove('open');
            reportFiltersToggle.setAttribute('aria-expanded', 'false');
        };

        const updateReportFilterButton = () => {
            if (!reportForm || !reportFiltersToggle) return;
            const activeCount = [...new FormData(reportForm).entries()].filter(([, value]) => String(value).trim() !== '').length;
            reportForm.classList.toggle('is-active', activeCount > 0);
            if (reportFiltersCount) {
                reportFiltersCount.hidden = activeCount === 0;
                reportFiltersCount.textContent = activeCount;
            }
        };

        reportFiltersToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = reportFiltersPanel.classList.toggle('open');
            reportFiltersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', (event) => {
            if (!reportFiltersPanel?.classList.contains('open')) return;
            if (!reportFiltersPanel.contains(event.target)) closeReportFilters();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeReportFilters();
        });
        window.addEventListener('pageshow', () => updateReportFilterButton());
        updateReportFilterButton();

        renderCharts(chartData);

        <?php if ($isPrint): ?>
            window.addEventListener('load', () => setTimeout(() => window.print(), 500));
        <?php endif; ?>
    </script>
</body>

</html>
