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

    const SICMS_PHONE_RE = /^[0-9+()\-\s.]{7,20}$/;

    const cleanLabelText = (raw) => String(raw || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s*[·*:]\s*$/g, '')
        .replace(/\s*(\(required\)|\(optional\))\s*$/i, '')
        .replace(/\s+/g, ' ')
        .trim();

    const sentenceCase = (str) => String(str || '').replace(/^./, (char) => char.toUpperCase());

    const lowerFirst = (str) => String(str || '').replace(/^./, (char) => char.toLowerCase());

    const withArticle = (label) => /^(your|my|their|our|his|her|its)\b/i.test(label) ? label : `the ${label}`;

    const fieldLabel = (control) => {
        const field = control.closest('.field') || control.closest('.form-group') || control.closest('.settings-field');
        if (field) {
            const label = field.querySelector('label');
            const inside = label && cleanLabelText(label.textContent);
            if (inside) return inside;
        }
        if (control.id) {
            const label = document.querySelector('label[for="' + CSS.escape(control.id) + '"]');
            const linked = label && cleanLabelText(label.textContent);
            if (linked) return linked;
        }
        const fromData = cleanLabelText(control.dataset.label);
        if (fromData) return fromData;
        const fromAria = cleanLabelText(control.getAttribute('aria-label'));
        if (fromAria) return fromAria;
        const fromPlaceholder = cleanLabelText(control.getAttribute('placeholder'));
        if (fromPlaceholder) return fromPlaceholder;
        const fromName = control.getAttribute('name');
        if (fromName) return sentenceCase(fromName.replace(/[_-]+/g, ' '));
        return 'this field';
    };

    const renderable = (control) => {
        if (control.disabled || control.type === 'hidden') return false;
        if (control.closest('[hidden], .d-none, .d-none-inverse, .hidden')) return false;
        return true;
    };

    const hasCustomRule = (control) => {
        const dataset = control.dataset;
        return dataset.sicmsFuture !== undefined
            || dataset.sicmsPast !== undefined
            || dataset.sicmsPhone !== undefined
            || dataset.sicmsMatch !== undefined
            || dataset.sicmsSizeMb !== undefined
            || dataset.sicmsAcceptExt !== undefined;
    };

    const todayLocal = () => {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    };

    const constraintMessage = (control) => {
        const validity = control.validity;
        const label = sentenceCase(String(fieldLabel(control)).toLowerCase());

        if (validity.valueMissing) {
            const question = /[?]$/.test(label) || /^(do|are|was|were|is|has|have|did|does|should|would|can|will)\s/i.test(label);
            if (question) return 'Please make a selection.';
            if (control.type === 'file') return `Please choose ${withArticle(lowerFirst(label))}.`;
            if (control.type === 'checkbox') return `Please check ${withArticle(lowerFirst(label))}.`;
            if (control.type === 'radio') return `Please select ${withArticle(lowerFirst(label))}.`;
            if (control instanceof HTMLSelectElement) return `Please select ${withArticle(lowerFirst(label))}.`;
            return `Please enter ${withArticle(lowerFirst(label))}.`;
        }
        if (validity.typeMismatch) {
            if (control.type === 'email') return 'Please enter a valid email address.';
            if (control.type === 'url') return 'Please enter a valid URL.';
            return `Please enter a valid ${lowerFirst(label)}.`;
        }
        if (validity.tooShort) {
            return `Please enter at least ${control.minLength} characters for the ${lowerFirst(label)}.`;
        }
        if (validity.tooLong) {
            return `Please enter no more than ${control.maxLength} characters for the ${lowerFirst(label)}.`;
        }
        if (validity.rangeUnderflow) {
            if (control.type === 'date' || control.type === 'datetime-local' || control.type === 'month') {
                return `The ${lowerFirst(label)} cannot be earlier than ${control.min}.`;
            }
            return `Please enter a value of at least ${control.min} for the ${lowerFirst(label)}.`;
        }
        if (validity.rangeOverflow) {
            if ((control.type === 'date' || control.type === 'datetime-local' || control.type === 'month')
                && control.max === todayLocal()) {
                return `The ${lowerFirst(label)} cannot be in the future.`;
            }
            if (control.type === 'date' || control.type === 'datetime-local' || control.type === 'month') {
                return `The ${lowerFirst(label)} cannot be later than ${control.max}.`;
            }
            return `Please enter a value of at most ${control.max} for the ${lowerFirst(label)}.`;
        }
        if (validity.stepMismatch) {
            return `Please enter a valid value for the ${lowerFirst(label)}.`;
        }
        if (validity.patternMismatch) {
            return control.title || `Please enter a valid value for the ${lowerFirst(label)}.`;
        }
        if (validity.badInput) {
            return `Please enter a valid value for the ${lowerFirst(label)}.`;
        }
        return '';
    };

    const validationError = (control, message) => {
        const field = control.closest('.field') || control.closest('.form-group') || control.closest('.settings-field');
        if (field) {
            let box = field.querySelector('.field-error') || field.querySelector('.error-message');
            if (!box) {
                box = document.createElement('div');
                box.className = 'field-error';
                field.appendChild(box);
            }
            box.textContent = message;
            field.classList.add('is-invalid');
            control.setAttribute('aria-invalid', 'true');
        } else {
            let box = control.nextElementSibling;
            if (!box || !box.classList.contains('field-error')) {
                box = document.createElement('div');
                box.className = 'field-error';
                control.parentNode.insertBefore(box, control.nextSibling);
            }
            box.textContent = message;
            control.classList.add('is-invalid');
        }
        return false;
    };

    const clearValidationError = (control) => {
        const field = control.closest('.field') || control.closest('.form-group') || control.closest('.settings-field');
        const box = field ? (field.querySelector('.field-error') || field.querySelector('.error-message')) : null;
        if (box) box.remove();
        if (field) field.classList.remove('is-invalid');
        control.classList.remove('is-invalid');
        control.removeAttribute('aria-invalid');
    };

    const validateRuleControl = (control) => {
        clearValidationError(control);
        if (!renderable(control)) return true;
        const rules = control.dataset;
        const value = String(control.value || '').trim();
        const label = sentenceCase(String(fieldLabel(control)).toLowerCase());

        if (rules.sicmsFuture === '0' && value) {
            const parsed = new Date(value);
            if (!isNaN(parsed.getTime()) && parsed > new Date()) {
                return validationError(control, `The ${lowerFirst(label)} cannot be in the future.`);
            }
        }
        if (rules.sicmsPast === '0' && value) {
            const parsed = new Date(value);
            if (!isNaN(parsed.getTime()) && parsed < new Date()) {
                return validationError(control, `The ${lowerFirst(label)} cannot be in the past.`);
            }
        }
        if (rules.sicmsPhone !== undefined && value && !SICMS_PHONE_RE.test(value)) {
            return validationError(control, 'Please enter a valid phone number (digits, spaces, +, -, or parentheses).');
        }
        if (rules.sicmsMatch && value) {
            const target = document.querySelector(rules.sicmsMatch);
            if (target && target.value && value !== target.value) {
                const targetLabel = sentenceCase(String(fieldLabel(target)).toLowerCase());
                return validationError(control, `Please make the ${lowerFirst(label)} match the ${lowerFirst(targetLabel)}.`);
            }
        }
        if (control.type === 'file' && control.files && control.files.length) {
            const maxMb = Number(rules.sicmsSizeMb) || 0;
            const accepted = String(rules.sicmsAcceptExt || '')
                .split(',')
                .map((ext) => ext.trim().toLowerCase().replace(/^\./, ''))
                .filter(Boolean);
            for (const file of control.files) {
                if (maxMb && file.size > maxMb * 1024 * 1024) {
                    return validationError(control, `${file.name} is larger than ${maxMb}MB. Please choose a smaller file.`);
                }
                if (accepted.length && !accepted.includes((file.name.split('.').pop() || '').toLowerCase())) {
                    return validationError(control, `${file.name} has an invalid file type. Allowed: ${String(rules.sicmsAcceptExt || '')}`);
                }
            }
        }
        if (!control.willValidate || control.validity.valid) return true;
        const message = constraintMessage(control);
        return message ? validationError(control, message) : true;
    };

    const validateRuleForm = (form) => {
        let firstInvalid = null;
        let valid = true;

        form.querySelectorAll('input, select, textarea').forEach((control) => {
            if (!renderable(control) || (!hasCustomRule(control) && !control.willValidate)) return;
            if (!validateRuleControl(control)) {
                valid = false;
                if (!firstInvalid) firstInvalid = control;
            }
        });

        const fromName = form.dataset.sicmsDatefrom;
        const toName = form.dataset.sicmsDateto;
        if (fromName && toName) {
            const fromEl = form.elements.namedItem(fromName);
            const toEl = form.elements.namedItem(toName);
            const fromValue = fromEl ? String(fromEl.value || '').trim() : '';
            const toValue = toEl ? String(toEl.value || '').trim() : '';
            if (fromValue && toValue) {
                const fromDate = new Date(fromValue);
                const toDate = new Date(toValue);
                if (!isNaN(fromDate.getTime()) && !isNaN(toDate.getTime()) && fromDate.getTime() > toDate.getTime()) {
                    const fromLabel = sentenceCase(String(fieldLabel(fromEl)).toLowerCase());
                    const toLabel = sentenceCase(String(fieldLabel(toEl)).toLowerCase());
                    validationError(toEl, `The ${lowerFirst(toLabel)} cannot be earlier than the ${lowerFirst(fromLabel)}.`);
                    valid = false;
                    if (!firstInvalid) firstInvalid = toEl;
                }
            }
        }

        if (!valid) form.dataset.sicmsTouched = 'true';
        return { valid, firstInvalid };
    };

    const wireValidation = (form) => {
        if (form.dataset.sicmsValidationWired === 'true') return;
        form.dataset.sicmsValidationWired = 'true';

        form.setAttribute('novalidate', 'novalidate');
        form.addEventListener('invalid', (event) => event.preventDefault(), true);

        const liveValidate = (event) => {
            const control = event.target;
            if (!(control instanceof HTMLInputElement)
                && !(control instanceof HTMLSelectElement)
                && !(control instanceof HTMLTextAreaElement)) return;
            if (!renderable(control)) return;
            const touched = form.dataset.sicmsTouched === 'true'
                || control.classList.contains('is-invalid')
                || hasCustomRule(control);
            if (touched) validateRuleControl(control);
        };
        form.addEventListener('input', liveValidate);
        form.addEventListener('change', liveValidate);
        form.addEventListener('submit', (event) => {
            const result = validateRuleForm(form);
            if (!result.valid) {
                event.preventDefault();
                if (result.firstInvalid) result.firstInvalid.focus();
                if (event.submitter) event.submitter.disabled = false;
            }
        });
    };

    document.querySelectorAll('form[data-sicms-validate]').forEach(wireValidation);
    document.addEventListener('daris:ajax-success', () => {
        document.querySelectorAll('form[data-sicms-validate]').forEach(wireValidation);
    });

    window.SICMSValidation = {
        run: (form) => {
            const result = validateRuleForm(form);
            if (!result.valid && result.firstInvalid) result.firstInvalid.focus();
            return result.valid;
        },
        valid: (control) => validateRuleControl(control),
        error: validationError,
        clear: clearValidationError,
    };

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

    const ensureSweetAlert = async () => {
        if (window.Swal) return window.Swal;
        await new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-sicms-swal]');
            if (existing) {
                existing.addEventListener('load', resolve, { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            script.dataset.sicmsSwal = 'true';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
        return window.Swal;
    };

    const sweetAlertDefaults = {
        width: 520,
        position: 'center',
        backdrop: false,
        buttonsStyling: false,
        reverseButtons: true,
        customClass: {
            popup: 'dar-is-swal',
            title: 'dar-is-swal-title',
            htmlContainer: 'dar-is-swal-copy',
            actions: 'dar-is-swal-actions',
            confirmButton: 'dar-is-swal-button dar-is-swal-confirm',
            cancelButton: 'dar-is-swal-button dar-is-swal-cancel',
            denyButton: 'dar-is-swal-button dar-is-swal-danger',
            validationMessage: 'dar-is-swal-validation',
        },
    };

    const fireDarAlert = async (options = {}) => {
        const Swal = await ensureSweetAlert();
        if (!Swal) throw new Error('SweetAlert2 did not load.');
        return Swal.fire({ ...sweetAlertDefaults, ...options,
            position: 'center',
            backdrop: false,
            customClass: { ...sweetAlertDefaults.customClass, ...(options.customClass || {}) }
        });
    };

    const confirmImportantAction = async ({ title, text, confirmText = 'Yes, continue', icon = 'question' }) => {
        try {
            const Swal = await ensureSweetAlert();
            if (!Swal) throw new Error('SweetAlert2 did not load.');
            const result = await fireDarAlert({
                icon,
                title: title || 'Confirm action',
                text: text || '',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                allowOutsideClick: false,
            });
            return result.isConfirmed;
        } catch (error) {
            showAjaxNotice('Unable to open the confirmation dialog. Please try again.', 'error');
            return false;
        }
    };

    window.SICMSConfirm = confirmImportantAction;
    window.DARISAlert = {
        fire: fireDarAlert,
        confirm: confirmImportantAction,
        toast: async (icon, title, text, options = {}) => {
            const Swal = await ensureSweetAlert();
            if (!Swal) throw new Error('SweetAlert2 did not load.');
            return Swal.fire({
                toast: true,
                position: 'top',
                icon,
                title,
                text,
                timer: icon === 'error' ? 6500 : 4800,
                timerProgressBar: true,
                showConfirmButton: false,
                showCloseButton: true,
                backdrop: false,
                allowOutsideClick: true,
                allowEscapeKey: true,
                customClass: {
                    popup: 'dar-is-toast',
                    title: 'dar-is-toast-title',
                    htmlContainer: 'dar-is-toast-copy',
                    timerProgressBar: 'dar-is-toast-progress',
                },
                ...options,
                position: 'top',
                backdrop: false,
            });
        },
        processing: (title, text = 'Please wait while DARIS completes this action.') => fireDarAlert({
            title,
            text,
            allowEscapeKey: false,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => window.Swal?.showLoading(),
        }),
        success: (title, text, options = {}) => fireDarAlert({ icon: 'success', title, text, confirmButtonText: 'Done', ...options }),
        error: (title, text, options = {}) => fireDarAlert({ icon: 'error', title, text, confirmButtonText: 'Try Again', ...options }),
        closeProcessing: () => window.Swal?.close(),
    };

    try {
        const pendingToast = JSON.parse(sessionStorage.getItem('daris-pending-toast') || 'null');
        if (pendingToast?.title) {
            sessionStorage.removeItem('daris-pending-toast');
            window.setTimeout(() => window.DARISAlert.toast(pendingToast.icon || 'success', pendingToast.title, pendingToast.text || ''), 80);
        }
    } catch (error) {
        sessionStorage.removeItem('daris-pending-toast');
    }

    const setProcessingState = (form, submitter) => {
        const label = submitter?.dataset.sicmsProcessingLabel || form.dataset.sicmsProcessingLabel;
        if (!label || !submitter || submitter.dataset.sicmsProcessing === 'true') return () => {};

        submitter.dataset.sicmsProcessing = 'true';
        if (submitter instanceof HTMLInputElement) {
            const originalValue = submitter.value;
            submitter.value = label;
            return () => {
                submitter.value = originalValue;
                delete submitter.dataset.sicmsProcessing;
            };
        }

        const originalHtml = submitter.innerHTML;
        submitter.innerHTML = `<span class="sicms-processing-spinner" aria-hidden="true"></span> ${label}`;
        submitter.setAttribute('aria-busy', 'true');
        return () => {
            submitter.innerHTML = originalHtml;
            submitter.removeAttribute('aria-busy');
            delete submitter.dataset.sicmsProcessing;
        };
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

    const refreshAjaxTargets = (html, selectorList) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const selectors = String(selectorList || '').split(',').map((selector) => selector.trim()).filter(Boolean);
        if (!selectors.length) return false;
        const replacements = [];
        selectors.forEach((selector) => {
            const current = document.querySelector(selector);
            const incoming = parsed.querySelector(selector);
            if (current && incoming) replacements.push([current, incoming]);
        });
        if (replacements.length !== selectors.length) return false;
        replacements.forEach(([current, incoming]) => current.replaceWith(incoming));
        document.querySelectorAll('form[data-sicms-validate]').forEach(wireValidation);
        return true;
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
            document.dispatchEvent(new CustomEvent('daris:notifications-read', { detail: { unread: payload.unread ?? 0, all: true } }));
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
        if (form.target || !['get', 'post'].includes(method)) return;

        const submitter = event.submitter;
        const confirmation = submitter?.dataset.confirm || form.dataset.confirm;
        const bypassAjax = form.dataset.noAjax === 'true';
        if (!confirmation && bypassAjax) return;

        event.preventDefault();
        if (confirmation) {
            const confirmed = await confirmImportantAction({
                title: submitter?.dataset.confirmTitle || form.dataset.confirmTitle || 'Confirm action',
                text: confirmation,
                confirmText: submitter?.dataset.confirmButton || form.dataset.confirmButton || 'Yes, continue',
                icon: submitter?.dataset.confirmIcon || form.dataset.confirmIcon || 'question',
            });
            if (!confirmed) return;
        }

        if (bypassAjax) {
            if (submitter?.name) {
                const submittedValue = document.createElement('input');
                submittedValue.type = 'hidden';
                submittedValue.name = submitter.name;
                submittedValue.value = submitter.value;
                form.appendChild(submittedValue);
            }
            Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]')).forEach(control => { control.disabled = true; });
            setProcessingState(form, submitter);
            form.submit();
            return;
        }

        const data = new FormData(form);
        if (submitter?.name && !data.has(submitter.name)) data.append(submitter.name, submitter.value);
        const controls = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        controls.forEach(control => { control.disabled = true; });
        form.classList.add('sicms-ajax-loading');
        const restoreProcessingState = setProcessingState(form, submitter);
        const processingLabel = submitter?.dataset.sicmsProcessingLabel || form.dataset.sicmsProcessingLabel;
        if (processingLabel && (submitter?.dataset.sicmsProcessingModal === 'true' || form.dataset.sicmsProcessingModal === 'true')) {
            window.DARISAlert?.processing(processingLabel);
        }

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
                window.DARISAlert?.toast('success', payload.title || 'Changes Saved', payload.message || 'Your changes were saved successfully.');
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
            let successTitle = '';
            let successMessage = '';
            try {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const flash = parsed.querySelector('.alert-success, .alert-error, .alert-danger');
                if (flash?.textContent.trim()) {
                    const action = submitter?.value || '';
                    const successTitles = {
                        classify: 'Classification Saved', reject: 'Complaint Rejected', return: 'Returned for Revision',
                        assign: 'Coordinator Assigned', assign_reformation: 'Coordinator Assigned', resolve: 'Case Resolved',
                        reopen: 'Case Reopened', unarchive: 'Case Restored', reformation_activity: 'Reformation Update Saved',
                        reformation_report_upload: 'Report Uploaded', case_update: 'Case Update Added',
                        reformation_completed: 'Reformation Completed', proceed_counter_statement: 'Investigation Started',
                        request_counter_revision: 'Counter-Statement Returned', forward_counter_statement: 'Counter-Statement Forwarded',
                        upload_counter_evidence: 'Evidence Uploaded', remove_counter_evidence: 'Evidence Removed',
                        cancel: 'Hearing Cancelled', complete: 'Hearing Completed',
                    };
                    const isError = flash.matches('.alert-error, .alert-danger');
                    successTitle = successTitles[action] || (isError ? 'Unable to Complete Action' : 'Changes Saved');
                    successMessage = flash.textContent.trim();
                    if (!form.dataset.ajaxTarget) {
                        sessionStorage.setItem('daris-pending-toast', JSON.stringify({
                            icon: isError ? 'error' : 'success',
                            title: successTitle,
                            text: successMessage,
                        }));
                    }
                }
            } catch (error) {}
            const returnedDocument = new DOMParser().parseFromString(html, 'text/html');
            const returnedError = returnedDocument.querySelector('.alert-error, .alert-danger');
            if (returnedError?.textContent.trim()) {
                throw new Error(returnedError.textContent.trim());
            }
            if (form.dataset.ajaxTarget && refreshAjaxTargets(html, form.dataset.ajaxTarget)) {
                if (form.dataset.ajaxReset === 'true') {
                    form.reset();
                    form.querySelectorAll('[name="update_type"], [name="respondent_type"], [name="classification"]').forEach(control => {
                        control.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
                controls.forEach(control => { control.disabled = false; });
                form.classList.remove('sicms-ajax-loading');
                restoreProcessingState();
                if (form.dataset.ajaxClose) {
                    const overlay = document.querySelector(form.dataset.ajaxClose);
                    overlay?.classList.remove('show', 'open');
                    if (overlay) overlay.hidden = true;
                }
                window.DARISAlert?.closeProcessing?.();
                window.DARISAlert?.toast('success', successTitle || (submitter?.value === 'case_update' ? 'Case Update Added' : 'Changes Saved'), successMessage || (submitter?.value === 'case_update' ? 'The new update is now shown in Case Updates.' : 'The affected section has been updated.'));
                document.dispatchEvent(new CustomEvent('daris:ajax-success', { detail: { action: submitter?.value || '', form } }));
                return;
            }
            renderAjaxDocument(html, response.url);
        } catch (error) {
            window.DARISAlert?.closeProcessing?.();
            window.DARISAlert?.toast('error', 'Unable to Complete Action', error.message || 'The request could not be completed. Please try again.', { timer: 7000 });
            controls.forEach(control => { control.disabled = false; });
            form.classList.remove('sicms-ajax-loading');
            restoreProcessingState();
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
