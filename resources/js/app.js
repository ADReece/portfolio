import '../css/app.css';
import './components/modal';
import './components/downloads';

import imagesLoaded from 'imagesloaded';
import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';

import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

// Make libraries available globally
FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType,
);

window.FilePond = FilePond;
window.dispatchEvent(new CustomEvent('filepond:ready'));
window.imagesLoaded = imagesLoaded;
window.Toastify = Toastify;

import fslightbox from 'fslightbox';
window.fslightbox = fslightbox;

import Sortable from 'sortablejs';
window.Sortable = Sortable;

// Global toast helper functions
window.showToast = function(message, type = 'info') {
    const backgrounds = {
        success: 'linear-gradient(to right, #10b981, #059669)',
        error: 'linear-gradient(to right, #ef4444, #dc2626)',
        warning: 'linear-gradient(to right, #f59e0b, #d97706)',
        info: 'linear-gradient(to right, #3b82f6, #2563eb)',
    };

    Toastify({
        text: message,
        duration: 3000,
        gravity: 'top',
        position: 'right',
        style: {
            background: backgrounds[type] || backgrounds.info,
        },
        stopOnFocus: true,
    }).showToast();
};

window.showSuccessToast = (message) => window.showToast(message, 'success');
window.showErrorToast = (message) => window.showToast(message, 'error');
window.showWarningToast = (message) => window.showToast(message, 'warning');
window.showInfoToast = (message) => window.showToast(message, 'info');

// fslightbox automatically provides refreshFsLightbox() - don't override it

// Livewire 3 bundles Alpine (with the focus/x-trap, collapse, intersect and
// anchor plugins). Import both from Livewire's ESM build so there's exactly
// one Alpine, then start. Pages without Livewire
// components work the same way, so every page gets Alpine.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;

// Light / dark / system theme switch (see layouts/partials/user-menu.blade.php).
// The choice is saved in this browser and applied before paint by theme-script.
document.addEventListener('alpine:init', () => {
    Alpine.data('themeSwitch', () => ({
        theme: (() => { try { return localStorage.getItem('theme') || 'system'; } catch (e) { return 'system'; } })(),
        set(value) {
            this.theme = value;
            const root = document.documentElement;
            root.classList.remove('dark', 'light');
            if (value !== 'system') root.classList.add(value);
            try {
                value === 'system' ? localStorage.removeItem('theme') : localStorage.setItem('theme', value);
            } catch (e) {}
        },
    }));
});

Livewire.start();

// Rich-text editors are only needed on a few settings pages.
if (document.querySelector('[data-rich-text]')) {
    import('./rich-text.js');
}
