import { stripTrailingSlash } from '../utils/dom';

export function initAdminMenu() {
    const url = stripTrailingSlash(location.href);

    document.querySelectorAll('#admin-plugins-menu a').forEach(link => {
        if (url.includes(link.href) && link.pathname !== '/admin') {
            link.classList.add('active');

            const parent = link.closest('li')?.parentElement?.parentElement;
            parent?.classList.add('menu-open');
            parent?.firstElementChild?.classList.add('active');
        }
    });
}
