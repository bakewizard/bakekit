import { Datepicker } from 'vanillajs-datepicker';
import UseBootstrapSelect from 'use-bootstrap-select';
import { stripTrailingSlash } from '@/utils/dom.js';

export function initWidgets() {
    let menuItems = document.querySelectorAll('#admin-plugins-menu a');
    let url = stripTrailingSlash(window.location.href);

    document.querySelectorAll('[data-widget="datepicker"]').forEach((element) => {
        let config = {
            buttonClass: 'btn',
            autohide: true,
            format: 'dd-mm-yyyy'
        };

        if (element.dataset.format !== undefined) {
            config.format = element.dataset.format;
        }
        new Datepicker(element, config);
    });

    document.querySelectorAll('[data-widget="select"]').forEach((element) => {
        new UseBootstrapSelect(element);
    });

    for (let element of menuItems) {
        if (url.indexOf(element.href) !== -1 && element.pathname !== '/admin') {
            element.classList.add('active');
            let parent = element.closest('li').parentElement.parentElement;
            if (parent) {
                parent.classList.add('menu-open');
                parent.firstElementChild.classList.add('active');
            }
        }
    }
}
