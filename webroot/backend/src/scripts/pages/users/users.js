function addImage(e) {
    var files = e.target.files;
    var i, length = files.length;

    if (!length)
        return;

    for (i = 0; i < length; i++) {
        var file = files[i];
        if (file.type.match(/image.*/)) {
            var image = document.getElementById('users-main-image');
            image.setAttribute('style', 'width:200px;height:200px');
            image.setAttribute('src', window.URL.createObjectURL(file));
            image.addEventListener('load', function () {
                window.URL.revokeObjectURL(this.src);
            });

        }
    }
}

document.getElementById('users-images-input').addEventListener('change', addImage);