import { Modal } from 'bootstrap';

const confirmModal = document.getElementById('confirm-modal');
const confirmButton = document.getElementById('confirm-button');

function onModalShow(e) {
    const link = e.relatedTarget;
    confirmModal.dataset.href = link.href;
    document.getElementById('confirm-message').textContent = link.dataset.confirmMessage;
}

function onModalHide() {
    delete confirmModal.dataset.href;
    delete confirmModal.dataset.formName;
}

function onModalConfirmButtonClick() {
    const { formName, href } = confirmModal.dataset;
    if (formName) {
        document[formName].submit();
    } else if (href) {
        window.location = href;
    }
    Modal.getInstance(confirmModal)?.hide();
}

export function initConfirmModal() {
    if (!confirmModal) return;
    confirmButton?.addEventListener('click', onModalConfirmButtonClick);
    confirmModal.addEventListener('hide.bs.modal', onModalHide);
    confirmModal.addEventListener('show.bs.modal', onModalShow);
}
