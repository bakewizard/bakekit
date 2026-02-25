// import { bootstrap } from '../libs/bootstrap';

export function initConfirmModal() {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;

    const button = document.getElementById('confirm-button');

    modal.addEventListener('show.bs.modal', e => {
        modal.dataset.href = e.relatedTarget?.href;
    });

    modal.addEventListener('hide.bs.modal', () => {
        delete modal.dataset.href;
        delete modal.dataset.formName;
    });

    button?.addEventListener('click', () => {
        modal.dataset.formName
            ? document[modal.dataset.formName].submit()
            : modal.dataset.href && (location.href = modal.dataset.href);

        bootstrap.Modal.getInstance(modal)?.hide();
    });
}
