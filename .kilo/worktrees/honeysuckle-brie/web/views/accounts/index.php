<?php
require_once __DIR__ . '/../../controllers/AccountController.php';

$controller = new AccountController();
if (($_GET['ajax'] ?? '') === '1') $controller->search();
$viewData = $controller->index();

$user = $viewData['user'];
$accounts = $viewData['accounts'];
$complainants = $viewData['complainants'];
$isHead = !empty($viewData['isHead']);
$filters = $viewData['filters'];
$roles = $viewData['roles'];
$message = $viewData['message'];
$errors = $viewData['errors'];
$fieldErrors = $viewData['fieldErrors'] ?? [];
$old = $viewData['old'];
$visibleTotal = count($accounts) + count($complainants);
$visibleActive = count(array_filter(array_merge($accounts, $complainants), fn($account) => strtolower((string) ($account['status'] ?? $account['account_status'] ?? 'inactive')) === 'active'));
$visibleInactive = $visibleTotal - $visibleActive;

$controller->clearFlash();

function h($value) {
    return htmlspecialchars((string) $value);
}

function field_error_html($fieldErrors, $field) {
    $message = $fieldErrors[$field] ?? '';
    return $message !== '' ? '<div class="field-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';
}

function selected($left, $right) {
    return (string) $left === (string) $right ? 'selected' : '';
}

function account_role_label($role) {
    $labels = ['coordinator' => 'Discipline Coordinator', 'reformation-coordinator' => 'Reformation Coordinator'];
    $key = strtolower(str_replace(['_', ' '], '-', (string) $role));
    return $labels[$key] ?? ucwords(str_replace(['-', '_'], ' ', (string) $role));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/accounts.css?v=2">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/users.css?v=3">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'User Management'; require __DIR__ . '/../layout/topbar.php'; ?>

    <main class="wrap users-page">
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

        <section class="users-summary" aria-label="Account summary">
            <?php foreach ([
                ['label' => 'Visible Accounts', 'value' => $visibleTotal, 'icon' => 'bi-people'],
                ['label' => 'Active Accounts', 'value' => $visibleActive, 'icon' => 'bi-person-check'],
                ['label' => 'Inactive Accounts', 'value' => $visibleInactive, 'icon' => 'bi-person-dash'],
            ] as $item): ?>
                <article class="user-summary-card">
                    <div><span><?= h($item['label']) ?></span><strong data-user-summary="<?= strtolower(strtok($item['label'], ' ')) ?>"><?= (int) $item['value'] ?></strong></div>
                    <i class="bi <?= h($item['icon']) ?>" aria-hidden="true"></i>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if ($isHead): ?><section class="panel user-directory-panel">
            <div class="panel-heading directory-heading">
                <div class="panel-heading-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                <div><h2>Staff Directory</h2><p><span id="staffCount"><?= (int) count($accounts) ?></span> matching account<?= count($accounts) === 1 ? '' : 's' ?></p></div>
                <div class="heading-tools">
                    <div class="heading-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="accountSearch" name="search" type="text" value="<?= h($filters['search']) ?>" placeholder="Search name or email">
                    </div>
                    <?php if ($isHead): ?><button class="btn btn-primary" id="openCreateAccount" type="button"><i class="bi bi-person-plus"></i> New Account</button><?php endif; ?>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php if (empty($accounts)): ?>
                            <tr><td colspan="6">No accounts found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($accounts as $account): ?>
                            <?php $isOwnAccount = (int) $account['account_id'] === (int) $user['account_id']; ?>
                            <tr>
                                <td>
                                    <div class="user-identity">
                                        <span class="user-avatar" aria-hidden="true"><?= h(strtoupper(substr($account['first_name'], 0, 1) . substr($account['last_name'], 0, 1))) ?></span>
                                        <strong><?= h(trim($account['first_name'] . ' ' . $account['last_name'])) ?></strong>
                                    </div>
                                </td>
                                <td><?= h($account['email']) ?></td>
                                <td><span class="role-badge"><?= h(account_role_label($account['role'])) ?></span></td>
                                <td><span class="status"><?= h($account['status']) ?></span></td>
                                <td><?= h(date('M d, Y', strtotime($account['created_at']))) ?></td>
                                <td>
                                    <label class="account-toggle" title="<?= $isOwnAccount ? 'You cannot change your own account' : '' ?>">
                                        <input type="checkbox" class="sicms-status-toggle"
                                            data-account="<?= (int) $account['account_id'] ?>"
                                            data-name="<?= h(trim($account['first_name'] . ' ' . $account['last_name'])) ?>"
                                            <?= strtolower($account['status']) === 'active' ? 'checked' : '' ?>
                                            <?= $isOwnAccount ? 'disabled' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section><?php endif; ?>

        <?php if ($isHead): ?><section class="panel user-directory-panel">
            <div class="panel-heading directory-heading">
                <div class="panel-heading-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></div>
                <div><h2>Complainants</h2><p><span id="complainantCount"><?= (int) count($complainants) ?></span> matching account<?= count($complainants) === 1 ? '' : 's' ?></p></div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody id="complainantTableBody">
                        <?php if (empty($complainants)): ?>
                            <tr><td colspan="5">No complainants found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($complainants as $account): ?>
                            <tr>
                                <td>
                                    <div class="user-identity">
                                        <span class="user-avatar" aria-hidden="true"><?= h(strtoupper(substr($account['first_name'], 0, 1) . substr($account['last_name'], 0, 1))) ?></span>
                                        <strong><?= h(trim($account['first_name'] . ' ' . $account['last_name'])) ?></strong>
                                    </div>
                                </td>
                                <td><?= h($account['email']) ?></td>
                                <td><span class="role-badge"><?= h(account_role_label($account['role'])) ?></span></td>
                                <td><span class="status"><?= h($account['status']) ?></span></td>
                                <td><?= h(date('M d, Y', strtotime($account['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section><?php endif; ?>
    </main>
        </div>
    </div>

    <?php if ($isHead): ?><div class="account-modal-overlay" id="createAccountOverlay">
        <div class="account-modal" role="dialog" aria-modal="true" aria-labelledby="createAccountTitle">
            <div class="account-modal-header">
                <div>
                    <h3 id="createAccountTitle">New Account</h3>
                    <p>SDRU staff, discipline coordinator, and reformation coordinator access</p>
                </div>
                <button class="account-modal-close" id="closeCreateAccount" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="index.php" id="createAccountForm" data-sicms-validate>
                <?= Security::csrfField() ?>
                <div class="user-form-grid">
                    <div class="field">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input id="first_name" name="first_name" autocomplete="given-name" value="<?= h($old['first_name'] ?? '') ?>" required>
                        <?= field_error_html($fieldErrors, 'first_name') ?>
                    </div>
                    <div class="field">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input id="last_name" name="last_name" autocomplete="family-name" value="<?= h($old['last_name'] ?? '') ?>" required>
                        <?= field_error_html($fieldErrors, 'last_name') ?>
                    </div>
                    <div class="field">
                        <label for="email">Email <span class="required">*</span></label>
                        <input id="email" name="email" type="email" autocomplete="email" value="<?= h($old['email'] ?? '') ?>" required>
                        <?= field_error_html($fieldErrors, 'email') ?>
                    </div>
                    <div class="field">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role => $label): ?>
                                <option value="<?= h($role) ?>" <?= selected($old['role'] ?? '', $role) ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error_html($fieldErrors, 'role') ?>
                    </div>
                    <div class="field">
                        <label for="password">Temporary Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password" onclick="const p=this.previousElementSibling;p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=\'bi bi-eye\'></i>':'<i class=\'bi bi-eye-slash\'></i>';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="password-hint" id="passwordStrengthHint"></div>
                        <?= field_error_html($fieldErrors, 'password') ?>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required data-sicms-match="#password">
                            <button type="button" class="password-toggle" aria-label="Show password" onclick="const p=this.previousElementSibling;p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=\'bi bi-eye\'></i>':'<i class=\'bi bi-eye-slash\'></i>';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')"><i class="bi bi-eye"></i></button>
                        </div>
                        <?= field_error_html($fieldErrors, 'confirm_password') ?>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-secondary" type="button" id="cancelCreateAccount">Cancel</button>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Create Account</button>
                </div>
            </form>
        </div>
    </div><?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (!empty($message)): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.DARISAlert?.toast('success', 'Account Saved', <?= json_encode((string) $message) ?>);
        });
    </script>
    <?php elseif (!empty($errors)): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.DARISAlert?.toast('error', 'Please Review the Details', <?= json_encode(implode(' ', array_map('strval', $errors))) ?>, { timer: 7000 });
        });
    </script>
    <?php endif; ?>
    <script>
    (() => {
        const overlay = document.getElementById('createAccountOverlay');
        const openBtn = document.getElementById('openCreateAccount');
        const modalForm = document.getElementById('createAccountForm');
        if (overlay && openBtn && modalForm) {
            const setOpen = open => overlay.classList.toggle('open', open);
            openBtn.addEventListener('click', () => {
                setOpen(true);
                modalForm.querySelector('input')?.focus();
            });
            document.getElementById('closeCreateAccount')?.addEventListener('click', () => setOpen(false));
            document.getElementById('cancelCreateAccount')?.addEventListener('click', () => setOpen(false));
            overlay.addEventListener('mousedown', e => { if (e.target === overlay) setOpen(false); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });

            modalForm.addEventListener('submit', e => {
                e.preventDefault();
                const invalid = (window.SICMSValidation && !SICMSValidation.run(modalForm)) || !modalForm.checkValidity();
                if (invalid) {
                    const firstInvalid = modalForm.querySelector(':invalid');
                    window.DARISAlert?.toast('warning', 'Required Information Missing', 'Please complete the highlighted account information before continuing.');
                    firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    window.setTimeout(() => firstInvalid?.focus(), 180);
                    return;
                }
                const name = `${modalForm.first_name.value} ${modalForm.last_name.value}`.trim();
                const role = modalForm.role.options[modalForm.role.selectedIndex]?.textContent || '';
                overlay.style.zIndex = '1';
                (window.DARISAlert?.fire({
                    icon: 'question',
                    title: 'Create this account?',
                    html: `<strong>${String(name).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</strong><br>${String(modalForm.email.value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}<br><em>${String(role).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</em>`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, create it',
                    cancelButtonText: 'Go back',
                    confirmButtonColor: '#1a8c2b',
                    reverseButtons: true
                }) || Swal.fire({ icon: 'question', title: 'Create this account?', showCancelButton: true })).then(result => {
                    overlay.style.zIndex = '';
                    if (!result.isConfirmed) return;
                    const submitButton = modalForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="sicms-processing-spinner" aria-hidden="true"></span> Creating account...';
                    }
                    window.DARISAlert?.processing('Creating Account...');
                    modalForm.submit();
                });
            });

            const passwordInput = modalForm.querySelector('#password');
            const strengthHint = document.getElementById('passwordStrengthHint');
            if (passwordInput && strengthHint) {
                const checkStrength = () => {
                    const value = passwordInput.value;
                    const missing = [];
                    if (value.length < 12) missing.push('at least 12 characters');
                    if (!/[A-Z]/.test(value)) missing.push('an uppercase letter');
                    if (!/[a-z]/.test(value)) missing.push('a lowercase letter');
                    if (!/\d/.test(value)) missing.push('a number');
                    if (!/[^A-Za-z0-9]/.test(value)) missing.push('a symbol');
                    if (missing.length === 0) { strengthHint.textContent = ''; strengthHint.className = 'password-hint ok'; return; }
                    strengthHint.textContent = 'Password needs ' + missing.join(', ') + '.';
                    strengthHint.className = 'password-hint';
                };
                passwordInput.addEventListener('input', checkStrength);
            }
        }
    })();

    window.SICMS_ACCOUNTS = {
        csrf: <?= json_encode(Security::csrfToken()) ?>,
        viewerId: <?= (int) $user['account_id'] ?>
    };
    </script>
    <script>
    (() => {
        const input = document.getElementById('accountSearch');
        if (!input) return;
        const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
        const staffRowHtml = a => {
            const self = Number(a.account_id) === Number(window.SICMS_ACCOUNTS.viewerId);
            const active = String(a.status).toLowerCase() === 'active';
            return `<tr><td><div class="user-identity"><span class="user-avatar">${esc((a.first_name[0]||'')+(a.last_name[0]||''))}</span><strong>${esc(a.first_name+' '+a.last_name)}</strong></div></td><td>${esc(a.email)}</td><td><span class="role-badge">${esc(a.role.replace(/[-_]/g,' '))}</span></td><td><span class="status">${esc(a.status)}</span></td><td>${esc(new Date(a.created_at.replace(' ','T')).toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}))}</td><td><label class="account-toggle" ${self?'title="You cannot change your own account"':''}><input type="checkbox" class="sicms-status-toggle" data-account="${a.account_id}" data-name="${esc(a.first_name+' '+a.last_name)}" ${active?'checked':''} ${self?'disabled':''}><span class="slider"></span></label></td></tr>`;
        };
        const complainantRowHtml = a => `<tr><td><div class="user-identity"><span class="user-avatar">${esc((a.first_name[0]||'')+(a.last_name[0]||''))}</span><strong>${esc(a.first_name+' '+a.last_name)}</strong></div></td><td>${esc(a.email)}</td><td><span class="role-badge">${esc(a.role.replace(/[-_]/g,' '))}</span></td><td><span class="status">${esc(a.status)}</span></td><td>${esc(new Date(a.created_at.replace(' ','T')).toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}))}</td></tr>`;
        const fillTable = (node, rows, template, emptyText) => { if (node) node.innerHTML = (rows && rows.length) ? rows.map(template).join('') : `<tr><td colspan="${template === staffRowHtml ? 6 : 5}">${emptyText}</td></tr>`; };
        let timer, request;
        async function load() {
            if (request) request.abort(); request = new AbortController();
            const params = new URLSearchParams({ search: input.value.trim() }); params.set('ajax', '1');
            const response = await fetch(`index.php?${params}`, {headers:{'X-Requested-With':'XMLHttpRequest'}, signal:request.signal});
            const data = await response.json(); if (!data.success) throw new Error(data.message);
            fillTable(document.getElementById('userTableBody'), data.accounts, staffRowHtml, 'No accounts found.');
            fillTable(document.getElementById('complainantTableBody'), data.complainants, complainantRowHtml, 'No complainants found.');
            const staffCount = document.getElementById('staffCount'), complainantCount = document.getElementById('complainantCount');
            if (staffCount) staffCount.textContent = (data.accounts || []).length;
            if (complainantCount) complainantCount.textContent = (data.complainants || []).length;
            Object.entries(data.summary).forEach(([key,value]) => { const node=document.querySelector(`[data-user-summary="${key}"]`); if(node) node.textContent=value; });
            const q = input.value.trim();
            history.replaceState(null, '', q ? `index.php?search=${encodeURIComponent(q)}` : 'index.php');
        }
        input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 400); });
    })();
    </script>
    <script>
    (() => {
        document.addEventListener('change', async e => {
            const box = e.target.closest('.sicms-status-toggle');
            if (!box || box.disabled) return;
            const enable = box.checked;
            const name = box.dataset.name || 'this account';
            const confirmOptions = {
                icon: 'warning',
                title: enable ? `Reactivate ${name}?` : `Deactivate ${name}?`,
                text: enable ? `${name} will regain access to DARIS.` : `${name} will no longer be able to access DARIS until the account is reactivated.`,
                showCancelButton: true,
                confirmButtonText: enable ? 'Reactivate User' : 'Deactivate User',
                cancelButtonText: 'Cancel',
                confirmButtonColor: enable ? '#1a8c2b' : '#c0392b',
                reverseButtons: true
            };
            const ask = await (window.DARISAlert?.fire(confirmOptions) || Swal.fire(confirmOptions));
            if (!ask.isConfirmed) { box.checked = !enable; return; }
            try {
                box.disabled = true;
                window.DARISAlert?.processing(enable ? 'Reactivating User...' : 'Deactivating User...');
                const fd = new FormData();
                fd.set('action', 'toggle_status');
                fd.set('ajax', '1');
                fd.set('csrf_token', window.SICMS_ACCOUNTS.csrf);
                fd.set('account_id', box.dataset.account);
                fd.set('status', enable ? 'active' : 'inactive');
                const res = await fetch('index.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Unable to update account.');
                const pill = box.closest('tr')?.querySelector('.status');
                if (pill) { pill.textContent = data.status; pill.dataset.status = String(data.status).toLowerCase(); }
                Object.entries(data.summary || {}).forEach(([key,value]) => { const node=document.querySelector(`[data-user-summary="${key}"]`); if(node) node.textContent=value; });
                window.DARISAlert?.toast('success', enable ? 'Account Reactivated' : 'Account Deactivated', enable ? `${name} can now access DARIS.` : `${name}'s account has been deactivated.`);
            } catch (err) {
                box.checked = !enable;
                window.DARISAlert?.toast('error', 'Unable to Update User', 'The account status was not changed. Please try again.', { timer: 7000 });
            } finally {
                box.disabled = false;
            }
        });
    })();
    </script>
</body>

</html>
