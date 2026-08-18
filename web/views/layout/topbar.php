<?php
require __DIR__ . '/navigation.php';
$pageTitle = $pageTitle ?? 'Dashboard';
$hidePageTitle = $hidePageTitle ?? false;
?>
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
        <a class="topbar-icon" href="<?= h(app_route('notifications.index')) ?>" title="Notifications" aria-label="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
        </a>
        <details class="profile-dropdown">
            <summary>
                <span class="avatar" aria-hidden="true"><?= h($initials) ?></span>
                <span class="profile-dropdown-name"><?= h($displayName) ?></span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="profile-dropdown-menu">
                <span><?= h($user['email'] ?? '') ?></span>
                <a href="<?= h(app_route('logout')) ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </details>
    </div>
</header>
<script>
document.querySelector('.sidebar-toggle')?.addEventListener('click', function () {
    const open = document.body.classList.toggle('sidebar-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
<script src="<?= h(app_url('web/views/layout/system.js')) ?>" defer></script>
