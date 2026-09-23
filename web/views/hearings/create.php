<?php
require_once __DIR__ . '/../../controllers/HearingController.php';

$controller = new HearingController();
$viewData = $controller->create();

$user = $viewData['user'];
$cases = $viewData['cases'];
$errors = $viewData['errors'];
$fieldErrors = $viewData['fieldErrors'] ?? [];
$old = $viewData['old'];

function h($value) {
    return htmlspecialchars((string) $value);
}

function field_error_html($fieldErrors, $field) {
    $message = $fieldErrors[$field] ?? '';
    return $message !== '' ? '<div class="field-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Hearing | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; justify-content: flex-start; background: var(--bg-page, #f5f7f4); color: var(--text-primary, #172017); padding: 0; }
        .header { background: var(--bg-sidebar, #123c1b); color: #fff; display: flex; justify-content: space-between; padding: 22px 30px; }
        .header h1 { font-size: 24px; }
        .header a { background: var(--surface-primary, #fff); border-radius: 8px; color: var(--text-secondary, #123c1b); padding: 10px 14px; text-decoration: none; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 24px; width: 100%; }
        .panel { background: var(--surface-primary, #fff); border: 1px solid var(--border-primary, #dce5da); border-radius: 8px; padding: 20px; }
        .alert { background: var(--status-danger-bg, #fff5f5); border: 1px solid rgba(180,35,24,.5); border-radius: 8px; color: var(--status-danger-text, #b42318); font-size: 14px; margin-bottom: 18px; padding: 12px 14px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        label { color: var(--text-muted, #536052); font-size: 13px; font-weight: 500; }
        input, select, textarea { border: 1px solid var(--input-border, #b9c7b7); border-radius: 8px; font-family: inherit; font-size: 14px; padding: 10px 12px; width: 100%; background: var(--input-bg, #fff); color: var(--text-primary, #172017); }
        textarea { min-height: 110px; resize: vertical; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .btn { border: 0; border-radius: 8px; cursor: pointer; font-family: inherit; font-size: 14px; padding: 11px 16px; text-decoration: none; }
        .btn-secondary { background: rgba(26, 157, 0, 0.08); color: var(--text-secondary, #123c1b); }
        .btn-primary { background: var(--accent, #1A9D00); color: #fff; }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } .header { flex-direction: column; gap: 14px; } }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/hearings.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Schedule Hearing'; require __DIR__ . '/../layout/topbar.php'; ?>

    <main class="wrap hearing-form-page">
        <div class="hearing-form-toolbar">
            <a class="btn btn-secondary" href="index.php"><i class="bi bi-arrow-left"></i> Back to Hearings</a>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="panel hearing-form-panel" method="POST" action="create.php" data-sicms-validate>
            <?= Security::csrfField() ?>
            <div class="hearing-form-heading">
                <span><i class="bi bi-calendar-plus"></i></span>
                <div><h2>Hearing Details</h2><p>New hearing schedule</p></div>
            </div>
            <div class="grid hearing-form-grid">
                <div class="field full">
                    <label for="complaint_id">Case <span class="required">*</span></label>
                    <select id="complaint_id" name="complaint_id" required>
                        <option value="">Select verified or assigned case</option>
                        <?php foreach ($cases as $case): ?>
                            <option value="<?= (int) $case['complaint_id'] ?>" <?= (($old['complaint_id'] ?? '') == $case['complaint_id']) ? 'selected' : '' ?>>
                                <?= h($case['case_number'] . ' - ' . $case['complainant_name'] . ' (' . $case['status'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error_html($fieldErrors, 'complaint_id') ?>
                </div>
                <div class="field">
                    <label for="hearing_datetime">Date and Time <span class="required">*</span></label>
                    <input id="hearing_datetime" type="datetime-local" name="hearing_datetime" value="<?= h($old['hearing_datetime'] ?? '') ?>" required min="<?= h(date('Y-m-d\TH:i')) ?>" data-sicms-past="0">
                    <?= field_error_html($fieldErrors, 'hearing_datetime') ?>
                </div>
                <div class="field">
                    <label for="venue">Venue <span class="required">*</span></label>
                    <input id="venue" name="venue" value="<?= h($old['venue'] ?? '') ?>" required>
                    <?= field_error_html($fieldErrors, 'venue') ?>
                </div>
                <div class="field full">
                    <label for="google_meet_link">Google Meet Link</label>
                    <div class="meet-field-row">
                        <input id="google_meet_link" type="url" name="google_meet_link" value="<?= h($old['google_meet_link'] ?? '') ?>" placeholder="Paste a link or generate one below">
                        <input type="hidden" name="google_event_id" id="google_event_id" value="<?= h($old['google_event_id'] ?? '') ?>">
                        <button type="button" class="btn btn-secondary" id="generateMeetBtn"><i class="bi bi-camera-video"></i> Generate Meet Link</button>
                    </div>
                    <span class="meet-field-hint" id="meetFieldHint"></span>
                    <?= field_error_html($fieldErrors, 'google_meet_link') ?>
                </div>
                <div class="field full">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks"><?= h($old['remarks'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><i class="bi bi-calendar-check"></i> Schedule Hearing</button>
                <a class="btn btn-secondary" href="index.php"><i class="bi bi-x"></i> Cancel</a>
            </div>
        </form>
    </main>
        </div>
    </div>
    <script>
    (()=>{const btn=document.getElementById('generateMeetBtn');if(!btn)return;const form=btn.closest('form'),hint=document.getElementById('meetFieldHint'),link=document.getElementById('google_meet_link'),eventId=document.getElementById('google_event_id');btn.addEventListener('click',async()=>{if(!form)return;hint.textContent='';btn.disabled=true;btn.innerHTML='<i class="bi bi-hourglass-split"></i> Creating...';try{const res=await fetch('create_meet.php',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:new URLSearchParams(new FormData(form))});const data=await res.json();if(!data.success)throw new Error(data.message||'Could not create Meet link.');link.value=data.meet_link||'';eventId.value=data.google_event_id||'';hint.textContent='Meet link generated and will be attached to the calendar event when you schedule.';hint.className='meet-field-hint ok';}catch(err){hint.textContent=err.message;hint.className='meet-field-hint err';}finally{btn.disabled=false;btn.innerHTML='<i class="bi bi-camera-video"></i> Generate Meet Link';}});})();
    </script>
</body>

</html>
