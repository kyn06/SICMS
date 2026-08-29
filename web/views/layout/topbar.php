<?php
require __DIR__ . '/protection.php';
require __DIR__ . '/navigation.php';
require_once __DIR__ . '/../../helpers/Security.php';
require_once __DIR__ . '/../../models/Notification.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$hidePageTitle = $hidePageTitle ?? false;

$unreadNotificationCount = 0;
$recentNotifications = [];

if (isset($user['account_id'])) {
    $unreadNotificationCount = Notification::unreadCount((int) $user['account_id']);
    $recentNotifications = Notification::recentForUser((int) $user['account_id'], 5);
}

if (!function_exists('format_time_ago')) {
    function format_time_ago($dateTime) {
        $timestamp = strtotime((string) $dateTime);
        if (!$timestamp) return '';
        $diff = time() - $timestamp;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
        return date('M d, Y', $timestamp);
    }
}
?>
<div class="app-topbar-container">
    <header class="app-topbar<?= $hidePageTitle ? ' app-topbar-titleless' : '' ?>">
        <button class="sidebar-toggle" type="button" aria-label="Toggle navigation" aria-controls="app-sidebar" aria-expanded="false">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <?php if (!$hidePageTitle): ?>
            <div class="app-topbar-title">
                <h1><?= h($pageTitle) ?></h1>
                <p><?= h($roleLabel) ?></p>
            </div>
        <?php endif; ?>
        <div class="app-topbar-actions">
            <span class="topbar-date" style="font-weight: 200;"><?= h(date('F d, Y h:i A')) ?></span>
            <details class="profile-dropdown">
                <summary class="topbar-icon" title="Notifications" aria-label="Notifications">
                    <i class="bi bi-bell" aria-hidden="true" style="font-size:15ipx;"></i>
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="notification-badge"><?= (int) $unreadNotificationCount ?></span>
                    <?php endif; ?>
                </summary>
                <div class="dropdown-menu" data-notification-open>
                    <?php if (empty($recentNotifications)): ?>
                        <div class="notification-item">
                            <span class="notification-icon"><i class="bi bi-bell-slash"></i></span>
                            <div>
                                <div class="notification-title">No notifications yet</div>
                                <div class="notification-message">Unread notices will appear here.</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($recentNotifications as $notification): ?>
                        <?php $notificationDestination = !empty($notification['link']) ? app_url($notification['link']) : app_route('notifications.index'); ?>
                        <a class="notification-item<?= (int) $notification['is_read'] === 0 ? ' unread' : '' ?>"
                            href="<?= h($notificationDestination) ?>"
                            data-notification-id="<?= (int) $notification['notification_id'] ?>"
                            data-unread="<?= (int) $notification['is_read'] === 0 ? '1' : '0' ?>"
                            data-destination="<?= h($notificationDestination) ?>">
                            <span class="notification-icon"><i class="bi bi-info-circle"></i></span>
                            <span>
                                <span class="notification-title"><?= h($notification['title']) ?></span>
                                <span class="notification-message"><?= h($notification['message']) ?></span>
                                <span class="notification-time"><?= h(format_time_ago($notification['created_at'])) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>

                    <div class="dropdown-footer">
                        <form method="POST" action="<?= h(app_route('notifications.index')) ?>" data-mark-all-url="<?= h(app_url('web/api/notifications.php')) ?>">
                            <?= Security::csrfField() ?>
                            <button class="text-button" type="submit" name="notification_action" value="mark_all">Mark all as read</button>
                        </form>
                        <a class="text-button" href="<?= h(app_route('notifications.index')) ?>">View all</a>
                    </div>
                </div>
            </details>
        </div>
    </header>
</div>
<script>
document.querySelector('.sidebar-toggle')?.addEventListener('click', function () {
    const open = document.body.classList.toggle('sidebar-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
<script>
window.SICMS_NOTIFY = {
    csrf: <?= json_encode(Security::csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    api: <?= json_encode(app_url('web/api/notifications.php'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
</script>
<script src="<?= h(app_url('web/views/layout/system.js')) ?>?v=20260829c" defer></script>
