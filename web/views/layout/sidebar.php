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
<script>
try {
    const savedTheme = localStorage.getItem('sicms-theme') || document.cookie.match(/(?:^|; )sicms-theme=([^;]+)/)?.[1];
    if (savedTheme === 'dark') document.documentElement.dataset.theme = 'dark';
} catch (error) {}
</script>
<aside class="sidebar" id="app-sidebar">
    <div class="brand">
        <img src="<?= h(app_url('public/assets/clsulogo.png')) ?>" alt="CLSU logo">
        <div>
            <div class="brand-title">Student Discipline and Reformation Unit</div>
        </div>
    </div>

    <div class="sidebar-profile">
        <div class="avatar" aria-hidden="true"><?php $avatarUrl = profile_pic_url($user); ?>
        <?php if ($avatarUrl): ?><img src="<?= h($avatarUrl) ?>" alt=""><?php else: ?><?= h($initials) ?><?php endif; ?></div>
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

    <div class="sidebar-spacer"></div>

    <a class="logout-fixed" href="<?= h(app_route('logout')) ?>">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        Logout
    </a>

    <div class="sidebar-footer">
        <a href="<?= h(app_url('web/views/auth/terms.php')) ?>">Terms of Service</a> &middot;
        <a href="<?= h(app_url('web/views/auth/privacy.php')) ?>">Privacy Policy</a>
    </div>
</aside>

<!-- Cookie Consent Banner -->
<div id="cookieConsent" class="cookie-banner" style="display:none;">
    <div class="cookie-banner-inner">
        <div class="cookie-banner-text">
            <div class="cookie-banner-title"><i class="bi bi-cookie"></i> Cookie Consent</div>
            <p>DARIS uses cookies to ensure the system works properly. These include:</p>
            <ul>
                <li><strong>Session cookies</strong> &mdash; Keep you logged in and maintain your session state.</li>
                <li><strong>CSRF tokens</strong> &mdash; Protect forms from cross-site request forgery attacks.</li>
            </ul>
            <p>These are strictly necessary for the system to function and are always active. No tracking or analytics cookies are currently used.</p>
            <p class="cookie-banner-note">Your preference is saved for 12 months. You can change it anytime by clearing your browser cookies.</p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="cookie-btn cookie-deny" onclick="setCookieConsent('denied')">Deny</button>
            <button type="button" class="cookie-btn cookie-accept" onclick="setCookieConsent('accepted')">Accept All</button>
        </div>
    </div>
</div>

<script>
(function() {
    var banner = document.getElementById('cookieConsent');
    if (!banner) return;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    if (!getCookie('sicms_cookie_consent')) {
        banner.style.display = 'block';
    }

    window.setCookieConsent = function(choice) {
        var date = new Date();
        date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
        document.cookie = 'sicms_cookie_consent=' + choice + ';expires=' + date.toUTCString() + ';path=/;SameSite=Lax';
        banner.style.display = 'none';
    };
})();
</script>
