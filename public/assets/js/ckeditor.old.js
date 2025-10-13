$('.ck-editor').closest('div').css('position', 'relative');

const ckeditors = new WeakMap();

function initializeCKEditors() {
    document.querySelectorAll('.ck-editor').forEach((element) => {
        // Skip elements not in the DOM
        if (!document.body.contains(element)) return;

        // If already initialized, destroy first
        if (ckeditors.has(element)) {
            const existingEditor = ckeditors.get(element);
            existingEditor.destroy()
                .then(() => {
                    ckeditors.delete(element);
                    createEditorInstance(element);
                })
                .catch(error => {
                    console.error('CKEditor destroy error:', error);
                });
        } else {
            createEditorInstance(element);
        }
    });
}

// function createEditorInstance(element) {
//     ClassicEditor
//         .create(element, {
//             toolbar: [
//                 'heading', '|',
//                 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
//                 'undo', 'redo'
//             ],
//             removePlugins: ['ImageUpload', 'EasyImage', 'MediaEmbed']
//         })
//         .then(editor => {
//             ckeditors.set(element, editor);
//             element.classList.add('ckeditor-initialized');
//         })
//         .catch(error => {
//             console.error('CKEditor initialization error:', error);
//         });
// }

function createEditorInstance(element) {
    ClassicEditor
        .create(element, {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                'undo', 'redo'
            ],
            removePlugins: ['ImageUpload', 'EasyImage', 'MediaEmbed'],
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    // Remove or skip heading 1
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                ]
            }
        })
        .then(editor => {
            ckeditors.set(element, editor);
            element.classList.add('ckeditor-initialized');
        })
        .catch(error => {
            console.error('CKEditor initialization error:', error);
        });
}
initializeCKEditors();