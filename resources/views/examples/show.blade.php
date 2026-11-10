@php
    $images = app(\App\Support\MarketingImages::class);
    $keys = collect(range(1, $showcase['photos']))->map(fn ($i) => "{$slug}-{$i}")->filter(fn ($k) => $images->get($k))->values();
    $title = 'Sample '.strtolower($showcase['title']).' portfolio · '.config('app.name');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="A sample {{ strtolower($showcase['title']) }} portfolio made with {{ config('app.name') }}. {{ $showcase['blurb'] }}">
    <link rel="canonical" href="{{ route('examples.show', $slug) }}">
    @if($keys->isNotEmpty())
        <link rel="preload" as="image" type="image/avif" fetchpriority="high" imagesrcset="{{ $images->srcset($keys->first(), 'avif') }}" imagesizes="100vw">
    @endif
    @vite(['resources/css/app.css', 'resources/css/marketing.css', 'resources/js/marketing.js'])
</head>
<body class="bg-white font-body text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
    <p class="bg-stone-950 px-4 py-2.5 text-center text-sm text-stone-200">
        This is a sample portfolio made with {{ config('app.name') }}.
        <a href="{{ route('register') }}" class="ml-1 font-semibold text-white underline underline-offset-4 hover:text-amber-200">Make yours</a>
        <span aria-hidden="true">·</span>
        <a href="{{ url('/') }}" class="font-semibold text-white underline underline-offset-4 hover:text-amber-200">Back to {{ config('app.name') }}</a>
    </p>

    <header class="mx-auto flex max-w-7xl flex-wrap items-end justify-between gap-4 px-4 pt-10 pb-8 sm:px-6 lg:px-8">
        <div>
            <p class="text-sm tracking-widest text-stone-500 uppercase dark:text-stone-400">Sample portfolio</p>
            <h1 class="mt-1 font-display text-4xl font-medium tracking-tight sm:text-5xl">{{ $showcase['title'] }}</h1>
            <p class="mt-2 text-stone-600 dark:text-stone-400">{{ $showcase['blurb'] }}</p>
        </div>
        @isset($showcase['coming_soon'])
            <p class="rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-900 dark:bg-amber-950 dark:text-amber-200">{{ $showcase['coming_soon'] }}</p>
        @endisset
    </header>

    <main id="main" class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8" x-data="lightbox({{ $keys->count() }})" x-on:keydown.window="key($event)">
        <h2 class="sr-only">Photos</h2>
        <ul role="list" class="columns-1 gap-3 sm:columns-2 lg:columns-3 [&>li]:mb-3">
            @foreach($keys as $i => $key)
                <li class="break-inside-avoid">
                    <button type="button" x-ref="thumb{{ $i }}" x-on:click="show({{ $i }})" class="block w-full overflow-hidden focus:outline-hidden focus-visible:ring-4 focus-visible:ring-amber-400">
                        <span class="sr-only">Open photo {{ $i + 1 }} of {{ $keys->count() }}</span>
                        <x-marketing.photo :key="$key" :eager="$i === 0" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" imgClass="h-auto w-full transition duration-300 hover:opacity-90" />
                    </button>
                </li>
            @endforeach
        </ul>
        <div x-show="open" x-cloak x-trap.noscroll="open" role="dialog" aria-modal="true" aria-label="Photo viewer"
             class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/95 p-4" x-on:click.self="close()" x-on:keydown.escape="close()">
            @foreach($keys as $i => $key)
                <div x-show="index === {{ $i }}" class="max-h-full max-w-6xl">
                    <x-marketing.photo :key="$key" sizes="100vw" imgClass="max-h-[85svh] w-auto object-contain" />
                </div>
            @endforeach
            <p class="absolute top-5 left-5 text-sm text-stone-300" aria-live="polite"><span x-text="index + 1"></span> / {{ $keys->count() }}</p>
            <button type="button" x-on:click="close()" class="absolute top-3 right-3 rounded-full p-2 text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"><x-ui.icon name="x-mark" class="size-7" label="Close" /></button>
            <button type="button" x-on:click="prev()" class="absolute left-3 rounded-full bg-stone-950/50 p-2 text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"><x-ui.icon name="chevron-left" class="size-7" label="Previous photo" /></button>
            <button type="button" x-on:click="next()" class="absolute right-3 rounded-full bg-stone-950/50 p-2 text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white"><x-ui.icon name="chevron-right" class="size-7" label="Next photo" /></button>
        </div>

        <nav aria-labelledby="more-samples" class="mt-20 border-t border-stone-200 pt-10 dark:border-stone-800">
            <h2 id="more-samples" class="font-display text-2xl">More sample portfolios</h2>
            <ul role="list" class="mt-4 flex flex-wrap gap-3">
                @foreach($others as $otherSlug => $other)
                    <li><a href="{{ route('examples.show', $otherSlug) }}" class="inline-block rounded-full px-4 py-2 text-sm font-medium ring-1 ring-stone-300 hover:bg-stone-100 dark:ring-stone-700 dark:hover:bg-stone-900">{{ $other['title'] }}</a></li>
                @endforeach
            </ul>
        </nav>
    </main>

    @include('partials.marketing-footer')
    @livewireScriptConfig
    @include('partials.chatmesh')
</body>
</html>
