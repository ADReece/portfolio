<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('My Collections') }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('collections.create') }}"
                   class="px-3 py-2 bg-indigo-600 text-white rounded-md text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create New Collection
                </a>
                <button onclick="toggleReorder()"
                        class="px-3 py-2 bg-gray-600 text-white rounded-md text-sm">
                    Reorder
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Reorder Panel -->
            <div id="reorder-panel" class="hidden mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-md shadow-sm p-6">
                    <livewire:collection-order-manager />
                    <div class="mt-4 text-right">
                        <button onclick="toggleReorder()"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            @if(!is_null($collections) && $collections->count() > 0)
                <!-- Collections Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($collections as $collection)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg hover:shadow-lg transition-all border-2 border-transparent hover:border-indigo-500 dark:hover:border-indigo-400">
                            <a href="{{ route('collections.edit', $collection->id) }}" class="block p-6">
                                <!-- Collection Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                            {{ $collection->name }}
                                        </h3>
                                        @if($collection->event_date)
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $collection->event_date->format('F j, Y') }}
                                            </p>
                                        @endif
                                    </div>

                                    <!-- Status Badge -->
                                    @if($collection->status === 'Published')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Published
                                        </span>
                                    @elseif($collection->status === 'Draft')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Draft
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            Archived
                                        </span>
                                    @endif
                                </div>

                                <!-- Collection Stats -->
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        {{ $collection->sets->count() }} {{ Str::plural('set', $collection->sets->count()) }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $collection->sets->sum(function($set) { return $set->photos->count(); }) }} {{ Str::plural('photo', $collection->sets->sum(function($set) { return $set->photos->count(); })) }}
                                    </div>
                                </div>

                                <!-- Collection Badges -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @if($collection->private)
                                        <span class="inline-flex items-center px-2 py-1 rounded-sm text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            🔒 Private
                                        </span>
                                    @endif
                                    @if($collection->watermarked)
                                        <span class="inline-flex items-center px-2 py-1 rounded-sm text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            💎 Watermarked
                                        </span>
                                    @endif
                                    @if($collection->hide_from_portfolio)
                                        <span class="inline-flex items-center px-2 py-1 rounded-sm text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            👁️‍🗨️ Hidden
                                        </span>
                                    @endif
                                </div>

                                <!-- Cover Photo Preview -->
                                @if($collection->coverPhoto)
                                    <div class="aspect-video rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                        <img src="{{ $collection->coverPhoto->getAwsThumbnail() }}"
                                             alt="{{ $collection->name }}"
                                             class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="aspect-video rounded-lg overflow-hidden bg-linear-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- Action Button -->
                                <div class="mt-4 flex items-center text-indigo-600 dark:text-indigo-400 text-sm font-medium">
                                    Manage Collection
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-24 w-24 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No collections yet</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first photo collection.</p>
                        <div class="mt-6">
                            <a href="{{ route('collections.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create Your First Collection
                            </a>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
    <script>
        function toggleReorder(){
            const panel = document.getElementById('reorder-panel');
            panel.classList.toggle('hidden');
        }
    </script>
</x-app-layout>