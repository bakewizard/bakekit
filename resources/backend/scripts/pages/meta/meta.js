tinyMCE.init({
    selector: 'textarea#description',
    menubar: false,
    statusbar: true,
    branding: false,
    skin: 'tinymce-5',
    content_css: 'tinymce-5',
    toolbar_mode: 'floating',
    license_key: 'gpl',
    language: 'uk',
    paste_as_text: true,
    link_default_target: '_blank',
    relative_urls: false,
    remove_script_host: false,
    plugins: [
        'advlist', 'autolink', 'link', 'lists', 'image', 'media', 'table', 'code', 'fullscreen', 'wordcount'
    ],
    toolbar: 'undo redo | fontsize | bold italic underline removeformat | forecolor backcolor | align bullist numlist | link image media table | code | fullscreen',
    file_picker_callback: openFileManager,
    setup: (editor) => {
        editor.on('init', () => {
            editor.getContainer().style.transition = 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out';
        });
        editor.on('focus', () => {
            editor.getContainer().style.boxShadow = '0 0 0 .2rem rgba(0, 123, 255, .25)';
            editor.getContainer().style.borderColor = '#80bdff';
        });
        editor.on('blur', () => {
            editor.getContainer().style.boxShadow = '';
            editor.getContainer().style.borderColor = '';
        });
    }
});

function openFileManager(callback, value, meta) {
    let x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
    let y = window.innerHeight || document.documentElement.clientHeight || document.getElementsByTagName('body')[0].clientHeight;
    let url = `/admin/file-manager?editor=true&field=${meta.fieldname}&type=${meta.filetype}`;

    tinyMCE.activeEditor.windowManager.openUrl({
        url: url,
        title: 'Select a file',
        width: x * 0.8,
        height: y * 0.8,
        resizable: 'yes',
        close_previous: 'no',
        onMessage: (api, message) => {
            callback(message.content);
        }
    });
}