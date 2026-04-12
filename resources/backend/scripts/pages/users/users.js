let imagePreview = document.getElementById('image-preview');
let imageInput = document.getElementById('image-input');
let imageDeleteButton = document.getElementById('image-delete-btn');
let hiddenInput = document.getElementById('hidden-input');

imageInput.addEventListener('change', onAddImage);
imageDeleteButton?.addEventListener('click', onDeleteImage);

function onAddImage(e) {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const url = URL.createObjectURL(file);
    imagePreview.style.width = '200px';
    imagePreview.style.height = '200px';
    imagePreview.src = url;
    imagePreview.onload = () => URL.revokeObjectURL(url);

    if (hiddenInput) {
        hiddenInput.remove();
        hiddenInput = null;
    }
}

function onDeleteImage(e) {
    e.preventDefault();
    imagePreview.src = '/img/noimage.svg';
    imageInput.value = '';
    if (!hiddenInput) return;
    hiddenInput.remove();
    hiddenInput = null;
}
