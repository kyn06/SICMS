<?php
require_once __DIR__ . '/../../controllers/AuditLogController.php';

$controller = new AuditLogController();
$viewData = $controller->index();

$user = $viewData['user'];
$filters = $viewData['filters'];
$logs = $viewData['logs'];
$options = $viewData['options'];
$pagination = $viewData['pagination'];

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
    $params = array_merge($_GET, $extra);

    return '?' . http_build_query($params);
}

function user_initials($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | SICMS</title>
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
        .wrap { max-width: 1280px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #dce5da; border-radius: 8px; margin-bottom: 18px; padding: 16px; }
        .filter-grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .field { display: flex; flex-direction: column; gap: 6px; }
        label { color: #536052; font-size: 13px; }
        input, select { border: 1px solid #b9c7b7; border-radius: 8px; font: inherit; padding: 10px 12px; }
        .actions, .pagination { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; margin-top: 14px; }
        .btn { border: 0; border-radius: 8px; cursor: pointer; font: inherit; padding: 10px 14px; text-decoration: none; }
        .btn-primary { background: #1A9D00; color: #fff; }
        .btn-secondary { background: #eef5ed; color: #123c1b; }
        .btn-page { background: #fff; border: 1px solid #dce5da; color: #123c1b; }
        .btn-page.active { background: #123c1b; color: #fff; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; min-width: 1120px; width: 100%; }
        th, td { border-bottom: 1px solid #edf4eb; padding: 11px 10px; text-align: left; vertical-align: top; }
        th { color: #536052; font-size: 12px; text-transform: uppercase; }
        td { color: #172017; font-size: 13px; line-height: 1.45; }
        .muted { color: #6b766a; font-size: 12px; }
        @media (max-width: 900px) {
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

        #auditFilters.is-active .filters-toggle-btn,
        #auditFilters.is-active .filters-toggle-btn:hover {
            background: #1A9D00;
            border-color: #1A9D00;
            color: #fff;
        }

        #auditFilters.is-active .filters-count {
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
            width: min(760px, calc(100vw - 56px));
            z-index: 500;
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
            border-top: 1px solid #e4ece2;
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
    </style>
    <link rel="stylesheet" href="../layout/system.css">
    <link rel="stylesheet" href="../layout/audit-logs.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Audit Logs'; require __DIR__ . '/../layout/topbar.php'; ?>

    <main class="wrap audit-page">
        <section class="audit-summary" aria-label="Audit log summary">
            <article class="audit-summary-card">
                <div><span>Matching Events</span><strong><?= (int) $pagination['total'] ?></strong></div>
                <i class="bi bi-shield-check" aria-hidden="true"></i>
            </article>
            <article class="audit-summary-card">
                <div><span>Current Page</span><strong><?= (int) $pagination['currentPage'] ?> <small>of <?= (int) $pagination['totalPages'] ?></small></strong></div>
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            </article>
            <article class="audit-summary-card">
                <div><span>Events Shown</span><strong><?= count($logs) ?></strong></div>
                <i class="bi bi-list-check" aria-hidden="true"></i>
            </article>
        </section>

        <div class="audit-toolbar">
            <form id="auditFilters" class="filters-host" method="GET" action="index.php" data-no-ajax="true">
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
                <div class="field search-field">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="search" value="<?= h($filters['search']) ?>" placeholder="Action, user, description, IP">
                </div>
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
                        <a class="btn btn-secondary" href="index.php"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    </div>
                </div>
            </form>
            <div class="export-group">
                <a class="btn btn-primary" href="<?= h(with_query(['export' => 'pdf'])) ?>"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
            </div>
        </div>

        <section class="panel audit-table-panel">
            <div class="audit-table-heading">
                <div>
                    <h2>Activity History</h2>
                    <p><?= (int) $pagination['total'] ?> matching event<?= (int) $pagination['total'] === 1 ? '' : 's' ?></p>
                </div>
                <span class="audit-page-indicator">Page <?= (int) $pagination['currentPage'] ?> of <?= (int) $pagination['totalPages'] ?></span>
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
                    <tbody>
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

            <nav class="pagination" aria-label="Audit log pagination">
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a class="btn btn-page" href="<?= h(page_url(1)) ?>">First</a>
                <?php endif; ?>
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a class="btn btn-page" href="<?= h(page_url($pagination['currentPage'] - 1)) ?>">Previous</a>
                <?php endif; ?>

                <?php
                    $start = max(1, $pagination['currentPage'] - 2);
                    $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                ?>
                <?php for ($page = $start; $page <= $end; $page++): ?>
                    <a class="btn btn-page <?= $page === $pagination['currentPage'] ? 'active' : '' ?>" href="<?= h(page_url($page)) ?>"><?= $page ?></a>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a class="btn btn-page" href="<?= h(page_url($pagination['currentPage'] + 1)) ?>">Next</a>
                <?php endif; ?>
                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a class="btn btn-page" href="<?= h(page_url($pagination['totalPages'])) ?>">Last</a>
                <?php endif; ?>
            </nav>
        </section>
    </main>
        </div>
    </div>

    <script>
        const auditFiltersToggle = document.getElementById('auditFiltersToggle');
        const auditFiltersPanel = document.getElementById('auditFiltersPanel');
        const auditFiltersCount = document.getElementById('auditFiltersCount');
        const auditFiltersForm = document.getElementById('auditFilters');

        const closeAuditFilters = () => {
            if (!auditFiltersPanel || !auditFiltersToggle) return;
            auditFiltersPanel.classList.remove('open');
            auditFiltersToggle.setAttribute('aria-expanded', 'false');
        };

        const updateAuditFilterButton = () => {
            if (!auditFiltersForm || !auditFiltersToggle) return;
            const activeCount = [...new FormData(auditFiltersForm).entries()].filter(([, value]) => String(value).trim() !== '').length;
            auditFiltersForm.classList.toggle('is-active', activeCount > 0);
            if (auditFiltersCount) {
                auditFiltersCount.hidden = activeCount === 0;
                auditFiltersCount.textContent = activeCount;
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
        window.addEventListener('pageshow', () => updateAuditFilterButton());
        updateAuditFilterButton();
    </script>
</body>

</html>
