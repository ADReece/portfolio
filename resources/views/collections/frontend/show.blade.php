@php
    $photographerName = $user->name ?: $user->username;
    $photoCount = $collection->sets->sum(fn ($set) => $set->photos->count());
    // Films (Videographer plan): ready ones in this collection's sets.
    $films = $user->hasFeature('video_upload')
        ? \App\Models\Video::ready()->whereIn('set_id', $collection->sets->pluck('id'))->orderBy('sort_order')->orderBy('created_at')->get()
        : collect();
@endphp
<x-collection-layout :user="$user"
                     :title="$collection->name.' · '.$photographerName"
                     :description="'Photos by '.$photographerName.($collection->event_date ? ', '.$collection->event_date->format('j F Y') : '')"
                     :image="$collection->private ? null : $collection->coverPhoto?->getAwsThumbnail()"
                     :noindex="(bool) $collection->private">
    <header class="relative isolate flex min-h-[70svh] w-full items-end overflow-hidden">
        @if($collection->coverPhoto)
            @php
                $cover = $collection->coverPhoto;
                $coverSized = $cover->has_display || $cover->shouldShowWatermark();
            @endphp
            <img src="{{ $cover->getUri() }}" alt="" fetchpriority="high"
                 @if($coverSized) srcset="{{ $cover->getAwsThumbnail() }} 800w, {{ $cover->getUri() }} 2048w" sizes="100vw" @endif
                 class="absolute inset-0 -z-10 size-full object-cover" style="object-position: {{ $collection->coverObjectPosition() }}">
            <div class="absolute inset-0 -z-10 bg-linear-to-t from-black/75 via-black/25 to-black/10"></div>
        @else
            <div class="absolute inset-0 -z-10" style="background: linear-gradient(135deg, var(--portfolio-accent, #4f46e5), #111827);"></div>
        @endif

        <div class="mx-auto w-full max-w-7xl px-6 pt-24 pb-12 text-white sm:px-8">
            <p class="text-sm font-medium tracking-wide text-white/80 uppercase">
                Photos by <a href="{{ route('profile.view', $user->username) }}" class="underline-offset-4 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">{{ $photographerName }}</a>
            </p>
            <h1 class="portfolio-on-photo mt-2 max-w-4xl text-4xl font-bold text-balance drop-shadow-lg sm:text-6xl">{{ $collection->name }}</h1>
            <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-base text-white/85 sm:text-lg">
                @if($collection->event_date)<span>{{ $collection->event_date->format('j F Y') }}</span><span aria-hidden="true">·</span>@endif
                <span>{{ $photoCount }} {{ \Illuminate\Support\Str::plural('photo', $photoCount) }}</span>
                @if($collection->private)
                    <span aria-hidden="true">·</span>
                    <span class="inline-flex items-center gap-1"><x-ui.icon name="lock-closed" class="size-4" /> Private gallery</span>
                @endif
            </p>
            @if($collection->description)
                <p class="mt-4 max-w-2xl text-white/85">{{ $collection->description }}</p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="#gallery" class="portfolio-button inline-flex items-center gap-2 rounded-control px-5 py-3 font-semibold text-white shadow-lg focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black"
                   style="background-color: var(--portfolio-accent, #4f46e5);">
                    <x-ui.icon name="photo" class="size-5" /> View photos
                </a>
                @if(\App\Downloads\DigitalSales::onSale($collection))
                    <a href="{{ route('download-purchase.show', $collection) }}"
                       class="inline-flex items-center gap-2 rounded-control bg-white px-5 py-3 font-semibold text-gray-900 shadow-lg hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black">
                        <x-ui.icon name="shopping-bag" class="size-5" /> Buy photos
                    </a>
                @endif
                @if($collection->private && \App\Downloads\DigitalSales::freeDownloadsAllowed(request(), $collection))
                    <button type="button" onclick="requestArchive()"
                            class="inline-flex items-center gap-2 rounded-control bg-white/15 px-5 py-3 font-semibold text-white ring-1 ring-white/40 backdrop-blur hover:bg-white/25 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">
                        <x-ui.icon name="arrow-down-tray" class="size-5" /> Download all
                    </button>
                @endif
            </div>
        </div>
    </header>

    @if($films->isNotEmpty())
        <section id="films" aria-labelledby="films-heading" class="gallery-section scroll-mt-4 px-4 pt-12 sm:px-8" style="background-color: var(--portfolio-bg-light, #F9FAFB); color: var(--portfolio-text-light, #1F2937);">
            <div class="mx-auto max-w-7xl">
                <h2 id="films-heading" class="text-2xl font-semibold">{{ $films->count() === 1 ? 'Film' : 'Films' }}</h2>
                <ul role="list" class="mt-4 grid gap-6 md:grid-cols-2">
                    @foreach($films as $film)
                        <li>
                            <figure>
                                <video controls preload="none" playsinline class="aspect-video w-full rounded-lg bg-black object-contain shadow-lg"
                                       @if($film->posterUrl()) poster="{{ $film->posterUrl() }}" @endif
                                       @if($user->copy_protection) controlslist="nodownload" oncontextmenu="return false;" @endif
                                       aria-label="{{ $film->displayTitle() }}">
                                    <source src="{{ $film->playbackUrl() }}">
                                    Your browser can't play this film.
                                </video>
                                <figcaption class="mt-2 flex justify-between gap-3 text-sm">
                                    <span class="font-medium">{{ $film->displayTitle() }}</span>
                                    @if($film->durationLabel())<span class="opacity-70">{{ $film->durationLabel() }}</span>@endif
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <!-- Photo Gallery Section -->
    <div id="gallery" class="gallery-section scroll-mt-4 py-12 px-4 sm:px-8" style="background-color: var(--portfolio-bg-light, #F9FAFB); color: var(--portfolio-text-light, #1F2937);">
        <div class="max-w-7xl mx-auto">
            <livewire:masonry-grid :collectionId="$collection->id" />
        </div>
    </div>

    @if(isset($user))
    <style>
        /* Custom accent color for buttons */

        /* Gallery background respects theme */
        @media (prefers-color-scheme: dark) {
            .gallery-section {
                background-color: var(--portfolio-bg-dark, #111827) !important;
                color: var(--portfolio-text-dark, #F3F4F6) !important;
            }
        }

        @if(isset($user->portfolio_theme))
            @if($user->portfolio_theme === 'dark')
                .gallery-section {
                    background-color: var(--portfolio-bg-dark, #111827) !important;
                    color: var(--portfolio-text-dark, #F3F4F6) !important;
                }
            @elseif($user->portfolio_theme === 'light')
                .gallery-section {
                    background-color: var(--portfolio-bg-light, #F9FAFB) !important;
                    color: var(--portfolio-text-light, #1F2937) !important;
                }
            @endif
        @endif
    </style>
    @endif


    <script>
        // Request archive download
        function requestArchive() {
            window.promptUser({
                title: 'Request Download Archive',
                message: 'Enter your email address to receive the download link when the archive is ready:',
                placeholder: 'your@email.com',
                inputType: 'email',
                confirmText: 'Request Archive',
                onConfirm: (email) => {
                    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        window.showErrorToast('Please enter a valid email address');
                        return;
                    }

                    // Show loading toast
                    window.showInfoToast('Processing your request...');

                    fetch('{{ route("collections.request-archive", $collection->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ email: email })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.showSuccessToast("We're preparing your photos. We'll email " + email + ' a download link when they are ready.');
                        } else {
                            window.showErrorToast(data.message || 'Failed to request archive');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.showErrorToast("We couldn't start your download. Check your connection and try again.");
                    });
                }
            });
        }
    </script>
    @if($collection->favourites_enabled)
        {{-- Client favourites (proofing): hearts on the photos, a bar with the count, and a form to send them. --}}
        <div id="favourites-bar" hidden class="portfolio-neutral fixed inset-x-0 bottom-4 z-40 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-3 rounded-full bg-white px-4 py-2 text-sm text-gray-900 shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700">
            <span aria-live="polite"><span class="text-rose-600" aria-hidden="true">&#9829;</span> <span data-fav-count>0</span> <span data-fav-label>favourites</span><span data-fav-sent hidden> · sent</span></span>
            <button type="button" data-fav-open class="rounded-full bg-gray-900 px-4 py-1.5 font-semibold text-white hover:bg-gray-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-gray-500 dark:bg-white dark:text-gray-900">Send to {{ $photographerName }}</button>
        </div>
        <dialog id="favourites-dialog" aria-labelledby="favourites-title"
                class="portfolio-neutral w-[min(32rem,calc(100%-2rem))] rounded-card bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/50 dark:bg-gray-900 dark:text-gray-100">
            <form method="dialog" data-fav-form class="space-y-4 p-6">
                <h2 id="favourites-title" class="text-lg font-semibold">Send your favourites</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $photographerName }} will see the <span data-fav-count>0</span> photos you've hearted. You can keep changing them and send again.</p>
                <div>
                    <label for="fav-name" class="block text-sm font-medium">Your name <span class="font-normal text-gray-500">(optional)</span></label>
                    <input id="fav-name" name="name" maxlength="100" autocomplete="name" class="mt-1 w-full rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                </div>
                <div>
                    <label for="fav-email" class="block text-sm font-medium">Your email <span class="font-normal text-gray-500">(optional)</span></label>
                    <input id="fav-email" name="email" type="email" maxlength="255" autocomplete="email" class="mt-1 w-full rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                </div>
                <div>
                    <label for="fav-note" class="block text-sm font-medium">A note <span class="font-normal text-gray-500">(optional)</span></label>
                    <textarea id="fav-note" name="note" rows="3" maxlength="2000" class="mt-1 w-full rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800" placeholder="e.g. these for the album, the first one as a large print"></textarea>
                </div>
                <p data-fav-status role="status" class="text-sm"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" data-fav-close class="rounded-control px-4 py-2 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">Close</button>
                    <button type="submit" value="send" class="rounded-control bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900">Send favourites</button>
                </div>
            </form>
        </dialog>
        <script>
        (function () {
            var stateUrl = @js(route('favourites.show', $collection));
            var toggleUrl = @js(url('/collections/'.$collection->id.'/favourites')) + '/';
            var submitUrl = @js(route('favourites.submit', $collection));
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            var ids = new Set();
            var bar = document.getElementById('favourites-bar');
            var dialog = document.getElementById('favourites-dialog');
            var form = dialog.querySelector('[data-fav-form]');
            var status = dialog.querySelector('[data-fav-status]');
            var field = function (name) { return form.elements.namedItem(name); };

            function request(url, body) {
                return fetch(url, {
                    method: body ? 'POST' : 'GET',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: body ? JSON.stringify(body) : undefined,
                    credentials: 'same-origin',
                }).then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error(d.message || 'Something went wrong. Please try again.'); return d; }); });
            }
            function apply(state) {
                ids = new Set(state.ids || []);
                document.querySelectorAll('[data-fav-count]').forEach(function (el) { el.textContent = ids.size; });
                bar.querySelector('[data-fav-label]').textContent = ids.size === 1 ? 'favourite' : 'favourites';
                bar.querySelector('[data-fav-sent]').hidden = !state.submitted_at;
                bar.hidden = ids.size === 0;
                sync(document);
                if (state.name !== undefined) {
                    field('name').value = field('name').value || state.name || '';
                    field('email').value = field('email').value || state.email || '';
                    field('note').value = field('note').value || state.note || '';
                }
            }
            function sync(root) {
                root.querySelectorAll('[data-favourite]').forEach(function (b) {
                    b.setAttribute('aria-pressed', ids.has(b.getAttribute('data-favourite')) ? 'true' : 'false');
                });
            }
            // The grid copies photos into columns as it lays them out: keep new hearts in step.
            new MutationObserver(function (records) {
                records.forEach(function (r) { r.addedNodes.forEach(function (n) { if (n.querySelectorAll) sync(n); }); });
            }).observe(document.body, { childList: true, subtree: true });

            document.addEventListener('click', function (e) {
                var b = e.target.closest('[data-favourite]');
                if (!b) return;
                e.preventDefault();
                e.stopPropagation();
                b.disabled = true;
                request(toggleUrl + encodeURIComponent(b.getAttribute('data-favourite')), {})
                    .then(apply)
                    .catch(function (err) { window.showErrorToast ? window.showErrorToast(err.message) : alert(err.message); })
                    .finally(function () { b.disabled = false; });
            }, true);
            bar.querySelector('[data-fav-open]').addEventListener('click', function () { status.textContent = ''; dialog.showModal(); });
            dialog.querySelector('[data-fav-close]').addEventListener('click', function () { dialog.close(); });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                status.textContent = 'Sending…';
                status.className = 'text-sm';
                request(submitUrl, { name: field('name').value, email: field('email').value, note: field('note').value })
                    .then(function (d) { apply(d); status.textContent = d.message; status.className = 'text-sm text-green-700 dark:text-green-400'; })
                    .catch(function (err) { status.textContent = err.message; status.className = 'text-sm text-red-700 dark:text-red-400'; });
            });
            request(stateUrl).then(apply).catch(function () {});
        })();
        </script>
    @endif
</x-collection-layout>