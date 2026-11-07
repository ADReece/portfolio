<x-profile-layout :user="$user">

    <x-slot name="header">
        <div class="flex flex-col items-center lg:mt-32">
            @if($user->logo_path)
                <div class="flex justify-center mb-6">
                    <img src="{{ $user->logoUrl() }}" alt="{{ ($user->name ?: $user->username) }} logo" class="h-20 object-contain drop-shadow-sm" />
                </div>
            @endif
            <h1 class="font-bold text-3xl">
                {{ $user->name ?? $user->username }}
            </h1>
            @if($user->bio)
            <div class="mt-2 text-sm leading-relaxed text-center break-words opacity-90 mx-auto [&_a]:underline [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 [&_p+p]:mt-2">
                {!! $user->bioHtml() !!}
            </div>
            @endif
        </div>
    </x-slot>

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12 overflow-x-hidden">
        @if($collections->isEmpty())
            <div class="text-center py-16">
                <p class="opacity-70">No collections to show yet.</p>
            </div>
        @else
            <!-- Collections Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($collections as $collection)
                    <a href="{{ route('profile.collection', ['username' => $user->username, 'collection_id' => $collection->id]) }}"
                       class="group relative overflow-hidden rounded-lg shadow-lg hover:shadow-2xl transition-all duration-300 block">

                        <!-- Cover Photo Container -->
                        <div class="relative aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            @if($collection->coverPhoto)
                                <img src="{{ $collection->coverPhoto->getAwsThumbnail() }}"
                                     alt="{{ $collection->name }}"
                                     class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-105"
                                     style="object-position: {{ $collection->coverObjectPosition() }};">
                            @else
                                <!-- Placeholder if no cover photo -->
                                <div class="w-full h-64 flex items-center justify-center bg-linear-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700">
                                    <svg class="w-20 h-20 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            <!-- Always-visible dark overlay for text readability -->
                            <div class="absolute inset-0 bg-linear-to-t from-black/70 via-black/30 to-transparent group-hover:from-black/90 group-hover:via-black/50 transition-all duration-300 pointer-events-none"></div>

                            <!-- Title bar with enhanced hover state -->
                            <div class="absolute bottom-0 left-0 right-0 p-4 pointer-events-none">
                                <h3 class="text-white text-lg font-semibold mb-1 transition-all duration-200">
                                    {{ $collection->name }}
                                </h3>
                                @if($collection->event_date)
                                    <p class="text-gray-300 text-sm mb-2">
                                        {{ $collection->event_date->format('F j, Y') }}
                                    </p>
                                @endif

                                <!-- Badges - visible on hover -->
                                <div class="flex flex-wrap gap-2 transition-opacity duration-300 opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 group-focus-within:opacity-100">
                                    @if($collection->private)
                                        <span class="inline-block px-2 py-1 bg-yellow-500/90 text-yellow-900 text-xs font-semibold rounded-full">
                                            🔒 Private
                                        </span>
                                    @endif
                                    @if($collection->watermarked)
                                        <span class="inline-block px-2 py-1 bg-blue-500/90 text-white text-xs font-semibold rounded-full">
                                            💎 Premium
                                        </span>
                                    @endif
                                    @php
                                        $photoCount = $collection->sets->sum(function($set) {
                                            return $set->photos->count();
                                        });
                                    @endphp
                                    @if($photoCount > 0)
                                        <span class="inline-block px-2 py-1 bg-white/20 text-white text-xs font-semibold rounded-full">
                                            📷 {{ $photoCount }} {{ Str::plural('photo', $photoCount) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        /* Ensure bio text has proper contrast */
        .prose {
            color: inherit;
        }

        .prose p, .prose strong, .prose em, .prose ul, .prose ol, .prose li {
            color: inherit !important;
        }

        .prose a {
            color: var(--portfolio-accent, #4F46E5) !important;
        }

        /* Prevent layout shift and scrollbar on hover */
        .group:hover {
            transform: translateY(-2px);
        }
    </style>
</x-profile-layout>
