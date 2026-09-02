(() => {
    const config = window.SICMS_PROTECTION;
    if (!config || !config.endpoint) return;

    const THROTTLE_MS = 30000;
    const lastSent = {};
    let toastTimer = null;

    const showToast = (text) => {
        let el = document.querySelector('.sicms-guard-toast');
        if (!el) {
            el = document.createElement('div');
            el.className = 'sicms-guard-toast';
            el.setAttribute('role', 'alert');
            document.body.appendChild(el);
        }
        el.textContent = text;
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 4000);
    };

    const report = (event) => {
        const now = Date.now();
        if (lastSent[event] && now - lastSent[event] < THROTTLE_MS) return;
        lastSent[event] = now;
        try {
            fetch(config.endpoint, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': config.token || ''
                },
                body: JSON.stringify({ event, page: location.pathname })
            }).catch(() => {});
        } catch (_) {}
    };

    const shieldOn = () => document.body.classList.add('sicms-shielded');
    const shieldOff = () => document.body.classList.remove('sicms-shielded');
    const shieldEnabled = !!config.shield;

    if (shieldEnabled) {
        window.addEventListener('blur', shieldOn);
        window.addEventListener('focus', shieldOff);
        window.addEventListener('pagehide', shieldOn);
        window.addEventListener('pageshow', () => {
            if (!document.hidden) shieldOff();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) shieldOn(); else shieldOff();
        });
    }

    document.addEventListener('keyup', (e) => {
        if (e.key === 'PrintScreen' || e.keyCode === 44) {
            if (shieldEnabled) {
                shieldOn();
                setTimeout(shieldOff, 900);
            }
            report('printscreen');
            showToast('Screen capture attempt detected. This action has been logged.');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(' ').catch(() => {});
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey
            && String(e.key).toLowerCase() === 'p') {
            e.preventDefault();
            report('print_attempt');
            showToast('Printing is disabled for this page.');
        }
    });

    window.addEventListener('beforeprint', () => report('print_attempt'));
})();
