import { ajax } from '@/utils/ajax.js';
import { Modal } from 'bootstrap';

const cellSelectDialog = document.getElementById('cell-select-dialog');
const cellSelectButton = document.getElementById('cell-select-button');
const cellInput = document.getElementById('cell');

if (cellSelectDialog && cellSelectButton) {
    const modal = new Modal(cellSelectDialog);

    async function onClick(e) {
        try {
            const response = await ajax({
                url: e.currentTarget.dataset.url,
                dataType: 'html'
            });
            cellSelectDialog.querySelector('.modal-body').innerHTML = response;
            modal.show();
        } catch (e) {
            console.error(e.message);
        }
    }

    function onDialogClick(e) {
        e.preventDefault();
        const element = e.target.closest('a.list-group-item');
        if (!element) return;
        cellInput.value = element.dataset.path;
        modal.hide();
    }

    cellSelectButton.addEventListener('click', onClick);
    cellSelectDialog.addEventListener('click', onDialogClick);
}
