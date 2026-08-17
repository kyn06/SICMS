<?php
require_once __DIR__ . '/../../controllers/HearingController.php';

$controller = new HearingController();
$hearingId = (int) ($_GET['id'] ?? 0);
$viewData = $controller->edit($hearingId);

$hearing = $viewData['hearing'];
$user = $viewData['user'];
$cases = $viewData['cases'];
$errors = $viewData['errors'];
$old = $viewData['old'];

function h($value) {
    return htmlspecialchars((string) $value);
}

$selectedComplaintId = $old['complaint_id'] ?? $hearing['complaint_id'];
$selectedDatetime = $old['hearing_datetime'] ?? date('Y-m-d\TH:i', strtotime($hearing['hearing_datetime']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hearing | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { align-items: stretch; justify-content: flex-start; background: #f5f7f4; padding: 0; }
        .header { background: #123c1b; color: #fff; display: flex; justify-content: space-between; padding: 22px 30px; }
        .header h1 { font-size: 24px; }
        .header a { background: #fff; border-radius: 8px; color: #123c1b; padding: 10px 14px; text-decoration: none; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 24px; width: 100%; }
        .panel { background: #fff; border: 1px solid #dce5da; border-radius: 8px; padding: 20px; }
        .alert { background: #fff5f5; border: 1px solid #dc3545; border-radius: 8px; color: #b42318; font-size: 14px; margin-bottom: 18px; padding: 12px 14px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        label { color: #536052; font-size: 13px; font-weight: 500; }
        input, select, textarea { border: 1px solid #b9c7b7; border-radius: 8px; font-family: inherit; font-size: 14px; padding: 10px 12px; width: 100%; }
        textarea { min-height: 110px; resize: vertical; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .btn { border: 0; border-radius: 8px; cursor: pointer; font-family: inherit; font-size: 14px; padding: 11px 16px; text-decoration: none; }
        .btn-secondary { background: #e9f5e7; color: #123c1b; }
        .btn-primary { background: #1A9D00; color: #fff; }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } .header { flex-direction: column; gap: 14px; } }
    </style>
    <link rel="stylesheet" href="../layout/system.css">
    <link rel="stylesheet" href="../layout/hearings.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Edit Hearing'; require __DIR__ . '/../layout/topbar.php'; ?>

    <main class="wrap hearing-form-page">
        <div class="hearing-form-toolbar">
            <a class="btn btn-secondary" href="index.php"><i class="bi bi-arrow-left"></i> Back to Hearings</a>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="panel hearing-form-panel" method="POST" action="edit.php?id=<?= (int) $hearing['hearing_id'] ?>">
            <?= Security::csrfField() ?>
            <div class="hearing-form-heading">
                <span><i class="bi bi-calendar2-week"></i></span>
                <div><h2>Hearing Details</h2><p><?= h($hearing['case_number']) ?></p></div>
                <span class="status hearing-current-status"><?= h($hearing['status']) ?></span>
            </div>
            <div class="grid hearing-form-grid">
                <div class="field full">
                    <label for="complaint_id">Case</label>
                    <select id="complaint_id" name="complaint_id" required>
                        <?php foreach ($cases as $case): ?>
                            <option value="<?= (int) $case['complaint_id'] ?>" <?= ((int) $selectedComplaintId === (int) $case['complaint_id']) ? 'selected' : '' ?>>
                                <?= h($case['case_number'] . ' - ' . $case['complainant_name'] . ' (' . $case['status'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="hearing_datetime">Date and Time</label>
                    <input id="hearing_datetime" type="datetime-local" name="hearing_datetime" value="<?= h($selectedDatetime) ?>" required>
                </div>
                <div class="field">
                    <label for="venue">Venue</label>
                    <input id="venue" name="venue" value="<?= h($old['venue'] ?? $hearing['venue']) ?>" required>
                </div>
                <div class="field full">
                    <label for="google_meet_link">Google Meet Link</label>
                    <input id="google_meet_link" type="url" name="google_meet_link" value="<?= h($old['google_meet_link'] ?? $hearing['google_meet_link']) ?>">
                </div>
                <div class="field full">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks"><?= h($old['remarks'] ?? $hearing['remarks']) ?></textarea>
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save Changes</button>
                <a class="btn btn-secondary" href="index.php"><i class="bi bi-x"></i> Cancel</a>
            </div>
        </form>
    </main>
        </div>
    </div>
</body>

</html>
