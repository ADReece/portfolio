<x-app-layout title="Collections">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" x-data="{ reordering: false }">
        <x-ui.page-header title="Collections" description="One collection per shoot or event. Publish a collection to share it with your client.">
            <x-slot:actions>
                @if($counts['all'] > 1)
                    <x-ui.button variant="secondary" icon="arrows-up-down" x-on:click="reordering = !reordering" ::aria-expanded="reordering" aria-controls="reorder-panel">
                        <span x-text="reordering ? 'Done reordering' : 'Reorder'">Reorder</span>
                    </x-ui.button>
                @endif
                <x-ui.button icon="plus" :href="route('collections.create')">New collection</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div id="reorder-panel" x-show="reordering" x-cloak x-collapse>
            <x-ui.card title="Reorder collections" description="Drag collections into the order they should appear on your portfolio, then save.">
                <livewire:collection-order-manager />
            </x-ui.card>
        </div>

        @if($counts['all'] > 0)
            <nav aria-label="Filter collections" class="-mx-1 flex gap-1 overflow-x-auto pb-1">
                @foreach($filters as $key => $label)
                    @continue($key !== 'all' && $counts[$key] === 0)
                    <a href="{{ route('collections.index', $key === 'all' ? [] : ['show' => $key]) }}"
                       @if($filter === $key) aria-current="page" @endif
                       @class([
                           'inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500',
                           'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $filter === $key,
                           'text-gray-700 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800' => $filter !== $key,
                       ])>
                        {{ $label }}
                        <span @class(['tabular-nums text-xs', 'opacity-70' => $filter === $key, 'text-gray-500' => $filter !== $key])>{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        @if($collections->isNotEmpty())
            <ul role="list" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($collections as $collection)
                    <li class="group relative overflow-hidden rounded-card bg-white shadow-xs ring-1 ring-gray-200 transition hover:shadow-md hover:ring-gray-300 focus-within:ring-2 focus-within:ring-brand-500 dark:bg-gray-800 dark:ring-gray-700 dark:hover:ring-gray-600">
                        <div class="aspect-[3/2] bg-gray-100 dark:bg-gray-900">
                            @if($collection->coverPhoto)
                                <img src="{{ $collection->coverPhoto->getAwsThumbnail() }}" alt="" loading="lazy"
                                     class="size-full object-cover" style="object-position: {{ $collection->coverObjectPosition() }}">
                            @else
                                <div class="flex size-full flex-col items-center justify-center gap-2 text-gray-400 dark:text-gray-500">
                                    <x-ui.icon name="photo" class="size-10" />
                                    <span class="text-sm">{{ $collection->photos_count ? 'No cover photo chosen' : 'No photos yet' }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="truncate text-base font-semibold text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('collections.edit', $collection) }}" class="focus:outline-hidden">
                                            <span class="absolute inset-0" aria-hidden="true"></span>
                                            {{ $collection->name }}
                                        </a>
                                    </h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        @if($collection->event_date){{ $collection->event_date->format('j M Y') }} · @endif{{ $collection->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $collection->photos_count) }} in {{ $collection->sets_count }} {{ \Illuminate\Support\Str::plural('set', $collection->sets_count) }}
                                    </p>
                                </div>
                                @include('collections.partials.status-badge', ['status' => $collection->status])
                            </div>
                            @if($collection->private || $collection->watermarked || $collection->hide_from_portfolio)
                                <div class="flex flex-wrap gap-1.5">
                                    @if($collection->private)<x-ui.badge tone="brand" icon="lock-closed">Private</x-ui.badge>@endif
                                    @if($collection->watermarked)<x-ui.badge tone="blue" icon="shield-check">Watermarked</x-ui.badge>@endif
                                    @if($collection->hide_from_portfolio && ! $collection->private)<x-ui.badge icon="eye-slash">Not on portfolio</x-ui.badge>@endif
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @elseif($counts['all'] === 0)
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="rectangle-stack" title="Create your first collection"
                                  description="Add a collection for a shoot or event, upload your photos, then share a link with your client.">
                    <x-ui.button icon="plus" :href="route('collections.create')">New collection</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="funnel" title="No {{ strtolower($filters[$filter]) }} collections">
                    <x-ui.button variant="secondary" :href="route('collections.index')">Show all collections</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
