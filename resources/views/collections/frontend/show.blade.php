<x-collection-layout :user="$user">
    <!-- Full-Page Loading Spinner -->
    <div id="collection-loader" class="fixed inset-0 bg-gray-900 dark:bg-black z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="loader-large"></div>
            <p class="mt-4 text-white text-lg">Loading collection...</p>
        </div>
    </div>

    <!-- Full-screen Hero Section -->
    <div class="relative w-full h-screen -mt-8">
        @if($collection->coverPhoto)
            <!-- Hero Image -->
            <img src="{{ $collection->coverPhoto->getUri() }}"
                 alt="{{ $collection->name }}"
                 class="absolute inset-0 w-full h-full object-cover" style="object-position: {{ $collection->coverObjectPosition() }}">

            <!-- Accent Overlay (dynamic) -->
            <div class="absolute inset-0" style="background: linear-gradient(to bottom, var(--portfolio-accent-overlay-start), var(--portfolio-accent-overlay-mid), var(--portfolio-accent-overlay-end));"></div>
        @else
            <!-- Fallback gradient if no cover photo -->
            <div class="absolute inset-0 bg-linear-to-br from-indigo-900 via-purple-900 to-pink-900"></div>
        @endif

        <!-- Hero Content -->
        <div class="relative h-full flex flex-col items-center justify-center text-white px-4">
            <h1 class="text-5xl md:text-7xl font-bold text-center mb-4 drop-shadow-2xl">
                {{ $collection->name }}
            </h1>

            @if($collection->event_date)
                <p class="text-xl md:text-2xl text-gray-200 mb-6 drop-shadow-lg">
                    {{ $collection->event_date->format('F j, Y') }}
                </p>
            @endif

            @if($collection->description)
                <p class="text-lg text-gray-200 max-w-2xl text-center mb-8 drop-shadow-lg">
                    {{ $collection->description }}
                </p>
            @endif

            <!-- Badges -->
            <div class="flex gap-3 mb-8">
                @if($collection->private)
                    <span class="px-4 py-2 bg-yellow-500 text-yellow-900 text-sm font-semibold rounded-full">
                        🔒 Private Collection
                    </span>
                @endif
                @if($collection->watermarked)
                    <span class="px-4 py-2 bg-blue-500 text-white text-sm font-semibold rounded-full">
                        💎 Premium Content
                    </span>
                @endif
            </div>

            <!-- Download Archive Button (Private Collections Only) -->
            @if($collection->private)
                <div class="mb-8">
                    <button onclick="requestArchive()"
                            class="px-6 py-3 text-white text-lg font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105 flex items-center gap-2"
                            style="background-color: var(--portfolio-accent, #6366F1);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download All Photos
                    </button>
                </div>
            @endif

            <!-- Scroll Indicator -->
            <div class="absolute bottom-8 animate-bounce">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Photo Gallery Section -->
    <div class="py-12 px-8" style="background-color: var(--portfolio-bg-light, #F9FAFB); color: var(--portfolio-text-light, #1F2937);">
        <div class="max-w-7xl mx-auto">
            <livewire:masonry-grid :collectionId="$collection->id" />
        </div>
    </div>

    @if(isset($user))
    <style>
        /* Custom accent color for buttons */
        button[onclick="requestArchive()"]:hover {
            opacity: 0.9;
            filter: brightness(0.95);
        }

        /* Gallery background respects theme */
        @media (prefers-color-scheme: dark) {
            .py-12.px-8 {
                background-color: var(--portfolio-bg-dark, #111827) !important;
                color: var(--portfolio-text-dark, #F3F4F6) !important;
            }
        }

        @if(isset($user->portfolio_theme))
            @if($user->portfolio_theme === 'dark')
                .py-12.px-8 {
                    background-color: var(--portfolio-bg-dark, #111827) !important;
                    color: var(--portfolio-text-dark, #F3F4F6) !important;
                }
            @elseif($user->portfolio_theme === 'light')
                .py-12.px-8 {
                    background-color: var(--portfolio-bg-light, #F9FAFB) !important;
                    color: var(--portfolio-text-light, #1F2937) !important;
                }
            @endif
        @endif
    </style>
    @endif

    <style>
        .loader-large {
            border: 8px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top: 8px solid white;
            width: 80px;
            height: 80px;
            animation: spin-large 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin-large {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            const loader = document.getElementById('collection-loader');
            if (loader) {
                loader.style.opacity = '0';
                loader.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 300);
            }
        });

        // Fallback: Hide after 5 seconds even if load event doesn't fire
        setTimeout(function() {
            const loader = document.getElementById('collection-loader');
            if (loader && loader.style.display !== 'none') {
                loader.style.opacity = '0';
                loader.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 300);
            }
        }, 5000);

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