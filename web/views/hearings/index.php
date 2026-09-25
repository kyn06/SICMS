<?php
require_once __DIR__ . '/../../controllers/HearingController.php';

$controller = new HearingController();
if (($_GET['ajax'] ?? '') === '1') $controller->search();
$viewData = $controller->index();

$user = $viewData['user'];
$hearings = $viewData['hearings'];
$calendar = $viewData['calendar'];
$message = $viewData['message'];
$errors = $viewData['errors'];
$scheduledCount = count(array_filter($hearings, fn($hearing) => $hearing['status'] === 'Scheduled'));
$todayCount = count(array_filter($hearings, fn($hearing) => substr($hearing['hearing_datetime'], 0, 10) === date('Y-m-d')));
$completedCount = count(array_filter($hearings, fn($hearing) => $hearing['status'] === 'Completed'));
$cancelledCount = count(array_filter($hearings, fn($hearing) => $hearing['status'] === 'Cancelled'));

$calMonth = (isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])) ? $_GET['month'] : date('Y-m');
$calYear = (int) substr($calMonth, 0, 4);
$calMonthNum = (int) substr($calMonth, 5, 2);
$calFirstDay = new DateTime(sprintf('%04d-%02d-01', $calYear, $calMonthNum));
$calDaysInMonth = (int) $calFirstDay->format('t');
$calStartWeekday = (int) $calFirstDay->format('N');
$calFilterParams = function ($month) {
    $query = $_GET;
    $query['month'] = $month;
    return '?' . http_build_query($query);
};
$hearingsByDay = [];
foreach ($hearings as $hearing) {
    if (substr($hearing['hearing_datetime'], 0, 7) === $calMonth) {
        $hearingsByDay[(int) substr($hearing['hearing_datetime'], 8, 2)][] = $hearing;
    }
}

$controller->clearFlash();

function h($value) {
    return htmlspecialchars((string) $value);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hearing Management | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/hearings.css">
    <style>
        body { align-items: stretch; justify-content: flex-start; background: var(--bg-page, #f5f7f4); color: var(--text-primary, #172017); padding: 0; }
        .page { min-height: 100vh; width: 100%; }
        .header { align-items: center; background: var(--bg-sidebar, #123c1b); color: #fff; display: flex; justify-content: space-between; gap: 16px; padding: 22px 30px; }
        .header h1 { font-size: 24px; margin-bottom: 4px; }
        .header p { color: rgba(255,255,255,0.8); font-size: 13px; }
        .actions { display: flex; gap: 10px; }
        .actions a, .btn { border: 0; border-radius: 8px; cursor: pointer; font-family: inherit; font-size: 14px; text-decoration: none; }
        .hearing-toolbar .actions a { background: linear-gradient(135deg, var(--accent, #1A9D00) 0%, #128000 100%) !important; color: #fff !important; padding: 10px 18px; font-weight: 600; box-shadow: 0 2px 8px rgba(26,157,0,0.18); transition: transform .12s, box-shadow .12s; }
        .hearing-toolbar .actions a:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(26,157,0,0.28); }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 24px; }
        .panel { background: var(--surface-primary, #fff); border: 1px solid var(--border-primary, #dce5da); border-radius: 8px; padding: 18px; }
        .alert { border-radius: 8px; font-size: 14px; margin-bottom: 18px; padding: 12px 14px; }
        .alert-success { background: rgba(26, 157, 0, 0.08); border: 1px solid rgba(26, 157, 0, 0.35); color: var(--status-success-text, #137500); }
        .alert-error { background: var(--status-danger-bg, #fff5f5); border: 1px solid rgba(180, 35, 24, 0.4); color: var(--status-danger-text, #b42318); }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid var(--divider, #e4ece2); font-size: 14px; padding: 12px 10px; text-align: left; vertical-align: top; }
        th { color: var(--text-muted, #536052); font-weight: 600; }
        .status { background: var(--surface-accent, #edf4eb); border-radius: 999px; color: var(--text-primary, #123c1b); display: inline-block; font-size: 12px; padding: 5px 10px; }
        .btn-edit { background: linear-gradient(135deg, var(--accent, #1A9D00) 0%, #128000 100%) !important; color: #fff !important; display: inline-block; padding: 9px 12px; }
        .btn-cancel { background: var(--status-danger-bg, #b42318); color: #fff; padding: 9px 12px; }
        .btn-complete { background: var(--accent, #1A9D00); color: #fff; padding: 9px 12px; }
        .row-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .empty { color: var(--text-muted, #536052); padding: 18px 0; text-align: center; }
        @media (max-width: 900px) { .header { align-items: flex-start; flex-direction: column; } table { display: block; overflow-x: auto; white-space: nowrap; } }
    </style>
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Hearing Management'; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="wrap hearings-page">
            <div class="hearing-toolbar">
                <div><h2>Hearing Schedule</h2><p><?= count($hearings) ?> hearing record<?= count($hearings) === 1 ? '' : 's' ?></p></div>
                <div class="actions">
                    <a class="btn btn-primary" href="create.php"><i class="bi bi-calendar-plus"></i> Schedule Hearing</a>
                    <a class="btn btn-primary" href="../cases/index.php"><i class="bi bi-folder2-open"></i> View Cases</a>
                </div>
            </div>
            <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="hearing-summary" aria-label="Hearing summary">
                <?php foreach ([
                    ['label' => 'Scheduled', 'value' => $scheduledCount, 'icon' => 'bi-calendar-event'],
                    ['label' => 'Hearings Today', 'value' => $todayCount, 'icon' => 'bi-calendar-day'],
                    ['label' => 'Completed', 'value' => $completedCount, 'icon' => 'bi-check2-circle'],
                    ['label' => 'Cancelled', 'value' => $cancelledCount, 'icon' => 'bi-calendar-x'],
                ] as $item): ?>
                    <article class="hearing-summary-card">
                        <div><span><?= h($item['label']) ?></span><strong data-hearing-summary="<?= strtolower(str_replace(['hearings ', ' '], ['', '-'], $item['label'])) ?>"><?= (int) $item['value'] ?></strong></div>
                        <i class="bi <?= h($item['icon']) ?>" aria-hidden="true"></i>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="hearing-calendar-layout">
                <section class="panel hearing-calendar-panel">
                    <div class="hearing-panel-heading hearing-calendar-heading">
                        <div><h2>Calendar</h2><p><?= h($calFirstDay->format('F Y')) ?></p></div>
                        <div class="cal-nav">
                            <a class="btn btn-secondary" href="index.php<?= h($calFilterParams(date('Y-m', strtotime($calMonth . '-01 -1 month')))) ?>" title="Previous month"><i class="bi bi-chevron-left"></i></a>
                            <a class="btn btn-secondary" href="index.php<?= h($calFilterParams(date('Y-m'))) ?>">Today</a>
                            <a class="btn btn-secondary" href="index.php<?= h($calFilterParams(date('Y-m', strtotime($calMonth . '-01 +1 month')))) ?>" title="Next month"><i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="cal-grid">
                        <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $calDow): ?>
                            <div class="cal-dow"><?= $calDow ?></div>
                        <?php endforeach; ?>
                        <?php for ($calBlank = 1; $calBlank < $calStartWeekday; $calBlank++): ?>
                            <div class="cal-cell is-empty"></div>
                        <?php endfor; ?>
                        <?php for ($calDay = 1; $calDay <= $calDaysInMonth; $calDay++):
                            $calIsToday = $calMonth === date('Y-m') && $calDay === (int) date('j');
                            $calItems = $hearingsByDay[$calDay] ?? []; ?>
                            <div class="cal-cell<?= $calIsToday ? ' is-today' : '' ?>">
                                <div class="cal-daynum"><?= $calDay ?></div>
                                <?php foreach (array_slice($calItems, 0, 2) as $calHearing): ?>
                                    <a class="cal-chip<?= $calHearing['status'] === 'Cancelled' ? ' is-cancelled' : '' ?>" href="edit.php?id=<?= (int) $calHearing['hearing_id'] ?>" title="<?= h(date('M d, h:i A', strtotime($calHearing['hearing_datetime'])) . ' - ' . $calHearing['case_number']) ?>">
                                        <?php if (!empty($calHearing['google_meet_link'])): ?><i class="bi bi-camera-video"></i><?php endif; ?>
                                        <?= h(date('g:i', strtotime($calHearing['hearing_datetime']))) ?> <?= h($calHearing['case_number']) ?>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($calItems) > 2): ?>
                                    <span class="cal-more">+<?= count($calItems) - 2 ?> more</span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </section>
                <aside class="panel hearing-sync-panel">
                    <div class="hearing-panel-heading">
                        <div><h2>Google Calendar</h2><p><?= $calendar['connected'] ? h($calendar['email'] ?? 'Connected') : 'Not connected' ?></p></div>
                    </div>
                    <?php if (!$calendar['connected']): ?>
                        <div class="cal-sync-empty"><i class="bi bi-google"></i><span>Connect the office calendar in <a href="../settings/index.php">Settings</a> to sync hearings automatically.</span></div>
                    <?php elseif (empty($calendar['upcoming'])): ?>
                        <div class="cal-sync-empty"><i class="bi bi-calendar2-check"></i><span>No upcoming synced events.</span></div>
                    <?php else: ?>
                        <ul class="cal-sync-list">
                            <?php foreach ($calendar['upcoming'] as $calSynced): ?>
                                <li>
                                    <a href="<?= h($calSynced['link']) ?>" target="_blank" rel="noopener">
                                        <strong><?= h($calSynced['summary']) ?></strong>
                                        <span><?= $calSynced['start'] ? h(date('M d, h:i A', strtotime($calSynced['start']))) : 'All day' ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </aside>
            </section>

            <section class="panel hearing-table-panel">
                <div class="hearing-panel-heading">
                    <div><h2>All Hearings</h2><p>Case hearing activity and status</p></div>
                </div>
                <form id="hearingFilters" method="GET" action="index.php" style="display:grid;grid-template-columns:minmax(220px,2fr) repeat(2,minmax(150px,1fr)) auto;gap:12px;margin-bottom:16px;align-items:end">
                    <div class="field"><label for="hearing_search">Search</label><input id="hearing_search" name="search" placeholder="Case number, student, or venue"></div>
                    <div class="field"><label for="hearing_status">Status</label><select id="hearing_status" name="status"><option value="">All Statuses</option><option>Scheduled</option><option>Completed</option><option>Cancelled</option></select></div>
                    <div class="field"><label for="hearing_year">Year</label><select id="hearing_year" name="year"><option value="">All Years</option><?php for ($year=(int)date('Y');$year>=2020;$year--): ?><option><?= $year ?></option><?php endfor; ?></select></div>
                    <button class="btn btn-secondary" id="resetHearingFilters" type="button">Reset</button>
                </form>
                <?php if (empty($hearings)): ?>
                    <div class="empty hearing-empty"><i class="bi bi-calendar2-x"></i><strong>No hearings found</strong><span>Scheduled hearings will appear here.</span></div>
                <?php else: ?>
                    <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Case Number</th>
                                <th>Complainant</th>
                                <th>Date and Time</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hearingTableBody">
                            <?php foreach ($hearings as $hearing): ?>
                                <tr>
                                    <td><a class="hearing-case-link" href="../cases/show.php?id=<?= (int) $hearing['complaint_id'] ?>"><?= h($hearing['case_number']) ?></a></td>
                                    <td><div class="hearing-student"><span class="hearing-avatar" aria-hidden="true"><?= h(strtoupper(substr($hearing['complainant_name'], 0, 1))) ?></span><strong><?= h($hearing['complainant_name']) ?></strong></div></td>
                                    <td class="hearing-date"><strong><?= h(date('M d, Y', strtotime($hearing['hearing_datetime']))) ?></strong><span><?= h(date('h:i A', strtotime($hearing['hearing_datetime']))) ?></span></td>
                                    <td>
                                        <div class="hearing-venue"><i class="bi bi-geo-alt"></i><span><?= h($hearing['venue']) ?></span></div>
                                        <?php if (!empty($hearing['google_meet_link'])): ?>
                                            <a class="meet-link" href="<?= h($hearing['google_meet_link']) ?>" target="_blank" rel="noopener"><i class="bi bi-camera-video"></i> Google Meet</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status"><?= h($hearing['status']) ?></span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="btn btn-edit" href="edit.php?id=<?= (int) $hearing['hearing_id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                                            <?php if ($hearing['status'] === 'Scheduled'): ?>
                                                <form method="POST" action="index.php" data-confirm-title="Cancel hearing?" data-confirm="This scheduled hearing will be cancelled. Participants may need to be informed of the cancellation." data-confirm-button="Yes, cancel hearing" data-confirm-icon="warning">
                                                    <?= Security::csrfField() ?>
                                                    <input type="hidden" name="hearing_id" value="<?= (int) $hearing['hearing_id'] ?>">
                                                    <button class="btn btn-cancel" type="submit" name="hearing_action" value="cancel" data-sicms-processing-label="Cancelling Hearing..." data-sicms-processing-modal="true"><i class="bi bi-x-circle"></i> Cancel</button>
                                                </form>
                                                <form method="POST" action="index.php">
                                                    <?= Security::csrfField() ?>
                                                    <input type="hidden" name="hearing_id" value="<?= (int) $hearing['hearing_id'] ?>">
                                                    <button class="btn btn-complete" type="submit" data-swal-hearing-complete name="hearing_action" value="complete" data-sicms-processing-label="Completing Hearing..." data-sicms-processing-modal="true"><i class="bi bi-check2-circle"></i> Complete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        </div>
    </div>
    <script>
    (()=>{const form=document.getElementById('hearingFilters'),body=document.getElementById('hearingTableBody');if(!form||!body)return;const csrf='<?= h(Security::csrfToken()) ?>',esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));let timer,request;async function load(){if(request)request.abort();request=new AbortController();const p=new URLSearchParams(new FormData(form));p.set('ajax','1');const r=await fetch(`index.php?${p}`,{headers:{'X-Requested-With':'XMLHttpRequest'},signal:request.signal});const d=await r.json();if(!d.success)throw Error(d.message);body.innerHTML=d.hearings.length?d.hearings.map(h=>{const actions=h.status==='Scheduled'?`<form method="POST" action="index.php" data-confirm-title="Cancel Hearing?" data-confirm="This scheduled hearing will be cancelled." data-confirm-button="Yes, Cancel Hearing" data-confirm-icon="warning"><input type="hidden" name="csrf_token" value="${csrf}"><input type="hidden" name="hearing_id" value="${+h.hearing_id}"><button class="btn btn-cancel" data-sicms-processing-label="Cancelling Hearing..." data-sicms-processing-modal="true" name="hearing_action" value="cancel">Cancel</button></form><form method="POST" action="index.php"><input type="hidden" name="csrf_token" value="${csrf}"><input type="hidden" name="hearing_id" value="${+h.hearing_id}"><button class="btn btn-complete" data-swal-hearing-complete data-sicms-processing-label="Completing Hearing..." data-sicms-processing-modal="true" name="hearing_action" value="complete">Complete</button></form>`:'';return `<tr><td><a class="hearing-case-link" href="../cases/show.php?id=${+h.complaint_id}">${esc(h.case_number)}</a></td><td><strong>${esc(h.complainant_name)}</strong></td><td>${esc(new Date(h.hearing_datetime.replace(' ','T')).toLocaleString())}</td><td>${esc(h.venue)}</td><td><span class="status">${esc(h.status)}</span></td><td><div class="row-actions"><a class="btn btn-edit" href="../hearings/edit.php?id=${+h.hearing_id}"><i class="bi bi-pencil"></i> Edit</a>${actions}</div></td></tr>`}).join(''):'<tr><td colspan="6" class="empty">No hearings found.</td></tr>';Object.entries(d.summary).forEach(([k,v])=>{const n=document.querySelector(`[data-hearing-summary="${k}"]`);if(n)n.textContent=v});p.delete('ajax');history.replaceState(null,'',p.toString()?`index.php?${p}`:'index.php')}form.addEventListener('submit',e=>{e.preventDefault();load().catch(()=>{})});form.querySelectorAll('select').forEach(x=>x.addEventListener('change',load));form.elements.search.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(load,400)});document.getElementById('resetHearingFilters').addEventListener('click',()=>{form.reset();load()})})();
    </script>
    <script>
        <?php if ($message): ?>
        window.addEventListener('load', () => {
            const message = <?= json_encode((string) $message) ?>;
            const title = message.toLowerCase().includes('cancel') ? 'Hearing Cancelled'
                : message.toLowerCase().includes('complet') ? 'Hearing Completed'
                : message.toLowerCase().includes('approval') ? 'Hearing Request Submitted'
                : 'Hearing Scheduled';
            (window.DARISAlert?.toast('success', title, message) || Swal.fire({ toast: true, position: 'top', icon: 'success', title, text: message, timer: 4800, showConfirmButton: false, showCloseButton: true, backdrop: false }));
        }, { once: true });
        <?php elseif (!empty($errors)): ?>
        window.addEventListener('load', () => {
            const text = <?= json_encode(implode(' ', array_map('strval', $errors))) ?>;
            (window.DARISAlert?.toast('error', 'Unable to Update Hearing', text, { timer: 7000 }) || Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Unable to Update Hearing', text, timer: 7000, showConfirmButton: false, showCloseButton: true, backdrop: false }));
        }, { once: true });
        <?php endif; ?>

        document.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-swal-hearing-complete]');
            if (!button) return;
            event.preventDefault();
            const options = {
                icon: 'question',
                title: 'Record this hearing as completed?',
                text: 'The scheduled hearing will be marked as completed in the case record.',
                showCancelButton: true,
                confirmButtonText: 'Complete Hearing',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                allowOutsideClick: false
            };
            (window.DARISAlert?.fire(options) || Swal.fire(options)).then((result) => {
                if (result.isConfirmed) button.form.requestSubmit(button);
            });
        });
    </script>
</body>

</html>
