(() => {
    const getSavedTheme = () => {
        try {
            return localStorage.getItem('sicms-theme')
                || document.cookie.match(/(?:^|; )sicms-theme=([^;]+)/)?.[1]
                || 'light';
        } catch (error) {
            return 'light';
        }
    };

    const saveTheme = (theme) => {
        try {
            localStorage.setItem('sicms-theme', theme);
            document.cookie = `sicms-theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
        } catch (error) {}
    };

    const updateToggle = (toggle) => {
        const dark = document.documentElement.dataset.theme === 'dark';
        toggle.setAttribute('aria-label', dark ? 'Enable light mode' : 'Enable dark mode');
        toggle.title = dark ? 'Enable light mode' : 'Enable dark mode';
        toggle.innerHTML = `<i class="bi bi-${dark ? 'sun' : 'moon-stars'}" aria-hidden="true"></i>`;
    };

    const applySavedTheme = () => {
        document.documentElement.dataset.theme = getSavedTheme() === 'dark' ? 'dark' : 'light';
        document.querySelectorAll('.theme-toggle').forEach(updateToggle);
    };

    const bindThemeToggles = () => {
        document.querySelectorAll('.theme-toggle').forEach(toggle => {
            if (toggle.dataset.themeBound === 'true') return;
            toggle.dataset.themeBound = 'true';
            toggle.addEventListener('click', () => {
                const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                document.documentElement.dataset.theme = theme;
                saveTheme(theme);
                document.querySelectorAll('.theme-toggle').forEach(updateToggle);
            });
            updateToggle(toggle);
        });
    };

    applySavedTheme();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindThemeToggles, { once: true });
    } else {
        bindThemeToggles();
    }
})();
