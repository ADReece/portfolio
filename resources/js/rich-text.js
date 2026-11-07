// Small rich-text editor (bold, italic, lists, links) for fields like the
// portfolio bio. Loaded only on pages that have a [data-rich-text] field.
// Markup: <div data-rich-text data-input="bio" aria-labelledby="bio-label"></div>
//         <input type="hidden" id="bio" name="bio" value="…">
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

document.querySelectorAll('[data-rich-text]').forEach((el) => {
    const input = document.getElementById(el.dataset.input);
    if (!input || el.dataset.ready) return;
    el.dataset.ready = 'true';

    const quill = new Quill(el, {
        theme: 'snow',
        placeholder: el.dataset.placeholder || '',
        modules: {
            toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']],
        },
    });

    if (input.value) {
        quill.clipboard.dangerouslyPasteHTML(input.value);
    }

    // Label the editable area for screen readers.
    const editable = el.querySelector('.ql-editor');
    if (editable && el.getAttribute('aria-labelledby')) {
        editable.setAttribute('role', 'textbox');
        editable.setAttribute('aria-multiline', 'true');
        editable.setAttribute('aria-labelledby', el.getAttribute('aria-labelledby'));
    }

    const sync = () => {
        input.value = quill.getText().trim() === '' ? '' : quill.getSemanticHTML();
    };
    quill.on('text-change', sync);
    input.form?.addEventListener('submit', sync);
});
