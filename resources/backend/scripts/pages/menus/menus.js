import { ajax } from '@/utils/ajax.js';
import { Modal } from 'bootstrap';

let linkSelectDialog = document.getElementById('link-select-dialog');
let linkSelectButton = document.getElementById('link-select-button');
let linkInput = document.getElementById('link-select-input');
let modal = new Modal(linkSelectDialog);

async function onClick(e) {
    try {
        const response = await ajax({
            url: e.currentTarget.dataset.url,
            dataType: 'html'
        });
        linkSelectDialog.querySelector('.modal-body').innerHTML = response;
        modal.show();
    } catch (e) {
        console.error(e.message);
    }
}

async function loadPage(url, formData) {
    if (formData) {
        if (url.indexOf('?') !== -1) {
            url = url.substring(0, url.indexOf('?'));
        }
    }

    try {
        const response = await ajax({
            url: url,
            dataType: 'html',
            data: formData
        });
        linkSelectDialog.querySelector('.modal-body').innerHTML = response;
        modal.show();
    } catch (e) {
        console.error(e.message);
    }
}

function onDialogClick(e) {
    e.preventDefault();

    let element = e.target.closest('a, button[type="submit"]');

    if (!element) {
        return;
    }

    let target = element.getAttribute('target');
    let href = element.getAttribute('href');

    if (element.getAttribute('data-bs-toggle') === 'collapse') {
        return;
    }

    if (element.classList.contains('list-group-item-action')) {
        if (target === '_blank') {
            loadPage(href);
        } else if (target === '_self') {
            linkInput.value = href;
            modal.hide();
        }
    } else if (element.hasAttribute('role') && element.getAttribute('role') === 'button') {
        linkInput.value = href;
        modal.hide();
    } else if (element.type === 'submit') {
        let filterForm = document.getElementById('filter-form');
        loadPage(filterForm.action, new FormData(filterForm));
    } else {
        loadPage(element.href);
    }
}

linkSelectButton.addEventListener('click', onClick);
linkSelectDialog.addEventListener('click', onDialogClick);
