<?php
require_once __DIR__ . '/../../controllers/AccountController.php';

$controller = new AccountController();
if (($_GET['ajax'] ?? '') === '1') $controller->search();
$viewData = $controller->index();

$user = $viewData['user'];
$accounts = $viewData['accounts'];
$filters = $viewData['filters'];
$roles = $viewData['roles'];
$message = $viewData['message'];
$errors = $viewData['errors'];
$old = $viewData['old'];
$visibleTotal = count($accounts);
$visibleActive = count(array_filter($accounts, fn($account) => strtolower($account['status']) === 'active'));
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
    <link rel="stylesheet" href="../layout/accounts.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/system.css">
    <link rel="stylesheet" href="../layout/users.css">
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

        <section class="panel user-create-panel">
            <div class="panel-heading">
                <div class="panel-heading-icon"><i class="bi bi-person-plus" aria-hidden="true"></i></div>
                <div><h2>Create Account</h2><p>SDRU staff and coordinator access</p></div>
            </div>
            <form method="POST" action="index.php">
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
                    <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Create Account</button>
                </div>
            </form>
        </section>

        <section class="panel user-directory-panel">
            <div class="panel-heading directory-heading">
                <div class="panel-heading-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                <div><h2>User Directory</h2><p><?= (int) $visibleTotal ?> matching account<?= $visibleTotal === 1 ? '' : 's' ?></p></div>
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
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php if (empty($accounts)): ?>
                            <tr><td colspan="5">No accounts found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($accounts as $account): ?>
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
    <script>
    (() => {
        const form = document.getElementById('userFilters'), body = document.getElementById('userTableBody');
        if (!form || !body) return;
        const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
        let timer, request;
        async function load() {
            if (request) request.abort(); request = new AbortController();
            const params = new URLSearchParams(new FormData(form)); params.set('ajax', '1');
            const response = await fetch(`${form.action}?${params}`, {headers:{'X-Requested-With':'XMLHttpRequest'}, signal:request.signal});
            const data = await response.json(); if (!data.success) throw new Error(data.message);
            body.innerHTML = data.accounts.length ? data.accounts.map(a => `<tr><td><div class="user-identity"><span class="user-avatar">${esc((a.first_name[0]||'')+(a.last_name[0]||''))}</span><strong>${esc(a.first_name+' '+a.last_name)}</strong></div></td><td>${esc(a.email)}</td><td><span class="role-badge">${esc(a.role.replace(/[-_]/g,' '))}</span></td><td><span class="status">${esc(a.status)}</span></td><td>${esc(new Date(a.created_at.replace(' ','T')).toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}))}</td></tr>`).join('') : '<tr><td colspan="5">No accounts found.</td></tr>';
            Object.entries(data.summary).forEach(([key,value]) => { const node=document.querySelector(`[data-user-summary="${key}"]`); if(node) node.textContent=value; });
            params.delete('ajax'); history.replaceState(null,'',params.toString()?`index.php?${params}`:'index.php');
        }
        form.addEventListener('submit', e => {e.preventDefault(); load().catch(()=>{});});
        form.querySelectorAll('select').forEach(el => el.addEventListener('change', load));
        form.elements.search.addEventListener('input', () => {clearTimeout(timer); timer=setTimeout(load,400);});
        document.getElementById('resetUserFilters').addEventListener('click', () => {form.reset(); load();});
    })();
    </script>
</body>

</html>
