{{--
    A self-hosted homepage photo (App\Support\MarketingImages) as a responsive
    <picture>: AVIF, then WebP, then one JPEG for old browsers, with the real width and height so
    nothing shifts while it loads. Below-the-fold photos load lazily; pass
    :eager="true" for the hero (the LCP image).
    If the photo isn't in the manifest, a plain block of the same shape renders.
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
        <img src="{{ $images->fallback($key) }}"
             width="{{ $image['width'] }}" height="{{ $image['height'] }}" alt="{{ $alt }}"
             @if($eager) fetchpriority="high" decoding="async" @else loading="lazy" decoding="async" @endif
             style="background-color: {{ $image['colour'] ?? '#d6d3d1' }}; object-position: {{ $image['position'] ?? 'center' }};{{ $ratio ? ' aspect-ratio: '.$ratio.';' : '' }}"
             class="{{ $imgClass }}">
    </picture>
@else
    <div {{ $attributes->merge(['class' => 'bg-stone-300 dark:bg-stone-800']) }} role="img" aria-label="{{ $alt }}"
         @if($ratio) style="aspect-ratio: {{ $ratio }}" @endif></div>
@endif
