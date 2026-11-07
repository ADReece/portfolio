// Global confirm and prompt dialogs (markup in resources/views/components/modals.blade.php).

document.addEventListener('alpine:init', () => {
    Alpine.data('confirmModal', () => ({
        show: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        isDanger: false,
        confirmCallback: null,

        open(options) {
            this.title = options.title || 'Are you sure?';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Confirm';
            this.cancelText = options.cancelText || 'Cancel';
            this.isDanger = !!options.isDanger;
            this.confirmCallback = options.onConfirm || null;
            this.show = true;
        },

        confirm() {
            const callback = this.confirmCallback;
            this.close();
            if (callback) callback();
        },

        close() {
            this.show = false;
            this.confirmCallback = null;
        },
    }));

    Alpine.data('promptModal', () => ({
        show: false,
        title: '',
        message: '',
        placeholder: '',
        inputValue: '',
        inputType: 'text',
        confirmText: 'Submit',
        cancelText: 'Cancel',
        error: '',
        confirmCallback: null,

        open(options) {
            this.title = options.title || 'Enter a value';
            this.message = options.message || '';
            this.placeholder = options.placeholder || '';
            this.inputValue = options.defaultValue || '';
            this.inputType = options.inputType || 'text';
            this.confirmText = options.confirmText || 'Submit';
            this.cancelText = options.cancelText || 'Cancel';
            this.confirmCallback = options.onConfirm || null;
            this.error = '';
            this.show = true;
            this.$nextTick(() => this.$refs.promptInput?.focus());
        },

        confirm() {
            const value = this.inputValue.trim();
            if (!value) {
                this.error = 'Please fill this in.';
                return;
            }
            if (this.inputType === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                this.error = 'Enter an email address like name@example.com.';
                return;
            }
            const callback = this.confirmCallback;
            this.close();
            if (callback) callback(value);
        },

        close() {
            this.show = false;
            this.inputValue = '';
            this.error = '';
            this.confirmCallback = null;
        },
    }));
});

function dialogData(name) {
    const el = document.querySelector(`[x-data="${name}"]`);
    return el && window.Alpine ? window.Alpine.$data(el) : null;
}

window.confirmAction = function (options) {
    const modal = dialogData('confirmModal');
    if (modal) {
        modal.open(options);
    } else if (window.confirm([options.title, options.message].filter(Boolean).join('\n\n'))) {
        // Pages without the dialog markup fall back to the browser's confirm.
        options.onConfirm?.();
    }
};

window.promptUser = function (options) {
    const modal = dialogData('promptModal');
    if (modal) {
        modal.open(options);
    } else {
        const value = window.prompt([options.title, options.message].filter(Boolean).join('\n\n'), options.defaultValue || '');
        if (value) options.onConfirm?.(value);
    }
};

// <form data-confirm="Message" data-confirm-title="Title" data-confirm-button="Delete" data-confirm-danger>
// asks in the app's dialog before submitting, instead of window.confirm().
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === 'true') {
        return;
    }
    event.preventDefault();
    const submitter = event.submitter;
    window.confirmAction({
        title: form.dataset.confirmTitle || 'Are you sure?',
        message: form.dataset.confirm,
        confirmText: form.dataset.confirmButton || 'Confirm',
        isDanger: 'confirmDanger' in form.dataset,
        onConfirm: () => {
            form.dataset.confirmed = 'true';
            form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
        },
    });
}, true);
