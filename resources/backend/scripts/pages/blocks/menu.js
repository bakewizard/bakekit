import { fadeOut } from '@/utils/animation.js';

const menuCellAttributes = document.getElementById('menu-cell-attributes');
const attributeItemTemplate = document.getElementById('attribute-item-template');

if (menuCellAttributes) {
    function addAttribute(element) {
        const attributeInput = element.previousElementSibling;
        const attributeName = attributeInput.value;
        if (!attributeName) return;

        attributeInput.value = '';

        const ul = element.closest('.card').lastElementChild;
        const li = attributeItemTemplate.content.cloneNode(true);
        const label = li.querySelector('label');
        const input = li.querySelector('input');
        const param = ul.dataset.param;
        const name = `${param}[${attributeName}]`;
        const id = `${param.toLowerCase()}-${attributeName}`;

        label.setAttribute('for', id);
        label.textContent = attributeName;
        input.setAttribute('id', id);
        input.setAttribute('name', name);
        input.setAttribute('value', '');

        ul.appendChild(li);
    }

    function removeAttribute(element) {
        const li = element.closest('.list-group-item');
        fadeOut(li, 200, function () {
            this.remove();
        });
    }

    function onClick(e) {
        e.preventDefault();
        const element = e.target.closest('button.add-attribute-btn, button.remove-attribute-btn');
        if (!element) return;

        if (element.classList.contains('add-attribute-btn')) {
            addAttribute(element);
        } else {
            removeAttribute(element);
        }
    }

    menuCellAttributes.addEventListener('click', onClick);
}
