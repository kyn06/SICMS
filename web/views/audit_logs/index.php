<?php
require_once __DIR__ . '/../../controllers/AuditLogController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new AuditLogController();
if (($_GET['ajax'] ?? '') === '1') {
    $controller->search();
}
$viewData = $controller->index();

$user = $viewData['user'];
$filters = $viewData['filters'];
$logs = $viewData['logs'];
$approvals = $viewData['approvals'] ?? [];
$options = $viewData['options'];
$pagination = $viewData['pagination'];
$filterError = $viewData['filterError'] ?? '';

function h($value) {
    return htmlspecialchars((string) $value);
}

function selected($left, $right) {
    return (string) $left === (string) $right ? 'selected' : '';
}

function page_url($page) {
    $params = $_GET;
    $params['page'] = $page;
    unset($params['export']);

    return '?' . http_build_query($params);
}

function with_query(array $extra) {
    $params = $_GET;
    unset($params['page'], $params['export'], $params['ajax']);
    $params = array_merge($params, $extra);

    return '?' . http_build_query($params);
}

function user_initials($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

$auditEndpoint = app_route('audit_logs.index');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | DARIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; background: var(--bg-page, #f5f7f4); display: block; justify-content: flex-start; padding: 0; color: var(--text-primary, #172017); }
        .header { align-items: center; background: var(--bg-sidebar, #123c1b); color: #fff; display: flex; justify-content: space-between; gap: 16px; padding: 22px 30px; }
        .header h1 { font-size: 24px; margin-bottom: 4px; }
        .header p { color: rgba(255,255,255,0.8); font-size: 13px; }
        .header a { background: var(--surface-primary, #fff); border-radius: 8px; color: var(--text-secondary, #123c1b); padding: 10px 14px; text-decoration: none; }
        .wrap { max-width: 100%; width: 100%; margin: 0 auto; padding: 24px; box-sizing: border-box; }
        .panel { background: var(--surface-primary, #fff); border: 1px solid var(--border-primary, #dce5da); border-radius: 8px; margin-bottom: 18px; padding: 16px; box-sizing: border-box; max-width: 100%; }
        .filter-grid { display: grid; gap: 12px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        label { color: var(--text-muted, #536052); font-size: 13px; }
        input, select { border: 1px solid var(--input-border, #b9c7b7); border-radius: 8px; font: inherit; padding: 10px 12px; background: var(--input-bg, #fff); color: var(--text-primary, #172017); box-sizing: border-box; width: 100%; max-width: 100%; }
        .actions, .pagination { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-top: 14px; }
        .btn { border: 0; border-radius: 8px; cursor: pointer; font: inherit; padding: 10px 14px; text-decoration: none; box-sizing: border-box; }
        .btn-primary { background: var(--accent, #1A9D00); color: #fff; }
        .btn-secondary { background: rgba(26, 157, 0, 0.08); color: var(--text-secondary, #123c1b); }
        .btn-page { background: var(--surface-primary, #fff); border: 1px solid var(--border-primary, #dce5da); color: var(--text-primary, #123c1b); }
        .btn-page.active { background: var(--bg-sidebar, #123c1b); color: #fff; }
        .table-wrap { overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch; }
        table { border-collapse: collapse; width: 100%; table-layout: auto; }
        th, td { border-bottom: 1px solid var(--divider, #edf4eb); padding: 11px 10px; text-align: left; vertical-align: top; word-break: break-word; overflow-wrap: break-word; }
        th { color: var(--text-muted, #536052); font-size: 12px; text-transform: uppercase; white-space: nowrap; }
        td { color: var(--text-primary, #172017); font-size: 13px; line-height: 1.45; }
        .muted { color: var(--text-muted, #6b766a); font-size: 12px; }
        @media (max-width: 1120px) {
            .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .filter-grid { grid-template-columns: 1fr; }
            .header { align-items: flex-start; flex-direction: column; }
        }

        .audit-toolbar {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 18px;
            max-width: 100%;
            box-sizing: border-box;
        }

        .audit-search {
            flex: 1 1 300px;
            max-width: 100%;
            position: relative;
        }

        .audit-search i {
            color: var(--text-muted, #6b766a);
            left: 13px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .audit-search input { padding-left: 38px; width: 100%; box-sizing: border-box; }
        mark.audit-search-match { background: #fff19a; border-radius: 2px; color: inherit; padding: 0 1px; }

        .filters-host {
            position: relative;
        }

        .filters-toggle-btn {
            align-items: center;
            background: var(--surface-primary, #fff);
            border: 1px solid var(--border-primary, #bfd0bc);
            border-radius: 6px;
            color: var(--text-primary, #172017);
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
            border-color: var(--accent, #1A9D00);
            color: var(--accent, #167a22);
        }

        .filters-count {
            align-items: center;
            background: rgba(26, 157, 0, 0.08);
            border-radius: 999px;
            color: var(--accent, #1a8c2b);
            display: inline-flex;
            font-size: 11px;
            height: 20px;
            justify-content: center;
            min-width: 20px;
            padding: 0 6px;
        }

        #auditFilters.is-active .filters-toggle-btn,
        #auditFilters.is-active .filters-toggle-btn:hover {
            background: var(--accent, #1A9D00);
            border-color: var(--accent, #1A9D00);
            color: #fff;
        }

        #auditFilters.is-active .filters-count {
            background: #fff;
        }

        .filters-popover {
            background: var(--surface-primary, #fff);
            border: 1px solid rgba(191, 208, 188, .75);
            border-radius: 14px;
            box-shadow: var(--sicms-shadow, 0 18px 45px rgba(15, 40, 21, .22));
            display: none;
            right: 0;
            left: auto;
            padding: 16px 18px;
            position: absolute;
            top: calc(100% + 10px);
            width: min(720px, calc(100vw - 32px));
            max-height: calc(100vh - 160px);
            overflow-y: auto;
            z-index: 500;
            box-sizing: border-box;
        }

        .filters-popover.open {
            animation: sicmsAuditPop .18s ease-out;
            display: block;
        }

        @keyframes sicmsAuditPop {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filters-popover .actions {
            border-top: 1px solid var(--divider, #e4ece2);
            margin-top: 16px;
        }

        .export-group {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 900px) {
            .audit-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .filters-host,
            .export-group {
                width: 100%;
            }

            .export-group .btn {
                flex: 1;
                justify-content: center;
            }
        }

        .pdf-columns-overlay { align-items: center; background: rgba(15, 31, 18, .5); display: flex; inset: 0; justify-content: center; padding: 16px; position: fixed; z-index: 1200; box-sizing: border-box; }
        .pdf-columns-modal { background: #fff; border-radius: 14px; box-shadow: 0 22px 60px rgba(0,0,0,.25); max-width: 520px; padding: 22px; width: 100%; box-sizing: border-box; }
        .pdf-columns-modal h2 { color: #123c1b; font-size: 18px; margin: 0 0 5px; }
        .pdf-columns-modal p { color: #536052; font-size: 13px; margin: 0 0 16px; }
        .pdf-columns-grid { display: grid; gap: 8px 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pdf-columns-grid label { align-items: center; display: flex; font-size: 14px; gap: 8px; cursor: pointer; color: var(--text-primary, #172017); }
        .pdf-columns-grid input { accent-color: #1a9d00; height: 16px; width: 16px; flex-shrink: 0; }
        .pdf-columns-feedback { color: #b42318; font-size: 13px; min-height: 18px; margin: 12px 0 0; }
        .pdf-columns-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; margin-top: 16px; }
        .pdf-columns-tools { display: flex; gap: 8px; margin-top: 14px; }
        @media (max-width: 520px) { .pdf-columns-grid { grid-template-columns: 1fr; } .pdf-columns-actions .btn:last-child { width: 100%; } }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=4">
    <link rel="stylesheet" href="../layout/audit-logs.css?v=2">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Audit Logs'; require __DIR__ . '/../layout/topbar.php'; ?>

    <main class="wrap audit-page">
        <section class="audit-summary" aria-label="Audit log summary">
            <article class="audit-summary-card">
                <div><span>Matching Events</span><strong id="auditMatchingEvents"><?= (int) $pagination['total'] ?></strong></div>
                <i class="bi bi-shield-check" aria-hidden="true"></i>
            </article>
            <article class="audit-summary-card">
                <div><span>Current Page</span><strong id="auditCurrentPage"><?= (int) $pagination['currentPage'] ?> <small>of <?= (int) $pagination['totalPages'] ?></small></strong></div>
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            </article>
            <article class="audit-summary-card">
                <div><span>Events Shown</span><strong id="auditEventsShown"><?= count($logs) ?></strong></div>
                <i class="bi bi-list-check" aria-hidden="true"></i>
            </article>
        </section>

        <form id="auditFilters" class="audit-toolbar" method="GET" action="<?= h($auditEndpoint) ?>" data-audit-endpoint="<?= h($auditEndpoint) ?>" data-no-ajax="true" data-sicms-validate data-sicms-datefrom="date_from" data-sicms-dateto="date_to">
            <div class="audit-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="search">Search audit logs</label>
                <input id="search" name="search" type="search" value="<?= h($filters['search']) ?>" placeholder="Search user, role, action, activity, IP, device, reference, or date">
            </div>
            <div class="filters-host">
                <button type="button" class="filters-toggle-btn" id="auditFiltersToggle" aria-expanded="false" aria-controls="auditFiltersPanel">
                    <i class="bi bi-funnel"></i> Filters
                    <span class="filters-count" id="auditFiltersCount" hidden></span>
                </button>
                <div class="filters-popover" id="auditFiltersPanel">
                    <div class="audit-panel-heading">
                        <span class="audit-heading-icon"><i class="bi bi-funnel" aria-hidden="true"></i></span>
                        <div><h2>Audit Filters</h2><p>Security and activity records</p></div>
                    </div>
            <div class="filter-grid">
                <div class="field">
                    <label for="account_id">User</label>
                    <select id="account_id" name="account_id">
                        <option value="">All Users</option>
                        <?php foreach ($options['users'] as $option): ?>
                            <option value="<?= (int) $option['account_id'] ?>" <?= selected($filters['account_id'], $option['account_id']) ?>>
                                <?= h($option['user_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="">All Roles</option>
                        <?php foreach ($options['roles'] as $role): ?>
                            <option value="<?= h($role) ?>" <?= selected($filters['role'], $role) ?>><?= h($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="action">Action</label>
                    <select id="action" name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($options['actions'] as $action): ?>
                            <option value="<?= h($action) ?>" <?= selected($filters['action'], $action) ?>><?= h($action) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="date_from">Date From</label>
                    <input id="date_from" name="date_from" type="date" value="<?= h($filters['date_from']) ?>">
                </div>
                <div class="field">
                    <label for="date_to">Date To</label>
                    <input id="date_to" name="date_to" type="date" value="<?= h($filters['date_to']) ?>">
                </div>
            </div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Apply Filters</button>
                        <button class="btn btn-secondary" id="resetAuditFilters" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                    </div>
                </div>
            </div>
            <?php if ($filterError): ?>
                <div class="filter-error" id="auditFilterError" role="alert"><?= h($filterError) ?></div>
            <?php else: ?>
                <div class="filter-error" id="auditFilterError" role="alert" hidden></div>
            <?php endif; ?>
            <div class="export-group">
                <button type="button" class="btn btn-primary" id="auditExportPdf"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
            </div>
        </form>

        <div class="pdf-columns-overlay" id="auditExportOverlay" hidden>
            <div class="pdf-columns-modal" role="dialog" aria-modal="true" aria-labelledby="auditExportTitle">
                <form method="GET" action="<?= h($auditEndpoint) ?>" id="auditExportForm" target="_blank">
                    <input type="hidden" name="export" value="pdf">
                    <input type="hidden" name="search" id="exportInputSearch" value="">
                    <input type="hidden" name="account_id" id="exportInputAccountId" value="">
                    <input type="hidden" name="role" id="exportInputRole" value="">
                    <input type="hidden" name="action" id="exportInputAction" value="">
                    <input type="hidden" name="date_from" id="exportInputDateFrom" value="">
                    <input type="hidden" name="date_to" id="exportInputDateTo" value="">

                    <h2 id="auditExportTitle">Choose Columns to Include</h2>
                    <p>Selected columns and active filters apply to this Audit Log PDF report.</p>

                    <div style="background: var(--surface-soft, #f8fbf7); border: 1px solid var(--border-primary, #dce7d9); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 12.5px; color: var(--text-secondary, #536052);">
                        <strong>Active Filters:</strong> <span id="auditExportFiltersSummary">None</span>
                    </div>

                    <div class="pdf-columns-grid" id="auditExportColumnsGrid">
                        <label><input type="checkbox" name="columns[]" value="created_at" checked> Date &amp; Time</label>
                        <label><input type="checkbox" name="columns[]" value="user_name" checked> User</label>
                        <label><input type="checkbox" name="columns[]" value="user_role" checked> Role</label>
                        <label><input type="checkbox" name="columns[]" value="action" checked> Action / Activity</label>
                        <label><input type="checkbox" name="columns[]" value="description" checked> Description / Details</label>
                        <label><input type="checkbox" name="columns[]" value="ip_address" checked> IP Address</label>
                        <label><input type="checkbox" name="columns[]" value="user_agent" checked> Browser / Device</label>
                    </div>

                    <div class="pdf-columns-tools">
                        <button class="btn btn-secondary" type="button" id="auditExportSelectAll">Select All</button>
                        <button class="btn btn-secondary" type="button" id="auditExportReset">Reset</button>
                    </div>

                    <div class="pdf-columns-feedback" id="auditExportFeedback" role="alert"></div>

                    <div class="pdf-columns-actions">
                        <button class="btn btn-secondary" type="button" id="auditExportCancel">Cancel</button>
                        <button class="btn btn-primary" type="submit" id="auditExportSubmit"><i class="bi bi-file-earmark-pdf"></i> Generate PDF</button>
                    </div>
                </form>
            </div>
        </div>

        <section class="panel audit-table-panel audit-history-panel">
            <div class="audit-table-heading">
                <div>
                    <h2>Activity History</h2>
                    <p id="auditResultCount"><?= (int) $pagination['total'] ?> matching event<?= (int) $pagination['total'] === 1 ? '' : 's' ?></p>
                </div>
                <span class="audit-page-indicator" id="auditPageIndicator">Page <?= (int) $pagination['currentPage'] ?> of <?= (int) $pagination['totalPages'] ?></span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User Name</th>
                            <th>User Role</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Browser / Device</th>
                        </tr>
                    </thead>
                    <tbody id="auditLogTableBody">
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7">No audit logs found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="audit-time"><strong><?= h(date('M d, Y', strtotime($log['created_at']))) ?></strong><span><?= h(date('h:i A', strtotime($log['created_at']))) ?></span></td>
                                <td><div class="audit-user"><span class="audit-avatar" aria-hidden="true"><?= h(user_initials($log['user_name'])) ?></span><strong><?= h($log['user_name']) ?></strong></div></td>
                                <td><span class="audit-role"><?= h(ucwords(str_replace(['-', '_'], ' ', $log['user_role']))) ?></span></td>
                                <td><span class="audit-action"><i class="bi bi-activity"></i><?= h($log['action']) ?></span></td>
                                <td class="audit-description"><?= h($log['description']) ?></td>
                                <td><code class="audit-ip"><?= h($log['ip_address']) ?></code></td>
                                <td><span class="audit-device" title="<?= h($log['user_agent']) ?>"><?= h($log['user_agent']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="pagination" id="auditPagination" aria-label="Audit log pagination">
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a class="btn btn-page" data-page="1" href="<?= h($auditEndpoint . page_url(1)) ?>">First</a>
                <?php endif; ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a class="btn btn-page" data-page="<?= (int) $pagination['currentPage'] - 1 ?>" href="<?= h($auditEndpoint . page_url($pagination['currentPage'] - 1)) ?>">Previous</a>
                <?php endif; ?>

                <?php
                    $start = max(1, $pagination['currentPage'] - 2);
                    $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                ?>
                <?php for ($page = $start; $page <= $end; $page++): ?>
                    <a class="btn btn-page <?= $page === $pagination['currentPage'] ? 'active' : '' ?>" data-page="<?= (int) $page ?>" href="<?= h($auditEndpoint . page_url($page)) ?>"<?= $page === $pagination['currentPage'] ? ' aria-current="page"' : '' ?>><?= $page ?></a>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a class="btn btn-page" data-page="<?= (int) $pagination['currentPage'] + 1 ?>" href="<?= h($auditEndpoint . page_url($pagination['currentPage'] + 1)) ?>">Next</a>
                <?php endif; ?>
                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a class="btn btn-page" data-page="<?= (int) $pagination['totalPages'] ?>" href="<?= h($auditEndpoint . page_url($pagination['totalPages'])) ?>">Last</a>
                <?php endif; ?>
            </nav>
        </section>

        <section class="panel audit-table-panel audit-approval-panel">
            <div class="audit-table-heading">
                <div>
                    <h2>Case Activity Approval</h2>
                    <p>Review coordinator actions before they are applied to a case.</p>
                </div>
                <span class="audit-page-indicator"><?= count($approvals) ?> pending</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Activity</th><th>Date</th><th>Performed By</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($approvals)): ?><tr><td colspan="5">No case activities are waiting for approval.</td></tr><?php endif; ?>
                    <?php foreach ($approvals as $approval): ?>
                    <tr>
                        <td><strong><?= h($approval['action_label']) ?></strong><div class="muted">Case <?= h($approval['case_number']) ?> · <?= h($approval['complainant_name']) ?></div><div><?= h(CaseApproval::description($approval)) ?></div></td>
                        <td><?= h(date('M d, Y', strtotime($approval['created_at']))) ?></td>
                        <td><?= h(trim($approval['requester_first_name'] . ' ' . $approval['requester_last_name'])) ?></td>
                        <td><span class="audit-role">Pending</span></td>
                        <td><a class="btn btn-secondary" href="../cases/show.php?id=<?= (int) $approval['complaint_id'] ?>&amp;approval_id=<?= (int) $approval['approval_id'] ?>#case-status">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
        </div>
    </div>

    <script>
        const auditFiltersToggle = document.getElementById('auditFiltersToggle');
        const auditFiltersPanel = document.getElementById('auditFiltersPanel');
        const auditFiltersCount = document.getElementById('auditFiltersCount');
        const auditFiltersForm = document.getElementById('auditFilters');
        const auditLogTableBody = document.getElementById('auditLogTableBody');
        const auditPagination = document.getElementById('auditPagination');
        const auditExportPdf = document.getElementById('auditExportPdf');
        const auditFilterError = document.getElementById('auditFilterError');

        // The filter form contains a control named "action", so HTMLFormElement.action
        // resolves to that <select> instead of the URL string. Always use this
        // canonical /audit-logs/ endpoint, never form.action.
        const auditEndpoint = new URL(
            auditFiltersForm.dataset.auditEndpoint || auditFiltersForm.getAttribute('action') || 'audit-logs/',
            document.baseURI
        ).href;

        let auditSearchTimer = null;
        let auditFilterTimer = null;
        let auditRequest = null;
        let auditRequestSequence = 0;

        const closeAuditFilters = () => {
            if (!auditFiltersPanel || !auditFiltersToggle) return;
            auditFiltersPanel.classList.remove('open');
            auditFiltersToggle.setAttribute('aria-expanded', 'false');
        };

        const updateAuditFilterButton = () => {
            if (!auditFiltersForm || !auditFiltersToggle) return;
            const activeCount = [...new FormData(auditFiltersForm).entries()]
                .filter(([name, value]) => name !== 'search' && name !== 'page' && String(value).trim() !== '')
                .length;
            auditFiltersForm.classList.toggle('is-active', activeCount > 0);
            if (auditFiltersCount) {
                auditFiltersCount.hidden = activeCount === 0;
                auditFiltersCount.textContent = activeCount;
            }
        };

        const auditParams = (page = 1) => {
            const params = new URLSearchParams(new FormData(auditFiltersForm));
            if (page > 1) params.set('page', String(page));
            else params.delete('page');
            return params;
        };

        const updateAuditExportLink = () => {
            const params = auditParams();
            params.set('export', 'pdf');
            auditExportPdf.href = `${auditEndpoint}?${params.toString()}`;
        };

        const appendAuditHighlight = (node, value) => {
            const text = String(value ?? '');
            const term = String(auditFiltersForm.elements.search.value || '').trim();
            if (!term) { node.append(document.createTextNode(text)); return; }
            const haystack = text.toLocaleLowerCase();
            const needle = term.toLocaleLowerCase();
            let cursor = 0;
            let index;
            while ((index = haystack.indexOf(needle, cursor)) !== -1) {
                node.append(document.createTextNode(text.slice(cursor, index)));
                const mark = document.createElement('mark');
                mark.className = 'audit-search-match';
                mark.textContent = text.slice(index, index + term.length);
                node.append(mark);
                cursor = index + term.length;
            }
            node.append(document.createTextNode(text.slice(cursor)));
        };

        const appendAuditCell = (row, value, className = '') => {
            const cell = document.createElement('td');
            if (className) cell.className = className;
            appendAuditHighlight(cell, value);
            row.appendChild(cell);
            return cell;
        };

        const auditDateParts = (value) => {
            const date = new Date(String(value || '').replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return [String(value || ''), ''];
            return [
                new Intl.DateTimeFormat('en-US', { month: 'short', day: '2-digit', year: 'numeric' }).format(date),
                new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit' }).format(date),
            ];
        };

        const auditInitials = (name) => {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
        };

        const renderAuditLogs = (logs) => {
            auditLogTableBody.replaceChildren();
            if (!logs.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.textContent = 'No audit logs found.';
                row.appendChild(cell);
                auditLogTableBody.appendChild(row);
                return;
            }

            logs.forEach((log) => {
                const row = document.createElement('tr');
                const time = document.createElement('td');
                time.className = 'audit-time';
                const [date, clock] = auditDateParts(log.created_at);
                const dateStrong = document.createElement('strong');
                const timeSpan = document.createElement('span');
                appendAuditHighlight(dateStrong, date);
                appendAuditHighlight(timeSpan, clock);
                time.append(dateStrong, timeSpan);
                row.appendChild(time);

                const userCell = document.createElement('td');
                const userWrap = document.createElement('div');
                userWrap.className = 'audit-user';
                const avatar = document.createElement('span');
                avatar.className = 'audit-avatar';
                avatar.setAttribute('aria-hidden', 'true');
                avatar.textContent = auditInitials(log.user_name);
                const userName = document.createElement('strong');
                appendAuditHighlight(userName, log.user_name);
                userWrap.append(avatar, userName);
                userCell.appendChild(userWrap);
                row.appendChild(userCell);

                const roleCell = document.createElement('td');
                const role = document.createElement('span');
                role.className = 'audit-role';
                appendAuditHighlight(role, String(log.user_role || '').replace(/[-_]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
                roleCell.appendChild(role);
                row.appendChild(roleCell);

                const actionCell = document.createElement('td');
                const action = document.createElement('span');
                action.className = 'audit-action';
                const actionIcon = document.createElement('i');
                actionIcon.className = 'bi bi-activity';
                action.appendChild(actionIcon);
                appendAuditHighlight(action, log.action);
                actionCell.appendChild(action);
                row.appendChild(actionCell);
                appendAuditCell(row, log.description, 'audit-description');

                const ipCell = document.createElement('td');
                const ip = document.createElement('code');
                ip.className = 'audit-ip';
                appendAuditHighlight(ip, log.ip_address);
                ipCell.appendChild(ip);
                row.appendChild(ipCell);

                const deviceCell = document.createElement('td');
                const device = document.createElement('span');
                device.className = 'audit-device';
                device.title = String(log.user_agent || '');
                appendAuditHighlight(device, log.user_agent);
                deviceCell.appendChild(device);
                row.appendChild(deviceCell);
                auditLogTableBody.appendChild(row);
            });
        };

        const renderAuditPagination = (pagination) => {
            auditPagination.replaceChildren();
            const add = (label, page, active = false) => {
                const link = document.createElement('a');
                link.className = `btn btn-page${active ? ' active' : ''}`;
                link.href = `${auditEndpoint}?${auditParams(page).toString()}`;
                link.dataset.page = String(page);
                link.textContent = label;
                if (active) link.setAttribute('aria-current', 'page');
                auditPagination.appendChild(link);
            };
            const current = Number(pagination.currentPage);
            const total = Number(pagination.totalPages);
            if (current > 1) { add('First', 1); add('Previous', current - 1); }
            for (let page = Math.max(1, current - 2); page <= Math.min(total, current + 2); page++) add(String(page), page, page === current);
            if (current < total) { add('Next', current + 1); add('Last', total); }
        };

        const updateAuditSummary = (pagination, shown) => {
            const currentPage = Number(pagination.currentPage) || 1;
            const totalPages = Number(pagination.totalPages) || 1;
            const total = Number(pagination.total) || 0;
            document.getElementById('auditMatchingEvents').textContent = total;
            const currentPageLabel = document.getElementById('auditCurrentPage');
            currentPageLabel.textContent = '';
            currentPageLabel.append(document.createTextNode(`${currentPage} `));
            const ofTotal = document.createElement('small');
            ofTotal.textContent = `of ${totalPages}`;
            currentPageLabel.appendChild(ofTotal);
            document.getElementById('auditEventsShown').textContent = shown;
            document.getElementById('auditResultCount').textContent = `${total} matching event${total === 1 ? '' : 's'}`;
            document.getElementById('auditPageIndicator').textContent = `Page ${currentPage} of ${totalPages}`;
        };

        const loadAuditLogs = async ({ page = 1, historyMode = 'replace' } = {}) => {
            if (window.SICMSValidation && !window.SICMSValidation.run(auditFiltersForm)) return;
            auditRequest?.abort();
            auditRequest = new AbortController();
            const sequence = ++auditRequestSequence;
            const params = auditParams(page);
            const requestParams = new URLSearchParams(params);
            requestParams.set('ajax', '1');
            auditFiltersForm.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(`${auditEndpoint}?${requestParams.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: auditRequest.signal,
                });
                if (response.redirected) {
                    throw new Error('Your session may have expired. Refresh the page and sign in again.');
                }
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.toLowerCase().includes('application/json')) {
                    throw new Error(response.ok
                        ? 'The audit log service returned an unexpected response.'
                        : `The audit log request failed (HTTP ${response.status}).`);
                }
                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (parseError) {
                    throw new Error('The audit log service returned an unreadable response.');
                }
                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) || 'Unable to load audit logs.');
                }
                if (sequence !== auditRequestSequence) return;
                const logs = Array.isArray(data.logs) ? data.logs : [];
                const pagination = data.pagination || { total: 0, currentPage: 1, totalPages: 1, perPage: 10 };
                renderAuditLogs(logs);
                renderAuditPagination(pagination);
                updateAuditSummary(pagination, logs.length);
                auditFilterError.textContent = data.filterError || '';
                auditFilterError.hidden = !data.filterError;
                const resolvedPage = Math.max(1, Number(pagination.currentPage) || 1);
                if (resolvedPage > 1) params.set('page', String(resolvedPage));
                else params.delete('page');
                const nextUrl = params.toString() ? `${auditEndpoint}?${params.toString()}` : auditEndpoint;
                if (historyMode === 'push') history.pushState(null, '', nextUrl);
                else if (historyMode === 'replace') history.replaceState(null, '', nextUrl);
                updateAuditFilterButton();
                updateAuditExportLink();
            } catch (error) {
                if (error.name !== 'AbortError' && sequence === auditRequestSequence) {
                    auditFilterError.textContent = error.message || 'Unable to load audit logs.';
                    auditFilterError.hidden = false;
                }
            } finally {
                if (sequence === auditRequestSequence) auditFiltersForm.removeAttribute('aria-busy');
            }
        };

        auditFiltersToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = auditFiltersPanel.classList.toggle('open');
            auditFiltersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', (event) => {
            if (!auditFiltersPanel?.classList.contains('open')) return;
            if (!auditFiltersPanel.contains(event.target)) closeAuditFilters();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAuditFilters();
        });
        auditFiltersForm.elements.search.addEventListener('input', () => {
            clearTimeout(auditSearchTimer);
            updateAuditExportLink();
            auditSearchTimer = setTimeout(() => loadAuditLogs({ historyMode: 'replace' }), 350);
        });
        ['account_id', 'role', 'action', 'date_from', 'date_to'].forEach((name) => {
            auditFiltersForm.elements[name]?.addEventListener('change', () => {
                clearTimeout(auditFilterTimer);
                updateAuditFilterButton();
                updateAuditExportLink();
                auditFilterTimer = setTimeout(() => loadAuditLogs({ historyMode: 'push' }), 120);
            });
        });
        auditFiltersForm.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(auditSearchTimer);
            clearTimeout(auditFilterTimer);
            closeAuditFilters();
            loadAuditLogs({ historyMode: 'push' });
        });
        document.getElementById('resetAuditFilters').addEventListener('click', () => {
            clearTimeout(auditSearchTimer);
            clearTimeout(auditFilterTimer);
            auditFiltersForm.reset();
            [...auditFiltersForm.elements].forEach((control) => { if (control.name) control.value = ''; });
            closeAuditFilters();
            loadAuditLogs({ historyMode: 'push' });
        });
        auditPagination.addEventListener('click', (event) => {
            const link = event.target.closest('[data-page], .btn-page');
            if (!link) return;
            event.preventDefault();
            const page = Number(link.dataset.page || new URL(link.href, location.href).searchParams.get('page') || 1);
            loadAuditLogs({ page, historyMode: 'push' });
        });
        window.addEventListener('popstate', () => {
            const params = new URLSearchParams(location.search);
            ['search', 'account_id', 'role', 'action', 'date_from', 'date_to'].forEach((name) => {
                if (auditFiltersForm.elements[name]) auditFiltersForm.elements[name].value = params.get(name) || '';
            });
            loadAuditLogs({ page: Number(params.get('page')) || 1, historyMode: 'none' });
        });
        window.addEventListener('pageshow', () => { updateAuditFilterButton(); updateAuditExportLink(); });
        updateAuditFilterButton();
        updateAuditExportLink();

        if (auditFiltersForm.elements.search.value.trim()) {
            auditLogTableBody.querySelectorAll('td').forEach((cell) => {
                if (cell.querySelector('.audit-avatar')) {
                    const name = cell.querySelector('strong');
                    if (!name) return;
                    const text = name.textContent;
                    name.replaceChildren();
                    appendAuditHighlight(name, text);
                    return;
                }
                const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
                const nodes = [];
                while (walker.nextNode()) if (walker.currentNode.nodeValue.trim()) nodes.push(walker.currentNode);
                nodes.forEach((textNode) => {
                    if (textNode.parentElement.closest('mark, .audit-avatar, i')) return;
                    const fragment = document.createDocumentFragment();
                    appendAuditHighlight(fragment, textNode.nodeValue);
                    textNode.replaceWith(fragment);
                });
            });
        }

        const auditExportOverlay = document.getElementById('auditExportOverlay');
        const auditExportForm = document.getElementById('auditExportForm');
        const auditExportBtn = document.getElementById('auditExportPdf');
        const auditExportCancel = document.getElementById('auditExportCancel');
        const auditExportSelectAll = document.getElementById('auditExportSelectAll');
        const auditExportReset = document.getElementById('auditExportReset');
        const auditExportFeedback = document.getElementById('auditExportFeedback');
        const auditExportFiltersSummary = document.getElementById('auditExportFiltersSummary');
        const auditExportColumnsGrid = document.getElementById('auditExportColumnsGrid');

        const openAuditExportModal = (event) => {
            event.preventDefault();
            const formData = new FormData(auditFiltersForm);
            document.getElementById('exportInputSearch').value = formData.get('search') || '';
            document.getElementById('exportInputAccountId').value = formData.get('account_id') || '';
            document.getElementById('exportInputRole').value = formData.get('role') || '';
            document.getElementById('exportInputAction').value = formData.get('action') || '';
            document.getElementById('exportInputDateFrom').value = formData.get('date_from') || '';
            document.getElementById('exportInputDateTo').value = formData.get('date_to') || '';

            const activeParts = [];
            if (formData.get('search')) activeParts.push(`Search: "${formData.get('search')}"`);
            if (formData.get('account_id')) activeParts.push(`User ID: ${formData.get('account_id')}`);
            if (formData.get('role')) activeParts.push(`Role: ${formData.get('role')}`);
            if (formData.get('action')) activeParts.push(`Action: ${formData.get('action')}`);
            if (formData.get('date_from')) activeParts.push(`From: ${formData.get('date_from')}`);
            if (formData.get('date_to')) activeParts.push(`To: ${formData.get('date_to')}`);

            auditExportFiltersSummary.textContent = activeParts.length ? activeParts.join(' · ') : 'None (All records)';
            auditExportColumnsGrid.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
            auditExportFeedback.textContent = '';
            auditExportOverlay.hidden = false;
        };

        const closeAuditExportModal = () => {
            auditExportOverlay.hidden = true;
            auditExportFeedback.textContent = '';
        };

        auditExportBtn?.addEventListener('click', openAuditExportModal);
        auditExportCancel?.addEventListener('click', closeAuditExportModal);
        auditExportOverlay?.addEventListener('mousedown', (event) => {
            if (event.target === auditExportOverlay) closeAuditExportModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !auditExportOverlay.hidden) closeAuditExportModal();
        });

        auditExportSelectAll?.addEventListener('click', () => {
            auditExportColumnsGrid.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
            auditExportFeedback.textContent = '';
        });

        auditExportReset?.addEventListener('click', () => {
            auditExportColumnsGrid.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
            auditExportFeedback.textContent = '';
        });

        auditExportForm?.addEventListener('submit', (event) => {
            const checked = auditExportColumnsGrid.querySelectorAll('input[type="checkbox"]:checked');
            if (checked.length === 0) {
                event.preventDefault();
                auditExportFeedback.textContent = 'Select at least one column to include in the report.';
                window.DARISAlert?.toast?.('warning', 'Columns Required', 'Please select at least one column to include in the PDF report.');
                return;
            }
            auditExportFeedback.textContent = '';
            window.DARISAlert?.toast?.('info', 'Generating Report...', 'DARIS is preparing your PDF report.', { timer: 3000 });
            setTimeout(closeAuditExportModal, 300);
        });
    </script>
</body>

</html>
