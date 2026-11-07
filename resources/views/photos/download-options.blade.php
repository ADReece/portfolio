@php
    $photographer = $photo->user;
    $collection = $photo->set?->collection;
    $backUrl = $collection && $photographer ? route('profile.collection', ['username' => $photographer->username, 'collection_id' => $collection->id]) : url()->previous();
@endphp

<x-collection-layout :user="$photographer" :title="'Download photo · '.($photographer->name ?: $photographer->username)" :noindex="true">
    <div class="mx-auto max-w-2xl space-y-6 px-4 py-10">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm opacity-80 hover:underline hover:opacity-100">
            <x-ui.icon name="chevron-left" class="size-4" /> Back to gallery
        </a>

        <div>
            <h1 class="text-2xl font-bold">Download this photo</h1>
            @if($photo->caption)<p class="mt-1 opacity-80">{{ $photo->caption }}</p>@endif
        </div>

        <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ?: 'The photo you are downloading' }}"
             class="max-h-72 w-full rounded-card bg-black/5 object-contain">

        <section class="portfolio-neutral space-y-3 rounded-card bg-white p-5 text-gray-900 shadow-sm dark:bg-gray-800 dark:text-gray-100"
                 x-data="{ email: '', sending: false, sent: false, err: '' }" aria-labelledby="download-heading">
            <h2 id="download-heading" class="font-semibold">Get the full-size photo</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">We'll email you a download link that works for 24 hours.</p>
            <form class="flex flex-wrap gap-2" novalidate
                  x-on:submit.prevent="
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { err = 'Enter an email address like name@example.com.'; return; }
                    err = ''; sending = true;
                    fetch(@js(route('photos.request-download', $photo)), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ email }),
                    })
                    .then(r => r.json().then(d => ({ ok: r.ok, d })))
                    .then(({ ok, d }) => { if (ok && d.success) { sent = true } else { err = d.message || 'We couldn’t send the link. Try again.' } })
                    .catch(() => err = 'We couldn’t send the link. Check your connection and try again.')
                    .finally(() => sending = false)">
                <label for="download-email" class="sr-only">Your email address</label>
                <input id="download-email" type="email" x-model="email" placeholder="you@example.com" autocomplete="email" required
                       :aria-invalid="err ? 'true' : 'false'" aria-describedby="download-status"
                       class="min-w-0 flex-1 rounded-control border-gray-300 text-sm shadow-xs focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <button type="submit" :disabled="sending || sent"
                        class="portfolio-button inline-flex items-center gap-2 rounded-control px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        style="background: var(--portfolio-accent, #4f46e5)">
                    <span x-text="sent ? 'Link sent' : (sending ? 'Sending…' : 'Email me the link')">Email me the link</span>
                </button>
            </form>
            <p id="download-status" role="status" aria-live="polite" class="text-sm">
                <span x-show="err" x-text="err" class="text-red-700 dark:text-red-400"></span>
                <span x-show="sent" x-cloak class="text-green-700 dark:text-green-400">Check your inbox for the download link.</span>
            </p>
        </section>

        @if($templates->isNotEmpty())
            <section class="space-y-4" aria-labelledby="template-heading">
                <h2 id="template-heading" class="font-semibold">Or download it with a design</h2>
                @foreach($templates as $template)
                    <form method="POST" action="{{ route('photos.download-with-template', [$photo, $template]) }}"
                          class="portfolio-neutral space-y-4 rounded-card bg-white p-5 text-gray-900 shadow-sm dark:bg-gray-800 dark:text-gray-100">
                        @csrf
                        <div class="flex items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold">{{ $template->name }}</h3>
                                @if($template->description)<p class="text-sm text-gray-600 dark:text-gray-400">{{ $template->description }}</p>@endif
                            </div>
                            @if($template->overlayUrl())
                                <img src="{{ $template->overlayUrl() }}" alt="" class="size-16 shrink-0 rounded-md object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                            @endif
                        </div>
                        @foreach($template->fields ?? [] as $field)
                            <x-ui.input :id="'t'.$loop->parent->index.'-'.$field['key']" :name="'fields['.$field['key'].']'" :error-key="'fields.'.$field['key']"
                                        :label="$field['label'] ?? $field['key']" :value="old('fields.'.$field['key'], $photo->caption ?? '')" />
                        @endforeach
                        <button type="submit" class="portfolio-button w-full rounded-control px-4 py-2 text-sm font-semibold text-white" style="background: var(--portfolio-accent, #4f46e5)">
                            Create and download
                        </button>
                    </form>
                @endforeach
            </section>
        @endif
    </div>
</x-collection-layout>
