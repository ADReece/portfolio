@php
    $photographer = $archive->user;
    $photographerName = $photographer ? ($photographer->name ?: $photographer->username) : null;
@endphp
<x-collection-layout :user="$photographer" :title="'Download · '.$archive->name" :noindex="true">
    <div class="mx-auto max-w-xl px-4 py-12 sm:px-6" id="archive-page"
         data-status-url="{{ $statusUrl }}" data-email-url="{{ $emailUrl }}">
        <h1 class="text-2xl font-bold sm:text-3xl">Your download</h1>
        <p class="mt-2 opacity-80">
            {{ $archive->photo_count }} {{ \Illuminate\Support\Str::plural('photo', $archive->photo_count) }}{{ $photographerName ? ' by '.$photographerName : '' }}, as one ZIP file:
            <span class="font-semibold">{{ $archive->name }}</span>
        </p>

        <div class="portfolio-neutral mt-6 rounded-card bg-white p-6 text-gray-900 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700">
            <p data-archive-status role="status" aria-live="polite" class="font-medium">
                @switch($state['status'])
                    @case('ready') Ready to download. @break
                    @case('failed') {{ $state['message'] ?: 'We couldn\'t prepare this download.' }} @break
                    @case('expired') This download has expired. Go back to the gallery and choose Download again. @break
                    @default Preparing your photos… {{ $state['done'] }} of {{ $state['total'] }}
                @endswitch
            </p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                <div data-archive-bar class="h-full rounded-full transition-[width]" style="background: var(--portfolio-accent, #4f46e5); width: {{ $state['total'] ? round($state['done'] / $state['total'] * 100) : 0 }}%"></div>
            </div>

            <a data-archive-download href="{{ $state['download_url'] ?? '#' }}" @unless($state['download_url']) hidden @endunless
               class="portfolio-button mt-5 inline-flex items-center gap-2 rounded-control px-5 py-3 font-semibold text-white" style="background: var(--portfolio-accent, #4f46e5)">
                <x-ui.icon name="arrow-down-tray" class="size-5" /> Download {{ $archive->name }}
            </a>

            @if(! in_array($state['status'], ['failed', 'expired'], true))
                <form data-archive-email class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700" @if($archive->emailed_at) hidden @endif>
                    <label for="archive-email" class="block text-sm font-medium">Email me the link too <span class="font-normal opacity-70">(optional)</span></label>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <input id="archive-email" name="email" type="email" required maxlength="255" autocomplete="email" value="{{ $archive->email }}"
                               class="min-w-0 flex-1 rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                        <button type="submit" class="rounded-control bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900">Email me</button>
                    </div>
                    <p data-archive-email-status role="status" class="mt-2 text-sm"></p>
                </form>
            @endif
            <p class="mt-5 text-sm opacity-70">You can leave this page: the link works until {{ $archive->expires_at->format('j F Y, g:i a') }}.</p>
        </div>
    </div>
    <script>
    (function () {
        var page = document.getElementById('archive-page');
        var status = page.querySelector('[data-archive-status]');
        var bar = page.querySelector('[data-archive-bar]');
        var link = page.querySelector('[data-archive-download]');
        var form = page.querySelector('[data-archive-email]');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        function poll() {
            fetch(page.dataset.statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (s) {
                    bar.style.width = (s.total ? Math.round(s.done / s.total * 100) : 0) + '%';
                    if (s.status === 'ready') {
                        status.textContent = 'Ready to download.';
                        link.href = s.download_url;
                        link.hidden = false;
                        return;
                    }
                    if (s.status === 'failed' || s.status === 'expired') {
                        status.textContent = s.message || 'This download isn\'t available. Go back to the gallery and choose Download again.';
                        return;
                    }
                    status.textContent = 'Preparing your photos… ' + s.done + ' of ' + s.total;
                    setTimeout(poll, 3000);
                })
                .catch(function () { setTimeout(poll, 6000); });
        }
        if (link.hidden && ! /expired|couldn|isn/.test(status.textContent)) poll();
        if (form) form.addEventListener('submit', function (e) {
            e.preventDefault();
            var out = form.querySelector('[data-archive-email-status]');
            out.textContent = 'Sending…';
            fetch(page.dataset.emailUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : '' },
                body: JSON.stringify({ email: form.elements.namedItem('email').value })
            }).then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || 'Please check the address and try again.'); return d; }); })
              .then(function (d) { out.textContent = d.message; })
              .catch(function (err) { out.textContent = err.message; });
        });
    })();
    </script>
</x-collection-layout>
