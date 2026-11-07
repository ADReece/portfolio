@php
    $photographerName = $user->name ?: $user->username;
    $photoCount = $collection->sets->sum(fn ($set) => $set->photos->count());
@endphp
<x-collection-layout :user="$user"
                     :title="$collection->name.' · '.$photographerName"
                     :description="'Photos by '.$photographerName.($collection->event_date ? ', '.$collection->event_date->format('j F Y') : '')"
                     :image="$collection->private ? null : $collection->coverPhoto?->getAwsThumbnail()"
                     :noindex="(bool) $collection->private">
    <header class="relative isolate flex min-h-[70svh] w-full items-end overflow-hidden">
        @if($collection->coverPhoto)
            <img src="{{ $collection->coverPhoto->getUri() }}" alt="" fetchpriority="high"
                 class="absolute inset-0 -z-10 size-full object-cover" style="object-position: {{ $collection->coverObjectPosition() }}">
            <div class="absolute inset-0 -z-10 bg-linear-to-t from-black/75 via-black/25 to-black/10"></div>
        @else
            <div class="absolute inset-0 -z-10" style="background: linear-gradient(135deg, var(--portfolio-accent, #4f46e5), #111827);"></div>
        @endif

        <div class="mx-auto w-full max-w-7xl px-6 pt-24 pb-12 text-white sm:px-8">
            <p class="text-sm font-medium tracking-wide text-white/80 uppercase">
                Photos by <a href="{{ route('profile.view', $user->username) }}" class="underline-offset-4 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">{{ $photographerName }}</a>
            </p>
            <h1 class="mt-2 max-w-4xl text-4xl font-bold text-balance drop-shadow-lg sm:text-6xl">{{ $collection->name }}</h1>
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
                @if($collection->private)
                    <button type="button" onclick="requestArchive()"
                            class="inline-flex items-center gap-2 rounded-control bg-white/15 px-5 py-3 font-semibold text-white ring-1 ring-white/40 backdrop-blur hover:bg-white/25 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">
                        <x-ui.icon name="arrow-down-tray" class="size-5" /> Download all
                    </button>
                @endif
            </div>
        </div>
    </header>

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
</x-collection-layout>