{{--
    A self-hosted homepage photo (App\Support\MarketingImages) as a responsive
    <picture>: AVIF, then WebP, then one JPEG for old browsers, with the real
    width and height so nothing shifts while it loads. Below-the-fold photos
    load lazily; pass :eager="true" for the hero (the LCP image).
    If the photo isn't in the manifest, a plain dark block renders instead.
--}}
@props(['key', 'sizes' => '100vw', 'eager' => false, 'alt' => null, 'imgClass' => 'size-full object-cover', 'ratio' => null])
@php
    $images = app(\App\Support\MarketingImages::class);
    $image = $images->get($key);
    $alt ??= $image['alt'] ?? '';
@endphp
@if($image)
    <picture {{ $attributes }}>
        <source type="image/avif" srcset="{{ $images->srcset($key, 'avif') }}" sizes="{{ $sizes }}">
        <source type="image/webp" srcset="{{ $images->srcset($key, 'webp') }}" sizes="{{ $sizes }}">
        <img src="{{ $images->fallback($key) }}" width="{{ $image['width'] }}" height="{{ $image['height'] }}" alt="{{ $alt }}"
             {{ $eager ? 'fetchpriority=high' : 'loading=lazy' }} decoding="async"
             style="background-color: {{ $image['colour'] ?? '#57534e' }}; object-position: {{ $image['position'] ?? 'center' }};{{ $ratio ? ' aspect-ratio: '.$ratio.';' : '' }}"
             class="{{ $imgClass }}">
    </picture>
@elseif($alt !== '')
    <div {{ $attributes->merge(['class' => 'bg-stone-600']) }} style="aspect-ratio: {{ $ratio ?? '4 / 3' }}" role="img" aria-label="{{ $alt }}"></div>
@else
    {{-- Same shape, dark enough for any text laid over it. --}}
    <div {{ $attributes->merge(['class' => 'bg-stone-600']) }} style="aspect-ratio: {{ $ratio ?? '4 / 3' }}" aria-hidden="true"></div>
@endif
