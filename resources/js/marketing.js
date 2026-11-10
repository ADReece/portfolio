// Small interactive demos for the homepage and sample portfolios. Everything
// they show is also in the server-rendered HTML; these only add behaviour.
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

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
