<?php
require_once __DIR__ . '/../../controllers/NotificationController.php';
$controller = new NotificationController();
if (($_GET['ajax'] ?? '') === '1') $controller->search();
$viewData = $controller->index();
$user = $viewData['user'];
$notifications = $viewData['notifications'];
$unreadCount = $viewData['unreadCount'];

function h($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function notification_icon($type) {
    $type = strtolower((string) $type);
    if (str_contains($type, 'message')) return 'bi-chat-dots';
    if (str_contains($type, 'hearing')) return 'bi-calendar-event';
    if (str_contains($type, 'resolved') || str_contains($type, 'verified')) return 'bi-check2-circle';
    if (str_contains($type, 'revision') || str_contains($type, 'returned')) return 'bi-pencil-square';
    if (str_contains($type, 'assigned')) return 'bi-person-check';
    if (str_contains($type, 'complaint') || str_contains($type, 'case')) return 'bi-folder2-open';
    return 'bi-bell';
}
function notification_group($date) {
    $day = date('Y-m-d', strtotime($date));
    if ($day === date('Y-m-d')) return 'Today';
    if ($day === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date('F Y', strtotime($date));
}
function time_ago($date) {
    $timestamp = strtotime($date); $difference = time() - $timestamp;
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' min ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hr ago';
    return date('M d, Y · h:i A', $timestamp);
}
$currentGroup = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; background: #f4f7f2; display: block; justify-content: flex-start; padding: 0; }
        .notification-wrap { margin: 0 auto; max-width: 100%; padding: 24px; width: 100%; }
        .notification-heading { align-items: center; display: flex; gap: 16px; justify-content: space-between; margin-bottom: 18px; }
        .notification-heading h1 { color: #172017; font-size: 24px; margin: 0 0 4px; }
        .notification-heading p { color: #62705f; font-size: 13px; margin: 0; }
        .notification-summary { align-items: center; background: #fff; border: 1px solid #dce7d9; border-radius: 8px; display: flex; gap: 12px; padding: 12px 14px; }
        .summary-icon { align-items: center; background: #eaf7e8; border-radius: 8px; color: #177124; display: flex; font-size: 18px; height: 40px; justify-content: center; width: 40px; }
        .summary-count { color: #172017; font-size: 19px; font-weight: 400; line-height: 1; }
        .summary-label { color: #687565; font-size: 11px; margin-top: 4px; }
        .notification-panel { background: #fff; border: 1px solid #dce7d9; border-radius: 8px; box-shadow: 0 8px 26px rgba(18,60,27,.07); overflow: hidden; }
        .notification-toolbar { align-items: center; border-bottom: 1px solid #e5ece3; display: flex; justify-content: space-between; gap: 12px; min-height: 58px; padding: 12px 16px; }
        .notification-toolbar strong { color: #263225; font-size: 14px; }
        .notification-toolbar .btn { align-items: center; display: inline-flex; gap: 7px; }
        .notification-filters { align-items: end; border-bottom: 1px solid #e5ece3; display: grid; gap: 12px; grid-template-columns: minmax(220px,1fr) 180px auto; padding: 14px 16px; }
        .notification-filters .field { margin: 0; }
        .notification-pagination { align-items: center; display: flex; gap: 7px; justify-content: center; padding: 14px; }
        .notification-pagination button { background:#fff;border:1px solid #cfdbcc;border-radius:6px;color:#294729;cursor:pointer;min-height:34px;padding:6px 10px; }
        .notification-pagination button.active { background:#177124;color:#fff; }
        .notification-pagination button:disabled { cursor:not-allowed;opacity:.45; }
        .notification-group-title { background: #f7faf6; border-bottom: 1px solid #e7ede5; color: #657363; font-size: 11px; font-weight: 400; padding: 9px 18px; text-transform: uppercase; }
        .notification-item-row { align-items: flex-start; border-bottom: 1px solid #e9eee7; display: grid; gap: 13px; grid-template-columns: 44px minmax(0,1fr) auto; padding: 16px 18px; position: relative; transition: background .16s ease; }
        .notification-item-row:last-child { border-bottom: 0; }
        .notification-item-row:hover { background: #fafcf9; }
        .notification-item-row.unread { background: #f5fbf3; }
        .notification-item-row.unread:hover { background: #f0f8ee; }
        .notification-item-row.unread::before { background: #1a8f2c; bottom: 0; content: ''; left: 0; position: absolute; top: 0; width: 4px; }
        .item-icon { align-items: center; background: #edf3eb; border-radius: 8px; color: #426344; display: flex; font-size: 18px; height: 42px; justify-content: center; width: 42px; }
        .unread .item-icon { background: #dff1dc; color: #167224; }
        .item-content { min-width: 0; }
        .item-title-line { align-items: center; display: flex; gap: 8px; }
        .item-title { color: #1d2a1d; font-size: 14px; font-weight: 400; margin: 0; }
        .unread-dot { background: #1a8f2c; border-radius: 50%; display: inline-block; height: 7px; width: 7px; }
        .item-message { color: #586655; font-size: 13px; line-height: 1.55; margin: 5px 0 7px; overflow-wrap: anywhere; }
        .item-meta { align-items: center; color: #748071; display: flex; flex-wrap: wrap; font-size: 11px; gap: 10px; }
        .related-link { align-items: center; color: #176f22; display: inline-flex; font-weight: 300; gap: 5px; text-decoration: none; }
        .related-link:hover { text-decoration: underline; }
        .item-actions { align-items: flex-end; display: flex; flex-direction: column; gap: 8px; }
        .mark-read { background: transparent; border: 1px solid #cad7c7; color: #365136; font-size: 12px; padding: 7px 10px; }
        .read-label { color: #859083; font-size: 11px; white-space: nowrap; }
        .empty-state { align-items: center; display: flex; flex-direction: column; padding: 64px 24px; text-align: center; }
        .empty-state i { align-items: center; background: #eaf4e8; border-radius: 50%; color: #428247; display: flex; font-size: 28px; height: 64px; justify-content: center; width: 64px; }
        .empty-state h2 { color: #263225; font-size: 17px; margin: 14px 0 5px; }
        .empty-state p { color: #687565; font-size: 13px; margin: 0; }
        @media (max-width: 700px) { .notification-heading { align-items: flex-start; flex-direction: column; } .notification-summary { width: 100%; } .notification-item-row { grid-template-columns: 42px minmax(0,1fr); padding: 14px; } .item-actions { flex-direction: row; grid-column: 2; justify-content: flex-start; } }
        @media (max-width: 480px) { .notification-wrap { padding: 14px; } .notification-toolbar { align-items: flex-start; flex-direction: column; } .notification-toolbar form,.notification-toolbar button { width: 100%; } }
    </style>
</head>
<body>
<div class="dashboard-shell">
    <?php require __DIR__ . '/../layout/sidebar.php'; ?>
    <div class="app-content">
        <?php $pageTitle = 'Notifications'; require __DIR__ . '/../layout/topbar.php'; ?>
        <main class="notification-wrap">
            <header class="notification-heading">
                <div><h1>Notification Center</h1><p>Updates about your cases, hearings, messages, and account activity.</p></div>
                <div class="notification-summary"><span class="summary-icon"><i class="bi bi-bell"></i></span><div><div class="summary-count"><?= (int) $unreadCount ?></div><div class="summary-label">Unread notification<?= $unreadCount === 1 ? '' : 's' ?></div></div></div>
            </header>

            <section class="notification-panel" aria-label="Notifications">
                <div class="notification-toolbar">
                    <strong id="notificationResultCount"><?= count($notifications) ?> notification<?= count($notifications) === 1 ? '' : 's' ?></strong>
                    <form method="POST" action="index.php">
                        <?= Security::csrfField() ?>
                        <button class="btn btn-primary" type="submit" name="notification_action" value="mark_all" <?= $unreadCount === 0 ? 'disabled' : '' ?>><i class="bi bi-check2-all"></i> Mark All as Read</button>
                    </form>
                </div>
                <form class="notification-filters" id="notificationFilters" method="GET" action="index.php">
                    <div class="field"><label for="notification_search">Search</label><input id="notification_search" name="search" placeholder="Search title or message"></div>
                    <div class="field"><label for="notification_read">Status</label><select id="notification_read" name="read"><option value="">All Notifications</option><option value="unread">Unread</option><option value="read">Read</option></select></div>
                    <button class="btn btn-secondary" id="resetNotificationFilters" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                </form>

                <div id="notificationList">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state"><i class="bi bi-bell-slash"></i><h2>No notifications yet</h2><p>Updates related to your SICMS activity will appear here.</p></div>
                <?php endif; ?>

                <?php foreach ($notifications as $notification): ?>
                    <?php $group = notification_group($notification['created_at']); ?>
                    <?php if ($group !== $currentGroup): $currentGroup = $group; ?><div class="notification-group-title"><?= h($group) ?></div><?php endif; ?>
                    <?php $isUnread = (int) $notification['is_read'] === 0; ?>
                    <article class="notification-item-row <?= $isUnread ? 'unread' : '' ?>">
                        <span class="item-icon"><i class="bi <?= h(notification_icon($notification['type'])) ?>"></i></span>
                        <div class="item-content">
                            <div class="item-title-line"><h2 class="item-title"><?= h($notification['title']) ?></h2><?php if ($isUnread): ?><span class="unread-dot" title="Unread"></span><?php endif; ?></div>
                            <p class="item-message"><?= h($notification['message']) ?></p>
                            <div class="item-meta"><span><i class="bi bi-clock"></i> <?= h(time_ago($notification['created_at'])) ?></span><?php if (!empty($notification['link'])): ?><a class="related-link" href="../../../<?= h($notification['link']) ?>">Open related page <i class="bi bi-arrow-right"></i></a><?php endif; ?></div>
                        </div>
                        <div class="item-actions">
                            <?php if ($isUnread): ?><form method="POST" action="index.php"><?= Security::csrfField() ?><input type="hidden" name="notification_id" value="<?= (int) $notification['notification_id'] ?>"><button class="btn mark-read" type="submit" name="notification_action" value="mark_one"><i class="bi bi-check2"></i> Mark read</button></form><?php else: ?><span class="read-label"><i class="bi bi-check2"></i> Read</span><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
                </div>
                <nav class="notification-pagination" id="notificationPagination" aria-label="Notification pages"></nav>
            </section>
        </main>
    </div>
</div>
<script>
(()=>{const form=document.getElementById('notificationFilters'),list=document.getElementById('notificationList'),pager=document.getElementById('notificationPagination'),count=document.getElementById('notificationResultCount');if(!form||!list)return;const size=10,esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));let items=<?= json_encode(array_values($notifications), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,page=1,timer,request;const icon=t=>{t=String(t).toLowerCase();return t.includes('message')?'bi-chat-dots':t.includes('hearing')?'bi-calendar-event':t.includes('revision')?'bi-pencil-square':t.includes('case')||t.includes('complaint')?'bi-folder2-open':'bi-bell'};function render(){const pages=Math.max(1,Math.ceil(items.length/size));page=Math.min(page,pages);const slice=items.slice((page-1)*size,page*size);list.innerHTML=slice.length?slice.map(n=>`<article class="notification-item-row ${+n.is_read===0?'unread':''}"><span class="item-icon"><i class="bi ${icon(n.type)}"></i></span><div class="item-content"><div class="item-title-line"><h2 class="item-title">${esc(n.title)}</h2></div><p class="item-message">${esc(n.message)}</p><div class="item-meta"><span><i class="bi bi-clock"></i> ${esc(new Date(n.created_at.replace(' ','T')).toLocaleString())}</span>${n.link?`<a class="related-link" href="../../../${esc(n.link)}">Open related page <i class="bi bi-arrow-right"></i></a>`:''}</div></div><div class="item-actions">${+n.is_read===0?`<form method="POST" action="index.php"><input type="hidden" name="csrf_token" value="<?= h(Security::csrfToken()) ?>"><input type="hidden" name="notification_id" value="${+n.notification_id}"><button class="btn mark-read" name="notification_action" value="mark_one">Mark read</button></form>`:'<span class="read-label"><i class="bi bi-check2"></i> Read</span>'}</div></article>`).join(''):'<div class="empty-state"><i class="bi bi-bell-slash"></i><h2>No notifications found</h2><p>Try changing the current search or status filter.</p></div>';pager.innerHTML=pages<=1?'':`<button data-page="1" ${page===1?'disabled':''}>First</button><button data-page="${page-1}" ${page===1?'disabled':''}>Previous</button><button class="active" disabled>Page ${page} of ${pages}</button><button data-page="${page+1}" ${page===pages?'disabled':''}>Next</button><button data-page="${pages}" ${page===pages?'disabled':''}>Last</button>`;count.textContent=`${items.length} notification${items.length===1?'':'s'}`}async function load(){if(request)request.abort();request=new AbortController();const p=new URLSearchParams(new FormData(form));p.set('ajax','1');const r=await fetch(`index.php?${p}`,{headers:{'X-Requested-With':'XMLHttpRequest'},signal:request.signal});const d=await r.json();if(!d.success)throw Error(d.message);items=d.notifications;page=1;render();p.delete('ajax');history.replaceState(null,'',p.toString()?`index.php?${p}`:'index.php')}pager.addEventListener('click',e=>{const b=e.target.closest('[data-page]');if(b){page=+b.dataset.page;render()}});form.addEventListener('submit',e=>{e.preventDefault();load().catch(()=>{})});form.elements.read.addEventListener('change',load);form.elements.search.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(load,400)});document.getElementById('resetNotificationFilters').addEventListener('click',()=>{form.reset();load()});render()})();
</script>
</body>
</html>
