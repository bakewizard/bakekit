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

        const iconMap = {
            light: 'fa-sun',
            dark: 'fa-moon',
            auto: 'fa-circle-half-stroke'
        };

        const currentIcon = switcher.querySelector('.nav-link i');
        currentIcon.className = `fa-solid ${iconMap[theme ?? 'auto']}`;

        switcher.querySelectorAll('.dropdown-item').forEach(item => {
            item.classList.toggle('active', item.dataset.bsTheme === theme);
        });
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
