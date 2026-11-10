// The only script on the public marketing pages (home, sample portfolios):
// Alpine from Livewire's ESM build (one Alpine, with x-trap) plus the small
// demos below, without the app's upload, editor and drag-and-drop code.
// Everything the demos show is also in the server-rendered HTML.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    // Photo lightbox: open(i), next(), prev(), close(), with arrow keys.
    Alpine.data('lightbox', (count) => ({
        open: false,
        index: 0,
        count,
        show(i) { this.index = i; this.open = true; },
        close() {
            this.open = false;
            this.$nextTick(() => this.$refs['thumb' + this.index]?.focus());
        },
        next() { this.index = (this.index + 1) % this.count; },
        prev() { this.index = (this.index + this.count - 1) % this.count; },
        key(e) {
            if (!this.open) return;
            if (e.key === 'ArrowRight') this.next();
            if (e.key === 'ArrowLeft') this.prev();
        },
    }));

    // "N days left" for the launch offer. The end date is already in the page;
    // this only adds the countdown.
    Alpine.data('countdown', (endsAt) => ({
        text: '',
        init() {
            const days = Math.ceil((new Date(endsAt) - new Date()) / 86400000);
            if (days > 1) this.text = days + ' days left';
            else if (days === 1) this.text = 'Last day';
        },
    }));
});

Livewire.start();
