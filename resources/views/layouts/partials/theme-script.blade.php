{{-- Applies the saved theme before first paint, so there's no flash. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || t === 'light') document.documentElement.classList.add(t);
        } catch (e) {}
    })();
</script>
