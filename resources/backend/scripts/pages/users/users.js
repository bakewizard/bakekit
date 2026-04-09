let mainImage = document.getElementById('users-main-image');
let imagesInput = document.getElementById('users-images-input');
let hiddenInput = document.getElementById('users-file-id');
let deleteImageButton = document.getElementById('users-images-delete-btn');

function onAddImage(e) {
    const file = e.target.files[0];
    if (!file || !file.type.startsWith('image/')) return;

    const url = URL.createObjectURL(file);
    mainImage.style.width = '200px';
    mainImage.style.height = '200px';
    mainImage.src = url;
    mainImage.onload = () => URL.revokeObjectURL(url);

    if (hiddenInput) {
        hiddenInput.remove();
        hiddenInput = null;
    }
}

function onDeleteImage(e) {
    e.preventDefault();
    mainImage.src = '/img/noimage.svg';
    imagesInput.value = '';
    if (!hiddenInput) return;
    hiddenInput.remove();
    hiddenInput = null;
}

imagesInput.addEventListener('change', onAddImage);
deleteImageButton?.addEventListener('click', onDeleteImage);
