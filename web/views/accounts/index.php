<?php
require_once __DIR__ . '/../../controllers/AccountController.php';

$controller = new AccountController();
if (($_GET['ajax'] ?? '') === '1') $controller->search();
$viewData = $controller->index();

$user = $viewData['user'];
$accounts = $viewData['accounts'];
$complainants = $viewData['complainants'];
$filters = $viewData['filters'];
$roles = $viewData['roles'];
$message = $viewData['message'];
$errors = $viewData['errors'];
$old = $viewData['old'];
$visibleTotal = count($accounts) + count($complainants);
$visibleActive = count(array_filter(array_merge($accounts, $complainants), fn($account) => strtolower($account['status']) === 'active'));
$visibleInactive = $visibleTotal - $visibleActive;

$controller->clearFlash();

function h($value) {
    return htmlspecialchars((string) $value);
}

function selected($left, $right) {
    return (string) $left === (string) $right ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | SICMS</title>
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

        <section class="panel user-create-banner">
            <div>
                <h2>Create Account</h2>
                <p>SDRU staff and coordinator access</p>
            </div>
            <button class="btn btn-primary" id="openCreateAccount" type="button"><i class="bi bi-person-plus"></i> New Account</button>
        </section>

        <section class="panel user-directory-panel">
            <div class="panel-heading directory-heading">
                <div class="panel-heading-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                <div><h2>Staff Directory</h2><p><span id="staffCount"><?= (int) count($accounts) ?></span> matching account<?= count($accounts) === 1 ? '' : 's' ?></p></div>
            </div>
            <form class="user-filter-form" id="userFilters" method="GET" action="index.php">
                <div class="user-filter-grid">
                    <div class="field">
                        <label for="search">Search</label>
                        <input id="search" name="search" value="<?= h($filters['search']) ?>" placeholder="Name or email">
                    </div>
                    <div class="field">
                        <label for="filter_role">Role</label>
                        <select id="filter_role" name="role">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $role => $label): ?>
                                <option value="<?= h($role) ?>" <?= selected($filters['role'], $role) ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                            <option value="student" <?= selected($filters['role'], 'student') ?>>Student</option>
                            <option value="head-of-sdru" <?= selected($filters['role'], 'head-of-sdru') ?>>Head SDRU</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="active" <?= selected($filters['status'], 'active') ?>>Active</option>
                            <option value="inactive" <?= selected($filters['status'], 'inactive') ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Apply Filters</button>
                    <button class="btn btn-secondary" id="resetUserFilters" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                </div>
            </form>

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
                                <td><span class="role-badge"><?= h(ucwords(str_replace(['-', '_'], ' ', $account['role']))) ?></span></td>
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
        </section>

        <section class="panel user-directory-panel">
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
                                <td><span class="role-badge"><?= h(ucwords(str_replace(['-', '_'], ' ', $account['role']))) ?></span></td>
                                <td><span class="status"><?= h($account['status']) ?></span></td>
                                <td><?= h(date('M d, Y', strtotime($account['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
        </div>
    </div>

    <div class="account-modal-overlay" id="createAccountOverlay">
        <div class="account-modal" role="dialog" aria-modal="true" aria-labelledby="createAccountTitle">
            <div class="account-modal-header">
                <div>
                    <h3 id="createAccountTitle">New Account</h3>
                    <p>SDRU staff and coordinator access</p>
                </div>
                <button class="account-modal-close" id="closeCreateAccount" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="index.php" id="createAccountForm">
                <?= Security::csrfField() ?>
                <div class="user-form-grid">
                    <div class="field">
                        <label for="first_name">First Name</label>
                        <input id="first_name" name="first_name" autocomplete="given-name" value="<?= h($old['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label for="last_name">Last Name</label>
                        <input id="last_name" name="last_name" autocomplete="family-name" value="<?= h($old['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" value="<?= h($old['email'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role => $label): ?>
                                <option value="<?= h($role) ?>" <?= selected($old['role'] ?? '', $role) ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="password">Temporary Password</label>
                        <div class="password-wrapper">
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password" onclick="const p=this.previousElementSibling;p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=\'bi bi-eye\'></i>':'<i class=\'bi bi-eye-slash\'></i>';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-wrapper">
                            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password" onclick="const p=this.previousElementSibling;p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=\'bi bi-eye\'></i>':'<i class=\'bi bi-eye-slash\'></i>';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-secondary" type="button" id="cancelCreateAccount">Cancel</button>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (!empty($message)): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            Swal.fire({ icon: 'success', title: <?= json_encode((string) $message) ?>, timer: 2200, showConfirmButton: false });
        });
    </script>
    <?php elseif (!empty($errors)): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            Swal.fire({ icon: 'error', title: 'Please review the details', html: <?= json_encode(implode('<br>', array_map('htmlspecialchars', array_map('strval', $errors)))) ?> });
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
                if (!modalForm.checkValidity()) { modalForm.reportValidity(); return; }
                const name = `${modalForm.first_name.value} ${modalForm.last_name.value}`.trim();
                const role = modalForm.role.options[modalForm.role.selectedIndex]?.textContent || '';
                Swal.fire({
                    icon: 'question',
                    title: 'Create this account?',
                    html: `<strong>${String(name).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</strong><br>${String(modalForm.email.value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}<br><em>${String(role).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</em>`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, create it',
                    cancelButtonText: 'Go back',
                    confirmButtonColor: '#1a8c2b',
                    reverseButtons: true
                }).then(result => { if (result.isConfirmed) modalForm.submit(); });
            });
        }
    })();

    window.SICMS_ACCOUNTS = {
        csrf: <?= json_encode(Security::csrfToken()) ?>,
        viewerId: <?= (int) $user['account_id'] ?>
    };
    </script>
    <script>
    (() => {
        const form = document.getElementById('userFilters'), body = document.getElementById('userTableBody');
        if (!form || !body) return;
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
            const params = new URLSearchParams(new FormData(form)); params.set('ajax', '1');
            const response = await fetch(`${form.action}?${params}`, {headers:{'X-Requested-With':'XMLHttpRequest'}, signal:request.signal});
            const data = await response.json(); if (!data.success) throw new Error(data.message);
            fillTable(body, data.accounts, staffRowHtml, 'No accounts found.');
            fillTable(document.getElementById('complainantTableBody'), data.complainants, complainantRowHtml, 'No complainants found.');
            const staffCount = document.getElementById('staffCount'), complainantCount = document.getElementById('complainantCount');
            if (staffCount) staffCount.textContent = (data.accounts || []).length;
            if (complainantCount) complainantCount.textContent = (data.complainants || []).length;
            Object.entries(data.summary).forEach(([key,value]) => { const node=document.querySelector(`[data-user-summary="${key}"]`); if(node) node.textContent=value; });
            params.delete('ajax'); history.replaceState(null,'',params.toString()?`index.php?${params}`:'index.php');
        }
        form.addEventListener('submit', e => {e.preventDefault(); load().catch(()=>{});});
        form.querySelectorAll('select').forEach(el => el.addEventListener('change', load));
        form.elements.search.addEventListener('input', () => {clearTimeout(timer); timer=setTimeout(load,400);});
        document.getElementById('resetUserFilters').addEventListener('click', () => {form.reset(); load();});

        document.addEventListener('change', async e => {
            const box = e.target.closest('.sicms-status-toggle');
            if (!box || box.disabled) return;
            const enable = box.checked;
            const name = box.dataset.name || 'this account';
            const ask = await Swal.fire({
                icon: 'warning',
                title: enable ? 'Enable this account?' : 'Disable this account?',
                text: enable ? `${name} will regain access to SICMS.` : `${name} will no longer be able to sign in.`,
                showCancelButton: true,
                confirmButtonText: enable ? 'Yes, enable' : 'Yes, disable',
                cancelButtonText: 'Cancel',
                confirmButtonColor: enable ? '#1a8c2b' : '#c0392b',
                reverseButtons: true
            });
            if (!ask.isConfirmed) { box.checked = !enable; return; }
            try {
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
                Swal.fire({ icon: 'success', title: data.message, timer: 1600, showConfirmButton: false });
            } catch (err) {
                box.checked = !enable;
                Swal.fire({ icon: 'error', title: 'Update failed', text: String(err.message || err) });
            }
        });
    })();
    </script>
</body>

</html>
