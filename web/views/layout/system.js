(() => {
    const PAGE_SIZE = 10;

    const statusKey = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-');

    const decorateStatuses = (root = document) => {
        root.querySelectorAll('.status, .status-pill').forEach((badge) => {
            badge.dataset.status = statusKey(badge.textContent);
        });
    };

    const pageButton = (label, page, current, disabled = false, ariaLabel = '') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `sicms-page-button${page === current ? ' active' : ''}`;
        button.textContent = label;
        button.dataset.page = String(page);
        button.disabled = disabled;
        if (ariaLabel) button.setAttribute('aria-label', ariaLabel);
        if (page === current) button.setAttribute('aria-current', 'page');
        return button;
    };

    const paginate = (table) => {
        if (table.dataset.noPagination === 'true' || !table.tBodies.length) return;
        const body = table.tBodies[0];
        const rows = Array.from(body.rows);
        const container = table.closest('.table-wrap') || table;
        let pager = container.nextElementSibling?.classList.contains('sicms-pagination')
            ? container.nextElementSibling
            : null;

        if (rows.length <= PAGE_SIZE) {
            rows.forEach((row) => { row.hidden = false; });
            pager?.remove();
            return;
        }

        const pages = Math.ceil(rows.length / PAGE_SIZE);
        let current = Math.min(Math.max(Number(table.dataset.page) || 1, 1), pages);
        table.dataset.page = String(current);
        rows.forEach((row, index) => {
            row.hidden = index < (current - 1) * PAGE_SIZE || index >= current * PAGE_SIZE;
        });

        if (!pager) {
            pager = document.createElement('nav');
            pager.className = 'sicms-pagination';
            pager.setAttribute('aria-label', 'Table pagination');
            container.insertAdjacentElement('afterend', pager);
            pager.addEventListener('click', (event) => {
                const button = event.target.closest('[data-page]');
                if (!button || button.disabled) return;
                table.dataset.page = button.dataset.page;
                paginate(table);
            });
        }

        pager.replaceChildren();
        const info = document.createElement('span');
        info.className = 'sicms-page-info';
        info.textContent = `Page ${current} of ${pages}`;
        pager.appendChild(info);

        const controls = document.createElement('div');
        controls.className = 'sicms-page-controls';
        controls.append(
            pageButton('First', 1, current, current === 1, 'First page'),
            pageButton('Previous', current - 1, current, current === 1, 'Previous page')
        );
        const start = Math.max(1, current - 2);
        const end = Math.min(pages, current + 2);
        for (let page = start; page <= end; page++) controls.appendChild(pageButton(String(page), page, current));
        controls.append(
            pageButton('Next', current + 1, current, current === pages, 'Next page'),
            pageButton('Last', pages, current, current === pages, 'Last page')
        );
        pager.appendChild(controls);
    };

    const initializeTable = (table) => {
        decorateStatuses(table);
        paginate(table);
        if (!table.tBodies.length || table.dataset.paginationObserved === 'true') return;
        table.dataset.paginationObserved = 'true';
        new MutationObserver(() => {
            table.dataset.page = '1';
            decorateStatuses(table);
            paginate(table);
        }).observe(table.tBodies[0], { childList: true });
    };

    document.querySelectorAll('table').forEach(initializeTable);
    decorateStatuses();

    const showAjaxNotice = (message, type = 'success') => {
        let notice = document.getElementById('sicms-ajax-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.id = 'sicms-ajax-notice';
            notice.setAttribute('role', 'status');
            notice.setAttribute('aria-live', 'polite');
            document.body.appendChild(notice);
        }
        notice.className = `sicms-ajax-notice ${type}`;
        notice.textContent = message;
        requestAnimationFrame(() => notice.classList.add('show'));
        clearTimeout(notice.hideTimer);
        notice.hideTimer = setTimeout(() => notice.classList.remove('show'), 3500);
    };

    const renderAjaxDocument = (html, url) => {
        history.replaceState({}, '', url);
        document.open();
        document.write(html);
        document.close();
    };

    const refreshAjaxPage = async (url = window.location.href) => {
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Unable to refresh the page.');
        renderAjaxDocument(await response.text(), response.url);
    };

    const applyNotificationsRead = (unread) => {
        document.querySelectorAll('details.dropdown, details.profile-dropdown').forEach((dropdown) => {
            if (!dropdown.querySelector('button[name="notification_action"][value="mark_all"]')) return;
            dropdown.querySelectorAll('.notification-badge, .badge').forEach((badge) => badge.remove());
            dropdown.querySelectorAll('.notification-item.unread').forEach((item) => item.classList.remove('unread'));
            const button = dropdown.querySelector('button[name="notification_action"][value="mark_all"]');
            if (button) button.disabled = true;
        });
    };

    document.addEventListener('submit', async (event) => {
        const submitter = event.submitter;
        if (!(event.target instanceof HTMLFormElement)) return;
        if (submitter?.name !== 'notification_action' || submitter?.value !== 'mark_all') return;

        event.preventDefault();
        const form = event.target;
        const data = new FormData(form);
        if (submitter.name && !data.has(submitter.name)) data.append(submitter.name, submitter.value);
        submitter.disabled = true;
        try {
            const markAllUrl = form.dataset.markAllUrl || form.getAttribute('action') || window.location.href;
            const response = await fetch(markAllUrl, {
                method: 'POST',
                body: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload?.success) throw new Error(payload?.message || 'Unable to mark notifications as read.');
            applyNotificationsRead(payload.unread ?? 0);
            showAjaxNotice('All notifications marked as read.');
        } catch (error) {
            showAjaxNotice(error.message || 'Unable to mark notifications as read.', 'error');
            submitter.disabled = false;
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        const method = String(form.method || 'get').toLowerCase();
        if (form.dataset.noAjax === 'true' || form.target || !['get', 'post'].includes(method)) return;

        event.preventDefault();
        const submitter = event.submitter;
        const confirmation = submitter?.dataset.confirm || form.dataset.confirm;
        if (confirmation && !window.confirm(confirmation)) return;

        const data = new FormData(form);
        if (submitter?.name && !data.has(submitter.name)) data.append(submitter.name, submitter.value);
        const controls = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        controls.forEach(control => { control.disabled = true; });
        form.classList.add('sicms-ajax-loading');

        try {
            let requestUrl = window.location.href;
            const rawAction = form.getAttribute('action');
            if (rawAction) requestUrl = new URL(rawAction, window.location.href).toString();
            const options = { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, text/html' } };
            if (method === 'post') {
                options.method = 'POST';
                options.body = data;
            } else {
                const url = new URL(requestUrl, window.location.href);
                url.search = new URLSearchParams(data).toString();
                requestUrl = url.toString();
            }
            const response = await fetch(requestUrl, options);
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const payload = await response.json();
                if (!response.ok || payload.success === false) throw new Error(payload.message || 'The request could not be completed.');
                showAjaxNotice(payload.message || 'Changes saved successfully.');
                if (payload.redirect) await refreshAjaxPage(payload.redirect);
                else await refreshAjaxPage();
                return;
            }

            const html = await response.text();
            if (!response.ok) {
                let message = 'The request could not be completed';
                if (response.status === 403) message = 'Your session expired or this page is outdated. Please refresh and try again.';
                else if (response.status === 404) message = 'The requested page was not found (' + requestUrl + '). Please refresh and try again.';
                else message = message + ' (HTTP ' + response.status + ').';
                console.error('AJAX form failure', requestUrl, response.status, html.slice(0, 500));
                throw new Error(message);
            }
            renderAjaxDocument(html, response.url);
        } catch (error) {
            showAjaxNotice(error.message || 'The request could not be completed.', 'error');
            controls.forEach(control => { control.disabled = false; });
            form.classList.remove('sicms-ajax-loading');
        }
    });

    const enableNotificationOpen = (config) => {
        if (!config || !config.api || !config.csrf) return;
        document.querySelectorAll('[data-notification-open]').forEach((container) => {
            if (container.dataset.notificationWired === 'true') return;
            container.dataset.notificationWired = 'true';
            container.addEventListener('click', (event) => {
                if (event.target.closest('button, form, .item-actions')) return;
                const item = event.target.closest('[data-notification-id]');
                if (!item) return;
                event.preventDefault();
                const destination = item.dataset.destination || '';
                const markReadUi = () => {
                    item.classList.remove('unread');
                    item.dataset.unread = '0';
                };
                const go = () => {
                    if (destination) window.location.href = destination;
                    else markReadUi();
                };
                if (item.dataset.unread !== '1') {
                    go();
                    return;
                }
                const body = new URLSearchParams();
                body.set('notification_action', 'mark_one');
                body.set('notification_id', item.dataset.notificationId);
                body.set('csrf_token', config.csrf);
                fetch(config.api, { method: 'POST', body, credentials: 'same-origin' })
                    .then((response) => response.json().catch(() => null))
                    .then((payload) => {
                        if (payload && typeof payload.unread === 'number') {
                            document.querySelectorAll('.notification-badge').forEach((badge) => {
                                if (payload.unread > 0) { badge.textContent = String(payload.unread); badge.hidden = false; }
                                else badge.hidden = true;
                            });
                        }
                        markReadUi();
                        go();
                    })
                    .catch(() => go());
            });
        });
    };

    enableNotificationOpen(window.SICMS_NOTIFY || null);
})();
