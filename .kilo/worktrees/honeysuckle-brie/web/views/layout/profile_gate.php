<?php
if (!isset($profileIncomplete) || !$profileIncomplete) {
    return;
}

require_once __DIR__ . '/../../helpers/ProfileCompletion.php';

$profileGateMode = isset($profileGateMode) ? $profileGateMode : 'auto';
$missingFields = isset($missingFields) && is_array($missingFields) ? $missingFields : [];
$blurTarget = isset($blurTarget) ? $blurTarget : '.app-content';

if (!isset($profileGateSettingsUrl)) {
    if (function_exists('app_route')) {
        $profileGateSettingsUrl = app_route('settings.index');
    } else {
        $profileGateSettingsUrl = '../settings/index.php';
    }
}

if (!function_exists('profile_gate_h')) {
    function profile_gate_h($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$missingSummary = count($missingFields) > 0
    ? implode(', ', array_slice($missingFields, 0, 4)) . (count($missingFields) > 4 ? ', …' : '')
    : 'student information';
?>
<style>
    .sicms-gate-overlay {
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        background: rgba(18, 46, 24, 0.45);
        inset: 0;
        position: fixed;
        z-index: 2147483500;
    }

    .sicms-gate-banner {
        background: #fff;
        border: 1px solid #dce7d9;
        border-radius: 16px;
        box-shadow: 0 24px 64px rgba(10, 35, 18, 0.28);
        display: none;
        left: 50%;
        max-width: min(440px, calc(100vw - 36px));
        padding: 22px;
        position: fixed;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
        z-index: 2147483600;
    }

    .sicms-gate-banner.sicms-gate-open {
        display: block;
        animation: sicms-gate-pop .2s ease-out;
    }

    @keyframes sicms-gate-pop {
        from {
            opacity: 0;
            transform: translate(-50%, -52%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }

    .sicms-gate-banner-icon {
        align-items: center;
        background: #fff4e5;
        border-radius: 12px;
        color: #b26a00;
        display: flex;
        font-size: 22px;
        height: 52px;
        justify-content: center;
        margin-bottom: 14px;
        width: 52px;
    }

    .sicms-gate-banner h3 {
        color: #162016;
        font-size: 18px;
        font-weight: 800;
        margin: 0 0 6px;
    }

    .sicms-gate-banner p {
        color: #54624f;
        font-size: 13.5px;
        line-height: 1.55;
        margin: 0 0 16px;
    }

    .sicms-gate-banner .sicms-gate-missing {
        background: #f7faf6;
        border: 1px solid #e2eadf;
        border-radius: 8px;
        color: #62705f;
        font-size: 12px;
        line-height: 1.5;
        margin-bottom: 16px;
        padding: 10px 12px;
    }

    .sicms-gate-actions {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .sicms-gate-actions .btn {
        align-items: center;
        border-radius: 8px;
        display: inline-flex;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        gap: 8px;
        justify-content: center;
        min-height: 40px;
        padding: 9px 16px;
        text-decoration: none;
        transition: box-shadow .16s ease, transform .16s ease;
    }

    .sicms-gate-actions .btn-primary {
        background: #1A9D00;
        color: #fff;
    }

    .sicms-gate-actions .btn-primary:hover {
        box-shadow: 0 8px 20px rgba(26, 157, 0, .3);
        transform: translateY(-1px);
    }

    .sicms-gate-actions .btn-text {
        background: none;
        border: 0;
        color: #62705f;
        cursor: pointer;
        padding: 9px 10px;
    }

    .sicms-gate-actions .btn-text:hover {
        color: #162016;
    }

    body.sicms-gate-popup-open {
        overflow: hidden;
    }

    .sicms-gate-blur {
        filter: blur(5px);
        pointer-events: none;
        -webkit-user-select: none;
        user-select: none;
    }

    .sicms-gate-trigger {
        cursor: not-allowed;
        opacity: .62;
        position: relative;
        text-decoration: none;
    }

    .sicms-gate-trigger:hover {
        opacity: .78;
    }
</style>

<div class="sicms-gate-overlay" id="sicmsGateOverlay" hidden></div>

<div class="sicms-gate-banner" id="sicmsGateBanner" role="dialog" aria-modal="true" aria-labelledby="sicmsGateTitle">
    <div class="sicms-gate-banner-icon"><i class="bi bi-person-exclamation" aria-hidden="true"></i></div>
    <h3 id="sicmsGateTitle">Complete your account settings first</h3>
    <p>Your profile is not yet complete. Please fill in your missing information in the Account Settings before you can
        submit or track complaints.</p>
    <?php if (!empty($missingFields)): ?>
        <div class="sicms-gate-missing"><strong>Still missing:</strong> <?= profile_gate_h($missingSummary) ?></div>
    <?php endif; ?>
    <div class="sicms-gate-actions">
        <button type="button" class="btn btn-text sicms-gate-dismiss">Later</button>
        <a class="btn btn-primary" href="<?= profile_gate_h($profileGateSettingsUrl) ?>"><i class="bi bi-gear"
                aria-hidden="true"></i> Go to Account Settings</a>
    </div>
</div>

<script>
    (function () {
        var mode = <?= json_encode($profileGateMode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var blurTargetSelector = <?= json_encode($blurTarget, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var overlay = document.getElementById('sicmsGateOverlay');
        var banner = document.getElementById('sicmsGateBanner');

        if (!banner) return;

        function openGate() {
            banner.classList.add('sicms-gate-open');
            if (overlay) overlay.hidden = false;
            document.body.classList.add('sicms-gate-popup-open');
        }

        function closeGate() {
            banner.classList.remove('sicms-gate-open');
            if (overlay) overlay.hidden = true;
            document.body.classList.remove('sicms-gate-popup-open');
        }

        document.querySelectorAll('.sicms-gate-dismiss').forEach(function (button) {
            button.addEventListener('click', closeGate);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeGate();
        });

        if (overlay) {
            overlay.addEventListener('click', function () {
                if (mode !== 'auto') closeGate();
            });
        }

        if (mode === 'auto') {
            var target = document.querySelector(blurTargetSelector);
            if (target) target.classList.add('sicms-gate-blur');
            openGate();
        } else {
            document.querySelectorAll('.sicms-gate-trigger').forEach(function (el) {
                el.addEventListener('click', function (event) {
                    event.preventDefault();
                    openGate();
                });
            });
        }
    })();
</script>