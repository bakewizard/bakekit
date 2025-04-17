import * as bootstrap from 'bootstrap';
import * as adminlte from 'admin-lte/dist/js/adminlte';
import Sortable from 'sortablejs';
import Inputmask from 'inputmask';
import UseBootstrapSelect from 'use-bootstrap-select';
import { Datepicker } from 'vanillajs-datepicker';
import uk from 'vanillajs-datepicker/locales/uk';

Object.assign(Datepicker.locales, uk);

window.bootstrap = bootstrap;
window.ComboBox = UseBootstrapSelect;
window.Sortable = Sortable;

let confirmModal = document.getElementById('confirm-modal');
let confirmButton = document.getElementById('confirm-button');
let toggleCheckbox = document.getElementById('toggle-checkbox');
let menuItems = document.querySelectorAll('#admin-plugins-menu a');
let themeSwitcher = document.getElementById('theme-switcher');
const storedTheme = localStorage.getItem('theme');
let url = stripTrailingSlash(window.location.href);

/*****************Exports*****************/
export function slideUp(target, duration = 500) {
    target.style.transitionProperty = 'height, margin, padding';
    target.style.transitionDuration = duration + 'ms';
    target.style.boxSizing = 'border-box';
    target.style.height = target.offsetHeight + 'px';
    target.offsetHeight;
    target.style.overflow = 'hidden';
    target.style.height = 0;
    target.style.paddingTop = 0;
    target.style.paddingBottom = 0;
    target.style.marginTop = 0;
    target.style.marginBottom = 0;
    window.setTimeout(() => {
        target.style.display = 'none';
        target.style.removeProperty('height');
        target.style.removeProperty('padding-top');
        target.style.removeProperty('padding-bottom');
        target.style.removeProperty('margin-top');
        target.style.removeProperty('margin-bottom');
        target.style.removeProperty('overflow');
        target.style.removeProperty('transition-duration');
        target.style.removeProperty('transition-property');
    }, duration);
}

export function slideDown(target, duration = 500) {
    target.style.removeProperty('display');
    let display = window.getComputedStyle(target).display;
    if (display === 'none') display = 'block';
    target.style.display = display;
    let height = target.offsetHeight;
    target.style.overflow = 'hidden';
    target.style.height = 0;
    target.style.paddingTop = 0;
    target.style.paddingBottom = 0;
    target.style.marginTop = 0;
    target.style.marginBottom = 0;
    target.offsetHeight;
    target.style.boxSizing = 'border-box';
    target.style.transitionProperty = "height, margin, padding";
    target.style.transitionDuration = duration + 'ms';
    target.style.height = height + 'px';
    target.style.removeProperty('padding-top');
    target.style.removeProperty('padding-bottom');
    target.style.removeProperty('margin-top');
    target.style.removeProperty('margin-bottom');
    window.setTimeout(() => {
        target.style.removeProperty('height');
        target.style.removeProperty('overflow');
        target.style.removeProperty('transition-duration');
        target.style.removeProperty('transition-property');
    }, duration);
}

export function slideToggle(target, duration = 500) {
    if (window.getComputedStyle(target).display === 'none') {
        return slideDown(target, duration);
    } else {
        return slideUp(target, duration);
    }
}

export function fadeIn(element, duration, callback) {
    element.style.opacity = 0;
    element.style.display = '';

    let start = null;
    const animate = (timestamp) => {
        if (!start)
            start = timestamp;
        const progress = timestamp - start;
        element.style.opacity = progress / duration;
        if (progress < duration) {
            requestAnimationFrame(animate);
        } else {
            if (callback && typeof callback === 'function') {
                callback.call(element);
            }
        }
    };

    requestAnimationFrame(animate);
}

export function fadeOut(element, duration, callback) {
    let start = null;
    const animate = (timestamp) => {
        if (!start)
            start = timestamp;
        const progress = timestamp - start;
        element.style.opacity = 1 - progress / duration;
        if (progress < duration) {
            requestAnimationFrame(animate);
        } else {
            element.style.display = 'none';
            if (callback && typeof callback === 'function') {
                callback.call(element);
            }
        }
    };

    requestAnimationFrame(animate);
}

export function offset(el) {
    let box = el.getBoundingClientRect();
    let docElem = document.documentElement;
    return {
        top: box.top + window.scrollY - docElem.clientTop,
        left: box.left + window.scrollX - docElem.clientLeft
    };
}

export async function ajax({
    url,
    method = "GET",
    data = null,
    headers = {},
    dataType = "json",
    timeout = 10000
}) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    const acceptTypes = {
        json: "application/json",
        text: "text/plain",
        html: "text/html",
        any: "*/*"
    };

    headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': acceptTypes[dataType] || acceptTypes.any,
        ...headers
    };

    let options = { method, headers, signal: controller.signal };

    if (data) {
        if (method.toUpperCase() === "GET") {
            url += "?" + new URLSearchParams(data).toString();
        } else if (data instanceof FormData) {
            options.body = data; // Let fetch handle FormData
        } else {
            headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(url, options);
        clearTimeout(timeoutId);

        if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);

        return dataType === "json" ? response.json() : response.text();
    } catch (err) {
        if (err.name === "AbortError") throw new Error("Request timed out");
        throw err;
    }
}

export function initModal(form) {
    confirmModal.dataset.formName = form.name;
}
/*****************************************/

function initWidgets() {
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

function stripTrailingSlash(str) {
    if (str.substr(-1) === '/') {
        return str.substr(0, str.length - 1);
    }
    return str;
}

function toggleCheckboxes(e) {
    let table = e.currentTarget.closest('table');
    let rows = table.tBodies[0].rows;

    for (var i = 0; i < rows.length; i++) {
        let checkbox = rows[i].cells[0].firstElementChild;
        checkbox.checked = e.currentTarget.checked;
    }
}

function setTheme() {
    let theme = arguments[0];
    let icon = null;

    if (!theme) {
        theme = storedTheme ? storedTheme : (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    }

    if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    } else {
        document.documentElement.setAttribute('data-bs-theme', theme);
    }

    themeSwitcher?.querySelectorAll('ul .dropdown-item').forEach(function (item) {
        if (item.dataset.bsTheme === theme) {
            item.classList.add('active');
            icon = item.firstElementChild.cloneNode();
            icon.classList.remove('me-2');
        } else {
            item.classList.remove('active');
        }
    });

    themeSwitcher?.firstElementChild.firstElementChild.replaceWith(icon);
}
/************Listeners************/
function onModalShow(event) {
    const link = event.relatedTarget;
    const message = link.dataset.confirmMessage;

    confirmModal.dataset.href = link.href;

    const confirmMessage = document.getElementById('confirm-message');

    confirmMessage.textContent = message;
}

function onModalHide() {
    delete confirmModal.dataset.href;
    delete confirmModal.dataset.formName;
}

function onModalConfirmButtonClick() {
    let formName = confirmModal.dataset.formName;
    let href = confirmModal.dataset.href;

    if (formName) {
        document[formName].submit();
    } else if (href) {
        window.location = href;
    }

    const modal = bootstrap.Modal.getInstance(confirmModal);

    modal.hide();
}

function onChangeTheme(e) {
    e.preventDefault();

    let element = e.target.closest('a.dropdown-item');

    if (!element) {
        return;
    }

    let theme = element.dataset.bsTheme;

    localStorage.setItem('theme', theme);
    setTheme(theme);
}

if (toggleCheckbox) {
    toggleCheckbox.addEventListener('change', toggleCheckboxes);
}
confirmButton?.addEventListener('click', onModalConfirmButtonClick);
confirmModal?.addEventListener('hide.bs.modal', onModalHide);
confirmModal?.addEventListener('show.bs.modal', onModalShow);
themeSwitcher?.lastElementChild.addEventListener('click', onChangeTheme);
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (storedTheme !== "light" || storedTheme !== "dark") {
        setTheme();
    }
});

initWidgets();
setTheme();
