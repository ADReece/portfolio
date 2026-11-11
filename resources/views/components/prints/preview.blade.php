{{--
    A print product's preview: its uploaded image, else our own simple drawing
    for its category (Prodigi's API has no product images to use).
    <x-prints.preview :product="$product" class="size-10" />
--}}
@props(['product', 'alt' => ''])
@php
    $url = $product->imageUrl();
    $kind = match ($product->category) {
        'Canvas' => 'canvas',
        'Framed' => 'framed',
        'Fine art prints' => 'fineart',
        'Wall art' => 'wallart',
        'Photo books' => 'book',
        'Cards' => 'card',
        'Gifts' => 'gift',
        default => 'print',
    };
@endphp
@if($url)
    <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->class('shrink-0 rounded-md object-cover') }}>
@else
    <svg viewBox="0 0 48 48" @if($alt === '') aria-hidden="true" @else role="img" aria-label="{{ $alt }}" @endif
         {{ $attributes->class('shrink-0 rounded-md bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400') }}>
        @switch($kind)
            @case('canvas')
                <path d="M13 11h20l3 3v23H16l-3-3z" fill="currentColor" opacity=".25"/>
                <rect x="13" y="11" width="20" height="23" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path d="M33 11l3 3v23H16l-3-3" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <path d="M15 30l6-7 4 4 3-3 5 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                @break
            @case('framed')
                <rect x="10" y="9" width="28" height="30" fill="none" stroke="currentColor" stroke-width="3"/>
                <rect x="16" y="15" width="16" height="18" fill="currentColor" opacity=".25"/>
                <path d="M16 30l5-6 3 3 3-3 5 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                @break
            @case('fineart')
                <rect x="12" y="8" width="24" height="32" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <rect x="16" y="12" width="16" height="18" fill="currentColor" opacity=".25"/>
                <path d="M16 27l5-6 3 3 3-3 5 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M18 35h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                @break
            @case('wallart')
                <rect x="9" y="13" width="30" height="22" rx="2" fill="currentColor" opacity=".25" stroke="currentColor" stroke-width="1.5"/>
                <path d="M12 31l8-9 5 5 4-4 7 8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M24 13V8" stroke="currentColor" stroke-width="1.5"/>
                @break
            @case('book')
                <path d="M10 12c5-2 10-2 14 1 4-3 9-3 14-1v24c-5-2-10-2-14 1-4-3-9-3-14-1z" fill="currentColor" opacity=".25" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M24 13v24" stroke="currentColor" stroke-width="1.5"/>
                @break
            @case('card')
                <path d="M10 14l14-4v28l-14 4z" fill="currentColor" opacity=".25" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M24 10l14 4v28l-14-4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                @break
            @case('gift')
                <path d="M12 14h20v20a4 4 0 0 1-4 4H16a4 4 0 0 1-4-4z" fill="currentColor" opacity=".25" stroke="currentColor" stroke-width="1.5"/>
                <path d="M32 18h3a4 4 0 0 1 0 8h-3" fill="none" stroke="currentColor" stroke-width="1.5"/>
                @break
            @default
                <rect x="9" y="11" width="30" height="26" rx="1" fill="currentColor" opacity=".25" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="31" cy="18" r="2.5" fill="currentColor"/>
                <path d="M12 33l8-9 5 5 4-4 7 8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        @endswitch
    </svg>
@endif
