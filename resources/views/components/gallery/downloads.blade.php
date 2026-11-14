@props(['collection' => null, 'scrollTo' => 'gallery'])
@php
    // The toolbar above a gallery grid: choose photos to download (or buy),
    // and download a whole collection or one set at once.
    $viewer = auth()->user();
    $isOwner = $collection && $viewer && $viewer->id === $collection->user_id;
    $free = ! $collection || \App\Downloads\DigitalSales::freeDownloadsAllowed(request(), $collection);
    $buyUrl = $collection && ! $free && \App\Downloads\DigitalSales::onSale($collection) ? route('download-purchase.show', $collection) : null;

    $sets = collect();
    $total = 0;
    if ($collection) {
        $sets = $collection->sets()
            ->when(! $collection->private && ! $isOwner, fn ($q) => $q->where('hide_from_portfolio', false))
            ->withCount(['photos' => fn ($q) => $q->ready()->when(! $collection->private && ! $isOwner, fn ($q) => $q->where('private', false)->where('hide_from_portfolio', false))])
            ->orderBy('sort_order')->orderBy('created_at')->get()
            ->filter(fn ($set) => $set->photos_count > 0)->values();
        $total = $sets->sum('photos_count');
    }
    $button = 'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1 ring-current/25 hover:bg-black/5 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-current dark:hover:bg-white/10';
@endphp
@if($free || $buyUrl)
    <div data-gallery-select data-collection-id="{{ $collection?->id }}" data-total="{{ $total }}" data-scroll-to="{{ $scrollTo }}"
         @if($buyUrl) data-buy-url="{{ $buyUrl }}" @endif
         {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-center justify-end gap-2']) }}>
        <button type="button" data-select-mode aria-pressed="false" class="{{ $button }} aria-pressed:bg-black/10 dark:aria-pressed:bg-white/15">
            <x-ui.icon name="check-circle" class="size-5" /> {{ $buyUrl ? 'Choose photos to buy' : 'Select photos' }}
        </button>

        @if($collection && $free && $total > 0)
            @if($sets->count() > 1)
                <details class="relative">
                    <summary class="{{ $button }} cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        <x-ui.icon name="arrow-down-tray" class="size-5" /> Download all <x-ui.icon name="chevron-down" variant="m" class="size-4" />
                    </summary>
                    <div class="portfolio-neutral absolute right-0 z-30 mt-2 w-72 max-w-[calc(100vw-2rem)] overflow-hidden rounded-card bg-white py-1 text-sm text-gray-900 shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700">
                        <button type="button" data-download='@json(['collection_id' => $collection->id])' class="flex w-full justify-between gap-3 px-4 py-2 text-left font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                            <span>Every photo</span><span class="text-gray-500">{{ $total }}</span>
                        </button>
                        @foreach($sets as $set)
                            <button type="button" data-download='@json(['collection_id' => $collection->id, 'set_id' => $set->id])' class="flex w-full justify-between gap-3 px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800">
                                <span class="truncate">{{ $set->name }}</span><span class="text-gray-500">{{ $set->photos_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </details>
            @else
                <button type="button" data-download='@json(['collection_id' => $collection->id])' class="{{ $button }}">
                    <x-ui.icon name="arrow-down-tray" class="size-5" /> Download all ({{ $total }})
                </button>
            @endif
        @endif
    </div>
@endif
