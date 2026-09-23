<?php
require_once __DIR__ . '/../../controllers/RespondentController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new RespondentController();
$viewData = $controller->cases();
$user = $viewData['user'];
$cases = $viewData['cases'];

function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function status_class($status) { return strtolower(str_replace(' ', '-', $status)); }

$pageTitle = 'Complaint Cases';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Cases | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; background: var(--bg-page, #f5f7f4); color: var(--text-primary, #172017); display: block; justify-content: flex-start; padding: 0; }
        .track-wrap { max-width: 100%; margin: 0 auto; padding: 24px; }
        .track-intro { align-items: center; display: flex; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .track-intro h1 { color: var(--text-primary, #172017); font-size: 24px; margin: 0 0 4px; }
        .track-intro p { color: var(--text-muted, #637060); font-size: 13px; margin: 0; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
        .summary-card { align-items: center; background: var(--surface-primary, #fff); border: 1px solid var(--border-primary, #dce5da); border-radius: 8px; display: flex; gap: 12px; min-height: 88px; padding: 16px; box-shadow: var(--sicms-shadow, 0 4px 14px rgba(18,60,27,.06)); }
        .summary-icon { align-items: center; background: var(--surface-accent, #eaf5e8); border-radius: 8px; color: var(--accent, #167322); display: flex; font-size: 20px; height: 42px; justify-content: center; width: 42px; }
        .summary-label { color: var(--text-muted, #637060); font-size: 12px; font-weight: 700; }
        .summary-value { color: var(--text-primary, #172017); font-size: 24px; font-weight: 800; line-height: 1.1; margin-top: 3px; }
        .table-panel { background: var(--surface-primary, #fff); border: 1px solid var(--border-primary, #dce5da); border-radius: 8px; overflow: hidden; box-shadow: var(--sicms-shadow, 0 4px 14px rgba(18,60,27,.06)); }
        .table-scroll { overflow-x: auto; }
        .cases-table { border-collapse: collapse; min-width: 860px; width: 100%; }
        .cases-table th { background: var(--bg-sidebar, #123c1b); color: #fff; font-size: 12px; padding: 13px 14px; text-align: left; }
        .cases-table td { border-bottom: 1px solid var(--divider, #e6ece4); color: var(--text-primary, #263225); font-size: 13px; padding: 14px; vertical-align: middle; }
        .cases-table tbody tr:hover { background: var(--hover-bg, #f8fbf7); }
        .case-link { color: var(--accent, #146d20); font-weight: 800; text-decoration: none; }
        .status-pill { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 800; padding: 6px 9px; white-space: nowrap; }
        .status-under-investigation { background: var(--status-info-bg, #e7f0ff); color: var(--status-info-text, #275ca8); }
        .status-returned-for-revision { background: var(--status-warning-bg, #fff5d8); color: var(--status-warning-text, #825e00); }
        .status-resolved { background: var(--status-success-bg, #e5f6e3); color: var(--status-success-text, #157000); }
        .status-reformation-in-progress { background: var(--status-warning-bg, #fff4d6); color: var(--status-warning-text, #8a5a00); }
        .status-reformation-completed { background: var(--status-success-bg, #e1f6ef); color: var(--status-success-text, #087f5b); }
        .status-escalated { background: var(--status-warning-bg, #fdeee3); color: var(--status-warning-text, #c2410c); }
        .status-rejected { background: var(--status-danger-bg, #fff0ef); color: var(--status-danger-text, #a92c23); }
        .status-archived { background: var(--status-muted-bg, #edf0ed); color: var(--status-muted-text, #59635a); }
        .status-counter-statement-required { background: var(--status-warning-bg, #fff4d6); color: var(--status-warning-text, #8a5a00); }
        .cs-required-banner { align-items: flex-start; background: var(--surface-soft, #fffdf5); border: 1px solid var(--status-warning-border, #ead9a5); border-left: 4px solid var(--status-warning-border, #b57600); border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px 16px; justify-content: space-between; margin-bottom: 16px; padding: 14px 16px; }
        .cs-required-banner p { color: var(--text-muted, #6a614c); font-size: 13px; margin: 4px 0 0; }
        .statement-pill { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 800; padding: 6px 9px; white-space: nowrap; }
        .statement-submitted { background: var(--status-success-bg, #e5f6e3); color: var(--status-success-text, #157000); }
        .statement-draft { background: var(--status-warning-bg, #fff5d8); color: var(--status-warning-text, #825e00); }
        .row-actions { display: flex; flex-wrap: wrap; gap: 7px; }
        .row-actions .btn { padding: 7px 10px; font-size: 12px; }
        .empty-state { padding: 52px 20px; text-align: center; }
        .empty-state i { color: var(--text-muted, #8eaa8a); font-size: 38px; }
        .empty-state h2 { color: var(--text-primary, #243123); font-size: 18px; margin: 10px 0 5px; }
        .empty-state p { color: var(--text-muted, #657164); font-size: 13px; margin: 0 0 16px; }
        @media (max-width: 900px) { .summary-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .track-wrap { padding: 16px; } .track-intro { align-items: flex-start; flex-direction: column; } .summary-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="app-content">
        <?php require __DIR__ . '/../layout/topbar.php'; ?>
        <main class="track-wrap">
            <?php
            $total = count($cases);
            $releasedCases = array_values(array_filter($cases, fn($c) => !empty($c['is_released'])));
            $releasedTotal = count($releasedCases);
            $underInvestigation = count(array_filter($releasedCases, fn($c) => $c['status'] === 'Under Investigation' || $c['status'] === 'Returned for Revision'));
            $statementsPending = count(array_filter($releasedCases, fn($c) => $c['statement_status'] !== 'Submitted'));
            $finalStatuses = ['Resolved', 'Reformation Completed', 'Archived', 'Rejected', 'Escalated'];
            $csRequiredCases = array_values(array_filter($releasedCases, fn($c) => !in_array($c['status'], $finalStatuses, true) && ($c['statement_status'] ?? null) !== 'Submitted'));
            ?>
            <header class="track-intro">
                <div><h1>Complaint Cases</h1><p>View the cases where you are named as a respondent, submit your counter-statement, and check your hearings.</p></div>
            </header>

            <section class="summary-grid" aria-label="Respondent case summary">
                <?php foreach ([['total','Total Assigned Cases','bi-folder2-open'],['active','Active Cases','bi-search'],['pending','Pending Response','bi-pencil-square'],['submitted','Submitted Statements','bi-check2-circle']] as [$key,$label,$icon]): ?>
                    <article class="summary-card"><span class="summary-icon"><i class="bi <?= h($icon) ?>"></i></span><div><div class="summary-label"><?= h($label) ?></div><div class="summary-value"><?= $key === 'total' ? $total : ($key === 'active' ? $underInvestigation : ($key === 'pending' ? $statementsPending : ($releasedTotal - $statementsPending))) ?></div></div></article>
                <?php endforeach; ?>
            </section>

            <?php if (!empty($csRequiredCases)): ?>
                <?php foreach ($csRequiredCases as $csCase): ?>
                <section class="cs-required-banner" aria-label="Counter-statement required">
                    <div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span class="status-pill status-counter-statement-required"><i class="bi bi-pencil-square"></i> Counter-Statement Required</span>
                            <strong style="font-size:14px;color:#172017">Case <?= h($csCase['case_number']) ?></strong>
                        </div>
                        <p>You are named as a respondent on this case. Please review the case details and file your counter-statement at your earliest convenience.</p>
                    </div>
                    <a class="btn btn-primary" href="case_show.php?id=<?= (int) $csCase['complaint_id'] ?>"><i class="bi bi-eye"></i> View Case</a>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>

            <section class="table-panel" aria-live="polite">
                <div class="table-scroll"><table class="cases-table">
                    <thead><tr><th>Case Number</th><th>Classification</th><th>Date Filed</th><th>Case Status</th><th>Counter-Statement</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($cases)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state"><i class="bi bi-folder2-open"></i><h2>No cases assigned to you yet.</h2><p>When the SDRU records you as a respondent in a case, it will appear here.</p></div>
                        </td></tr>
                    <?php else: foreach ($cases as $case): ?>
                        <tr>
                            <td><?php if (!empty($case['is_released'])): ?><a class="case-link" href="case_show.php?id=<?= (int) $case['complaint_id'] ?>"><?= h($case['case_number']) ?></a><?php else: ?><strong><?= h($case['case_number']) ?></strong><?php endif; ?></td>
                            <td><?= !empty($case['is_released']) ? h($case['case_classification']) : '<span class="muted">Awaiting release</span>' ?></td>
                            <td><?= h(date('M d, Y', strtotime($case['submitted_at']))) ?></td>
                            <td><span class="status-pill status-<?= h(status_class($case['status'])) ?>"><?= h($case['status']) ?></span></td>
                            <td>
                                <?php if (empty($case['is_released'])): ?>
                                    <span class="muted">Available after SDRU release</span>
                                <?php elseif ($case['statement_status']): ?>
                                    <?php if ($case['statement_status'] === 'Submitted'): ?>
                                        <span class="statement-pill statement-submitted"><i class="bi bi-check2-circle"></i> Submitted <?= h(date('M d, Y', strtotime($case['statement_submitted_at'] ?? $case['submitted_at']))) ?></span>
                                    <?php else: ?>
                                        <span class="statement-pill statement-draft"><i class="bi bi-pencil-square"></i> Draft</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="muted">No statement yet</span>
                                <?php endif; ?>
                            </td>
                            <td><div class="row-actions"><?php if (!empty($case['is_released'])): ?><a class="btn btn-primary" href="case_show.php?id=<?= (int) $case['complaint_id'] ?>"><i class="bi bi-eye"></i> View Details</a><?php else: ?><span class="muted">Awaiting SDRU release</span><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table></div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
