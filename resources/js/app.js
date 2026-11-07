import './bootstrap';
import '../css/app.css';
import './components/modal';

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

// Livewire 3 bundles Alpine. Import both from Livewire's ESM build so there's
// exactly one Alpine, register plugins, then start. Pages without Livewire
// components work the same way, so every page gets Alpine.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';

Alpine.plugin(intersect);
Alpine.plugin(collapse);
window.Alpine = Alpine;

Livewire.start();
