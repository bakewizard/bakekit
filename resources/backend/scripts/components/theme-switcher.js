export function initThemeSwitcher() {
    const switcher = document.getElementById('theme-switcher');
    if (!switcher) return;

    const storedTheme = localStorage.getItem('theme');

    function resolveTheme(theme) {
        if (!theme || theme === 'auto') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
        }
        return theme;
    }

    function applyTheme(theme) {
        const resolved = resolveTheme(theme);
        document.documentElement.setAttribute('data-bs-theme', resolved);

        let icon = null;

        switcher.querySelectorAll('.dropdown-item').forEach(item => {
            if (item.dataset.bsTheme === theme) {
                item.classList.add('active');
                icon = item.firstElementChild.cloneNode(true);
                icon.classList.remove('me-2');
            } else {
                item.classList.remove('active');
            }
        });

        if (icon) {
            switcher.firstElementChild.firstElementChild.replaceWith(icon);
        }
    }

    // click handler
    switcher.addEventListener('click', e => {
        const item = e.target.closest('.dropdown-item');
        if (!item) return;

        e.preventDefault();

        const theme = item.dataset.bsTheme;
        localStorage.setItem('theme', theme);
        applyTheme(theme);
    });

    // init on load
    applyTheme(storedTheme ?? 'auto');

    // system theme change (auto mode)
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (localStorage.getItem('theme') === 'auto') {
            applyTheme('auto');
        }
    });
}
