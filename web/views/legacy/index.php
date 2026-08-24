<?php
require_once __DIR__ . '/../../controllers/LegacyController.php';
require_once __DIR__ . '/../../../routes.php';

$controller = new LegacyController();
$viewData = $controller->index();

$user = $viewData['user'];
$entries = $viewData['entries'];
$message = $viewData['message'];
$errors = $viewData['errors'];

$controller->clearFlash();

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function legacy_initials(string $fullName): string {
    $parts = preg_split('/\s+/', trim($fullName)) ?: [];
    $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));

    return trim($initials) ?: 'U';
}

function legacy_photo_url(?string $photoPath): string {
    if ($photoPath && str_starts_with($photoPath, 'storage/')) {
        $absolute = dirname(__DIR__, 3) . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoPath);
        if (is_file($absolute)) {
            return app_url($photoPath);
        }
    }

    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legacy of SDRU In-Charge | SICMS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/accounts.css?v=2">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/legacy.css?v=6">
</head>
<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Legacy of SDRU In-Charge'; require __DIR__ . '/../layout/topbar.php'; ?>

            <main class="wrap legacy-page">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= h($message) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?= h($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <section class="panel legacy-hero">
                    <div class="legacy-hero-text">
                        <h2><i class="bi bi-award" aria-hidden="true"></i> Legacy of SDRU In-Charge</h2>
                        <p>Honoring the leaders who have served as heads of the Student Discipline and Reformation Unit.</p>
                    </div>
                    <button type="button" class="btn btn-primary legacy-add-open">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add In-Charge
                    </button>
                </section>

                <?php if (empty($entries)): ?>
                    <section class="panel legacy-empty">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <h3>No legacy records yet</h3>
                        <p>Added SDRU in-charges will appear here.</p>
                    </section>
                <?php else: ?>
                    <div class="legacy-list">
                        <?php foreach ($entries as $entry): ?>
                            <?php $photoUrl = legacy_photo_url($entry['photo_path'] ?? null); ?>
                            <article class="panel legacy-card" id="legacy-entry-<?= (int) $entry['legacy_id'] ?>" data-legacy-id="<?= (int) $entry['legacy_id'] ?>">
                                <div class="legacy-card-photo">
                                    <?php if ($photoUrl): ?>
                                        <img src="<?= $photoUrl ?>" alt="Photo of <?= h($entry['full_name']) ?>">
                                    <?php else: ?>
                                        <span class="legacy-photo-fallback"><?= h(legacy_initials((string) $entry['full_name'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="legacy-card-info">
                                    <div class="legacy-card-topline">
                                        <h3><?= h($entry['full_name']) ?></h3>
                                        <span class="status-pill"><?= h($entry['position']) ?></span>
                                    </div>
                                    <?php if (!empty($entry['tenure'])): ?>
                                        <div class="legacy-tenure"><i class="bi bi-calendar3" aria-hidden="true"></i> <?= h($entry['tenure']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['description'])): ?>
                                        <p class="legacy-card-description"><?= nl2br(h($entry['description'])) ?></p>
                                    <?php endif; ?>
                                    <div class="legacy-card-actions">
                                        <button type="button" class="icon-action legacy-view-open" title="View profile" aria-label="View profile of <?= h($entry['full_name']) ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                        <form method="POST" action="index.php" data-entry-name="<?= h($entry['full_name']) ?>">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="legacy_action" value="delete">
                                            <input type="hidden" name="legacy_id" value="<?= (int) $entry['legacy_id'] ?>">
                                            <button type="submit" class="icon-action icon-danger" title="Delete entry" aria-label="Delete entry of <?= h($entry['full_name']) ?>">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <div class="legacy-modal-overlay" id="legacyModalOverlay" hidden>
        <div class="legacy-modal" role="dialog" aria-modal="true" aria-labelledby="legacyModalName">
            <button type="button" class="legacy-modal-close" id="legacyModalClose" aria-label="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>

            <!-- View (banner) mode -->
            <div class="legacy-modal-view" id="legacyModalView">
                <button type="button" class="icon-action legacy-edit-open" id="legacyEditOpen" title="Edit entry" aria-label="Edit entry">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </button>
                <div class="legacy-banner-photo">
                    <img id="legacyBannerPhoto" src="" alt="" hidden>
                    <span class="legacy-photo-fallback legacy-photo-fallback-lg" id="legacyBannerFallback"></span>
                </div>
                <h3 id="legacyModalName"></h3>
                <div class="legacy-banner-meta">
                    <span class="status-pill" id="legacyModalPosition"></span>
                    <span class="legacy-tenure" id="legacyModalTenure" hidden><i class="bi bi-calendar3" aria-hidden="true"></i> <span></span></span>
                </div>
                <p class="legacy-banner-description" id="legacyModalDescription"></p>
            </div>

            <!-- Add/Edit form mode -->
            <div class="legacy-modal-form" id="legacyModalForm" hidden>
                <h3 id="legacyFormTitle">Add SDRU In-Charge</h3>
                <form method="POST" action="index.php" enctype="multipart/form-data" id="legacyEntryForm">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="legacy_action" id="legacyFormAction" value="add">
                    <input type="hidden" name="legacy_id" id="legacyFormId" value="">

                    <div class="legacy-photo-field">
                        <div class="legacy-photo-preview" id="legacyPhotoPreview">
                            <i class="bi bi-person" aria-hidden="true"></i>
                        </div>
                        <div class="legacy-photo-controls">
                            <label class="btn btn-secondary legacy-photo-btn">
                                <i class="bi bi-image" aria-hidden="true"></i> Choose Photo
                                <input type="file" name="photo" id="legacyPhotoInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" hidden>
                            </label>
                            <small>JPG, PNG, or WebP · up to 5 MB · square works best</small>
                        </div>
                    </div>

                    <div class="legacy-fields">
                        <div class="settings-field">
                            <label for="legacyFullName">Full Name <span class="required">*</span></label>
                            <input type="text" id="legacyFullName" name="full_name" maxlength="150" required placeholder="e.g., Prof. Juan A. Dela Cruz">
                        </div>
                        <div class="legacy-field-row">
                            <div class="settings-field">
                                <label for="legacyPosition">Position <span class="required">*</span></label>
                                <input type="text" id="legacyPositionField" name="position" maxlength="100" required placeholder="e.g., SDRU Head">
                            </div>
                            <div class="settings-field">
                                <label for="legacyTenure">Tenure</label>
                                <input type="text" id="legacyTenureField" name="tenure" maxlength="50" placeholder="e.g., 2018 – 2023">
                            </div>
                        </div>
                        <div class="settings-field">
                            <label for="legacyDescription">Description</label>
                            <textarea id="legacyDescription" name="description" rows="4" placeholder="Share their contributions, achievements, and legacy..."></textarea>
                        </div>
                    </div>

                    <div class="legacy-form-footer">
                        <button type="button" class="btn btn-secondary" id="legacyFormCancel">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="legacyFormSubmit">
                            <i class="bi bi-check-lg" aria-hidden="true"></i> Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete confirmation popup -->
    <div class="legacy-modal-overlay" id="legacyDeleteOverlay" hidden>
        <div class="legacy-modal legacy-modal-sm" role="alertdialog" aria-modal="true" aria-labelledby="legacyDeleteTitle">
            <div class="legacy-delete-icon"><i class="bi bi-trash3" aria-hidden="true"></i></div>
            <h3 id="legacyDeleteTitle">Delete legacy entry?</h3>
            <p class="legacy-delete-text">You are about to remove <strong id="legacyDeleteName"></strong> from the Legacy of SDRU In-Charge page. This cannot be undone.</p>
            <div class="legacy-form-footer legacy-delete-footer">
                <button type="button" class="btn btn-secondary" id="legacyDeleteCancel">Cancel</button>
                <button type="button" class="btn legacy-btn-danger" id="legacyDeleteConfirm">
                    <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const entries = <?= json_encode(array_map(static function ($entry) {
            return [
                'id' => (int) $entry['legacy_id'],
                'full_name' => (string) $entry['full_name'],
                'position' => (string) $entry['position'],
                'tenure' => (string) ($entry['tenure'] ?? ''),
                'description' => (string) ($entry['description'] ?? ''),
                'photo' => legacy_photo_url($entry['photo_path'] ?? null),
            ];
        }, $entries), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const overlay = document.getElementById('legacyModalOverlay');
        const viewMode = document.getElementById('legacyModalView');
        const formMode = document.getElementById('legacyModalForm');

        const initialsOf = (name) => {
            const parts = String(name).trim().split(/\s+/);
            return ((parts[0]?.[0] || 'U') + (parts[1]?.[0] || '')).toUpperCase();
        };

        const openOverlay = () => {
            overlay.hidden = false;
            document.body.classList.add('legacy-modal-open');
        };

        const closeModal = () => {
            overlay.hidden = true;
            document.body.classList.remove('legacy-modal-open');
        };

        const showViewMode = () => {
            formMode.hidden = true;
            viewMode.hidden = false;
        };

        const showFormMode = () => {
            viewMode.hidden = true;
            formMode.hidden = false;
        };

        const setPreview = (src, name) => {
            const preview = document.getElementById('legacyPhotoPreview');
            preview.innerHTML = '';
            if (src) {
                const img = document.createElement('img');
                img.src = src;
                img.alt = '';
                preview.appendChild(img);
            } else {
                preview.innerHTML = '<i class="bi bi-person" aria-hidden="true"></i>';
                preview.dataset.label = name ? initialsOf(name) : '';
            }
        };

        const openView = (entry) => {
            const photo = document.getElementById('legacyBannerPhoto');
            const fallback = document.getElementById('legacyBannerFallback');
            if (entry.photo) {
                photo.src = entry.photo;
                photo.alt = 'Photo of ' + entry.full_name;
                photo.hidden = false;
                fallback.hidden = true;
            } else {
                photo.hidden = true;
                photo.removeAttribute('src');
                fallback.hidden = false;
                fallback.textContent = initialsOf(entry.full_name);
            }
            document.getElementById('legacyModalName').textContent = entry.full_name;
            document.getElementById('legacyModalPosition').textContent = entry.position;

            const tenure = document.getElementById('legacyModalTenure');
            if (entry.tenure) {
                tenure.querySelector('span').textContent = entry.tenure;
                tenure.hidden = false;
            } else {
                tenure.hidden = true;
            }
            document.getElementById('legacyModalDescription').innerHTML = entry.description
                ? entry.description.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')
                : '<em>No description provided.</em>';

            document.getElementById('legacyEditOpen').dataset.id = entry.id;
            showViewMode();
            openOverlay();
        };

        const openForm = (mode, entry = null) => {
            const form = document.getElementById('legacyEntryForm');
            form.reset();
            document.getElementById('legacyFormAction').value = mode;
            document.getElementById('legacyFormId').value = entry ? entry.id : '';
            document.getElementById('legacyFormTitle').textContent = mode === 'edit' ? 'Edit Legacy Entry' : 'Add SDRU In-Charge';
            document.getElementById('legacyFullName').value = entry ? entry.full_name : '';
            document.getElementById('legacyPositionField').value = entry ? entry.position : '';
            document.getElementById('legacyTenureField').value = entry ? entry.tenure : '';
            document.getElementById('legacyDescription').value = entry ? entry.description : '';
            setPreview(entry ? entry.photo : '', entry ? entry.full_name : '');
            showFormMode();
            openOverlay();
        };

        document.querySelectorAll('.legacy-add-open').forEach((button) => {
            button.addEventListener('click', () => openForm('add'));
        });

        document.querySelectorAll('.legacy-view-open').forEach((button) => {
            button.addEventListener('click', () => {
                const card = button.closest('.legacy-card');
                const entry = entries.find((item) => item.id === Number(card?.dataset.legacyId));
                if (entry) openView(entry);
            });
        });

        document.getElementById('legacyEditOpen').addEventListener('click', function () {
            const entry = entries.find((item) => item.id === Number(this.dataset.id));
            if (entry) openForm('edit', entry);
        });

        document.getElementById('legacyModalClose').addEventListener('click', closeModal);
        document.getElementById('legacyFormCancel').addEventListener('click', closeModal);
        overlay.addEventListener('mousedown', (event) => {
            if (event.target === overlay) closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (!overlay.hidden) closeModal();
            const deleteOverlayEl = document.getElementById('legacyDeleteOverlay');
            if (deleteOverlayEl && !deleteOverlayEl.hidden) {
                deleteOverlayEl.hidden = true;
                document.body.classList.remove('legacy-modal-open');
            }
        });

        document.getElementById('legacyPhotoInput').addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (event) => setPreview(event.target.result, '');
            reader.readAsDataURL(file);
        });

        /* Delete confirmation popup */
        const deleteOverlay = document.getElementById('legacyDeleteOverlay');
        let pendingDeleteForm = null;

        document.addEventListener('submit', (event) => {
            if (!(event.target instanceof HTMLFormElement)) return;
            const actionInput = event.target.querySelector('input[name="legacy_action"]');
            if (!actionInput || actionInput.value !== 'delete') return;

            event.preventDefault();
            pendingDeleteForm = event.target;
            document.getElementById('legacyDeleteName').textContent =
                event.target.dataset.entryName || 'this entry';
            deleteOverlay.hidden = false;
            document.body.classList.add('legacy-modal-open');
        }, true);

        const closeDeleteModal = () => {
            deleteOverlay.hidden = true;
            pendingDeleteForm = null;
            document.body.classList.remove('legacy-modal-open');
        };

        document.getElementById('legacyDeleteCancel').addEventListener('click', closeDeleteModal);
        document.getElementById('legacyDeleteConfirm').addEventListener('click', () => {
            if (!pendingDeleteForm) { closeDeleteModal(); return; }
            document.getElementById('legacyDeleteConfirm').disabled = true;
            pendingDeleteForm.submit();
        });
        deleteOverlay.addEventListener('mousedown', (event) => {
            if (event.target === deleteOverlay) closeDeleteModal();
        });
    })();
    </script>
    <script src="<?= h(app_url('web/views/layout/system.js')) ?>" defer></script>
</body>
</html>
