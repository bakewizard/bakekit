let menuCellAttributes = document.getElementById('menu-cell-attributes');
let attributeItemTemplate = document.getElementById('attribute-item-template');

function onClick(e) {
    e.preventDefault();

    let element = e.target.closest('button.add-attribute-btn, button.remove-attribute-btn');

    if (!element) {
        return;
    }

    if (element.classList.contains('add-attribute-btn')) {
        addAttribute(element);
    } else if (element.classList.contains('remove-attribute-btn')) {
        removeAttribute(element);
    }
}

function addAttribute(element) {
    let attributeInput = element.previousElementSibling;
    let attributeName = attributeInput.value;

    if (attributeName === '') {
        return false;
    }

    attributeInput.value = '';

    let ul = element.closest('.card').lastElementChild;
    let li = attributeItemTemplate.content.cloneNode(true);
    let label = li.querySelector('label');
    let input = li.querySelector('input');

    let param = ul.dataset.param;
    let name = param + '[' + attributeName + ']';
    let id = param.toLowerCase() + '-' + attributeName;

    label.setAttribute('for', id);
    label.innerText = attributeName;
    input.setAttribute('id', id);
    input.setAttribute('name', name);
    input.setAttribute('value', '');
    ul.appendChild(li);
}

function removeAttribute(element) {
    let li = element.closest('.list-group-item');
    app.fadeOut(li, 200, function () {
        this.remove();
    });
}

menuCellAttributes.addEventListener('click', onClick);