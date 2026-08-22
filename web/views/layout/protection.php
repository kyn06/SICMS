<?php

if (defined('SICMS_PROTECTION_LOADED')) {
    return;
}

if (!isset($user) || !is_array($user)) {
    return;
}

define('SICMS_PROTECTION_LOADED', true);

// =====================================================================
// BLUR SHIELD TOGGLE
// Set to false (or comment the line out) to disable the blur-on-focus-
// loss protection. PrintScreen logging, clipboard wipe and print block
// stay active either way.
// =====================================================================
$sicmsBlurShieldEnabled = false;

require_once __DIR__ . '/../../helpers/Security.php';
require_once __DIR__ . '/../../../routes.php';

Security::startSession();
?>
<style>
    .sicms-shield {
        -webkit-backdrop-filter: blur(18px);
        backdrop-filter: blur(18px);
        background: rgba(245, 247, 244, 0.45);
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2147483400;
    }

    .sicms-shield span {
        color: #657164;
        font: bold 14px Verdana, sans-serif;
        left: 50%;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%);
        white-space: nowrap;
    }

    body.sicms-shielded .sicms-shield {
        display: block;
    }

    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        body.sicms-shielded .sicms-shield {
            background: rgba(245, 247, 244, 0.92);
        }
    }

    @media print {
        .dashboard-shell {
            visibility: hidden;
        }
    }

    .sicms-guard-toast {
        background: #17201a;
        border-radius: 8px;
        bottom: 28px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        color: #fff;
        font: 13px/1.45 Verdana, sans-serif;
        left: 50%;
        max-width: min(420px, calc(100vw - 32px));
        opacity: 0;
        padding: 12px 18px;
        pointer-events: none;
        position: fixed;
        transform: translate(-50%, 12px);
        transition: opacity 0.25s ease, transform 0.25s ease;
        z-index: 2147483600;
    }

    .sicms-guard-toast.show {
        opacity: 1;
        transform: translate(-50%, 0);
    }
</style>
<?php if (!empty($sicmsBlurShieldEnabled)): ?>
<div class="sicms-shield" aria-hidden="true"><span>Content hidden while this window is inactive</span></div>
<?php endif; ?>
<script>
    window.SICMS_PROTECTION = {
        endpoint: <?= json_encode(app_url('web/api/security_event.php')) ?>,
        token: <?= json_encode(Security::csrfToken()) ?>,
        shield: <?= json_encode(!empty($sicmsBlurShieldEnabled)) ?>
    };
</script>
<script src="<?= htmlspecialchars(app_url('web/views/layout/protection.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
