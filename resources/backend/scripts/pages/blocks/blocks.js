let cellSelectDialog = document.getElementById('cell-select-dialog');
let cellSelectButton = document.getElementById('cell-select-button');
let cellInput = document.getElementById('cell');
let modal = new bootstrap.Modal(cellSelectDialog);

async function onClick(e) {
    try {
        const response = await app.ajax({
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

    let element = e.target.closest('a.list-group-item');

    if (!element) {
        return;
    }

    cellInput.value = element.dataset.path;
    modal.hide();
}

cellSelectButton.addEventListener('click', onClick);
cellSelectDialog.addEventListener('click', onDialogClick);