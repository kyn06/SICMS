<?php
require __DIR__ . '/navigation.php';
$userEmail = isset($user['email']) ? $user['email'] : '';

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('allowed_for_role')) {
    function allowed_for_role(array $item, $roleKey) {
        return in_array('*', $item['roles'], true) || in_array($roleKey, $item['roles'], true);
    }
}

if (!function_exists('app_route')) {
    function app_route($name) {
        return $name;
    }
}
?>
<aside class="sidebar" id="app-sidebar">
    <div class="brand">
        <img src="<?= h(app_url('public/assets/clsulogo.png')) ?>" alt="CLSU logo">
        <div>
            <div class="brand-title">SICMS Dashboard</div>
            <div class="brand-subtitle">Student Discipline and Reformation Unit</div>
        </div>
    </div>

    <div class="sidebar-profile">
        <div class="avatar" aria-hidden="true"><?= h($initials) ?></div>
        <div>
            <div class="profile-name"><?= h($displayName) ?></div>
            <div class="profile-role"><?= h($roleLabel) ?></div>
            <div class="profile-email"><?= h($userEmail) ?></div>
        </div>
    </div>

    <?php foreach ($navGroups as $groupLabel => $items): ?>
        <?php $visibleItems = array_filter($items, fn($item) => allowed_for_role($item, $roleKey)); ?>
        <?php if (!empty($visibleItems)): ?>
            <div class="nav-group">
                <div class="nav-heading"><?= h($groupLabel) ?></div>
                <nav class="nav-list" aria-label="<?= h($groupLabel) ?> navigation">
                    <?php foreach ($visibleItems as $item): ?>
                        <a class="nav-link <?= $item['route'] !== null && $item['route'] === $currentRoute ? 'active' : '' ?>" href="<?= h($item['href']) ?>">
                            <i class="bi <?= h($item['icon']) ?>" aria-hidden="true"></i>
                            <span><?= h($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <a class="logout-fixed" href="<?= h(app_route('logout')) ?>">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        Logout
    </a>
</aside>
