@php
    /** @var \App\Seo\HomePage $page */
    $images = app(\App\Support\MarketingImages::class);
    $offerActive = $page->offerActive();
    $fromPrice = $page->fromPrice();
    $paidPlans = $page->paidPlans();
    $ogImage = $images->fallback('hero');
    $title = config('app.name').' · Portfolio websites and client galleries for photographers';
    $primaryHref = $starterPlan
        ? route('register', ['plan' => $starterPlan->id, 'interval' => 'month'])
        : route('register');
    $primaryLabel = $offerActive && $fromPrice ? "Start for {$currencySymbol}{$fromPrice}/month" : 'Start free';
    $sampleHref = route('examples.show', array_key_first($showcases));
    // The live demo portfolio (php artisan portfolio:demo-seed), once it exists.
    $demoUsername = \Illuminate\Support\Facades\Cache::remember('demo:username', 600, fn () => \App\Models\User::query()->where('is_demo', true)->value('username'));
    $galleryKeys = ['weddings-1', 'landscapes-1', 'portraits-1', 'events-1', 'landscapes-2', 'weddings-2', 'portraits-2', 'film-1', 'landscapes-3'];
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $page->metaDescription() }}">
    <link rel="canonical" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $page->metaDescription() }}">
    <meta property="og:url" content="{{ url('/') }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $page->metaDescription() }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    @if($images->get('hero'))
        {{-- The hero photo is the largest thing on screen: fetch it first. --}}
        <link rel="preload" as="image" type="image/avif" fetchpriority="high"
              imagesrcset="{{ $images->srcset('hero', 'avif') }}" imagesizes="100vw">
    @endif

    @foreach($page->structuredData(url('/'), $ogImage) as $block)
        <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach

    @vite(['resources/css/app.css', 'resources/css/marketing.css', 'resources/js/marketing.js'])
</head>
<body class="bg-stone-50 font-body text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-stone-900 focus:shadow-lg">
        Skip to content
    </a>

    {{-- Header: the launch line, then a minimal nav over the hero photo. --}}
    <header x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="absolute inset-x-0 top-0 z-40 text-white">
        @if($offerActive)
            <p class="bg-amber-300 px-4 py-2 text-center text-sm font-medium text-stone-950">
                <strong class="font-semibold">{{ $page->offerLine() }}.</strong>
                <a href="#launch-offer" class="ml-1 underline underline-offset-4 hover:no-underline">See the offer</a>
            </p>
        @endif
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-2 rounded-md focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">
                <x-application-logo class="h-7 w-auto text-white" aria-hidden="true" />
                <span class="font-display text-xl font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <nav aria-label="Main" class="hidden items-center gap-6 text-sm font-medium md:flex">
                <a href="#showcases" class="hover:text-amber-200">Examples</a>
                <a href="#features" class="hover:text-amber-200">Features</a>
                <a href="#pricing" class="hover:text-amber-200">Pricing</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full bg-white px-4 py-2 text-stone-950 hover:bg-amber-100">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hover:text-amber-200">Log in</a>
                    <a href="{{ $primaryHref }}" class="rounded-full bg-white px-4 py-2 text-stone-950 hover:bg-amber-100">{{ $primaryLabel }}</a>
                @endauth
            </nav>
            <button type="button" class="rounded-md p-2 hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white md:hidden"
                    x-on:click="open = !open" :aria-expanded="open" aria-controls="home-menu">
                <x-ui.icon name="bars-3" class="size-6" x-show="!open" label="Open menu" />
                <x-ui.icon name="x-mark" class="size-6" x-show="open" x-cloak label="Close menu" />
            </button>
        </div>
        <nav id="home-menu" aria-label="Main" x-show="open" x-cloak class="mx-4 rounded-xl bg-stone-950/95 p-3 text-base shadow-xl backdrop-blur md:hidden">
            <a href="#showcases" x-on:click="open = false" class="block rounded-md px-3 py-2 hover:bg-white/10">Examples</a>
            <a href="#features" x-on:click="open = false" class="block rounded-md px-3 py-2 hover:bg-white/10">Features</a>
            <a href="#pricing" x-on:click="open = false" class="block rounded-md px-3 py-2 hover:bg-white/10">Pricing</a>
            @auth
                <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 hover:bg-white/10">Go to dashboard</a>
            @else
                <a href="{{ route('login') }}" class="block rounded-md px-3 py-2 hover:bg-white/10">Log in</a>
            @endauth
        </nav>
    </header>

    <main id="main" tabindex="-1" class="focus:outline-hidden">
        {{-- Hero: a full-bleed photo, as a portfolio would open. --}}
        <section class="relative isolate flex min-h-[92svh] items-end overflow-hidden bg-stone-900 text-white">
            <x-marketing.photo key="hero" :eager="true" sizes="100vw" class="absolute inset-0 -z-10" alt="" />
            <div class="absolute inset-0 -z-10 bg-linear-to-t from-stone-950/90 via-stone-950/35 to-stone-950/40" aria-hidden="true"></div>
            <div class="mx-auto w-full max-w-7xl px-4 pt-40 pb-14 sm:px-6 sm:pb-20 lg:px-8">
                <div class="max-w-3xl">
                    <h1 class="font-display text-5xl leading-[1.05] font-medium tracking-tight text-balance sm:text-6xl lg:text-7xl">
                        Your work, shown the way you see it.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg text-stone-200 sm:text-xl">
                        Portfolio websites and private client galleries for photographers and videographers. Big images, no clutter, live in minutes.
                    </p>
                    @if($offerActive)
                        <p class="mt-6 hidden flex-wrap items-center gap-x-2 rounded-full bg-amber-300/95 sm:inline-flex px-4 py-1.5 text-sm font-semibold text-stone-950">
                            {{ $page->offerLine() }}
                        </p>
                    @endif
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ $primaryHref }}" class="rounded-full bg-white px-7 py-3.5 text-base font-semibold text-stone-950 shadow-lg hover:bg-amber-100 focus:outline-hidden focus-visible:ring-4 focus-visible:ring-amber-300">
                            {{ $primaryLabel }}
                        </a>
                        @if($demoUsername)
                            <a href="{{ route('profile.view', $demoUsername) }}" class="rounded-full px-5 py-3.5 text-base font-semibold text-white ring-1 ring-white/60 hover:bg-white/10 focus:outline-hidden focus-visible:ring-4 focus-visible:ring-white">
                                See a live demo
                            </a>
                        @else
                            <a href="{{ $sampleHref }}" class="rounded-full px-5 py-3.5 text-base font-semibold text-white ring-1 ring-white/60 hover:bg-white/10 focus:outline-hidden focus-visible:ring-4 focus-visible:ring-white">
                                See a sample portfolio
                            </a>
                        @endif
                    </div>
                    <p class="mt-4 text-sm text-stone-300">
                        @if($offerActive && $starterPlan)
                            {{ $starterPlan->name }} plan, then {{ $currencySymbol }}{{ $starterPlan->priceFor('month') }}/month. Or <a href="{{ route('register') }}" class="underline underline-offset-4 hover:text-white">start free</a>, no card needed.
                        @else
                            Free plan, no card needed.
                        @endif
                    </p>
                </div>
            </div>
        </section>

        {{-- Launch offer --}}
        @if($offerActive && $paidPlans->isNotEmpty())
            <section id="launch-offer" aria-labelledby="launch-offer-heading" class="scroll-mt-8 bg-stone-950 text-white">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:items-center lg:px-8">
                    <div>
                        <p class="text-sm font-semibold tracking-widest text-amber-300 uppercase">For our first photographers</p>
                        <h2 id="launch-offer-heading" class="mt-3 font-display text-4xl font-medium tracking-tight sm:text-5xl">
                            Launch offer: half price for your first {{ $offer->months() }} months
                        </h2>
                        <p class="mt-4 max-w-xl text-lg text-stone-300">
                            Sign up for a monthly plan by {{ $offer->endsLabel() }} and pay {{ $offer->percentOff() }}% less for your first {{ $offer->months() }} months. After that it's the normal monthly price, and you can cancel any time.
                        </p>
                        <p class="mt-3 text-sm text-stone-400">
                            Offer ends {{ $offer->endsLabel() }}<span x-data="countdown(@js($offer->endsAt()->toIso8601String()))" x-show="text" x-cloak>: <span x-text="text"></span></span>.
                        </p>
                    </div>
                    <ul role="list" class="grid gap-4 sm:grid-cols-2">
                        @foreach($paidPlans as $plan)
                            <li class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/15">
                                <h3 class="font-display text-2xl">{{ $plan->name }}</h3>
                                <p class="mt-3 flex flex-wrap items-baseline gap-x-2">
                                    <span class="text-lg text-stone-400 line-through tabular-nums"><span class="sr-only">Usually </span>{{ $currencySymbol }}{{ $plan->priceFor('month') }}</span>
                                    <span class="text-4xl font-semibold tabular-nums">{{ $currencySymbol }}{{ $offer->discountedMonthlyPrice($plan) }}</span>
                                    <span class="text-sm text-stone-300">/ month</span>
                                </p>
                                <p class="mt-1 text-sm text-amber-200">for your first {{ $offer->months() }} months, then {{ $currencySymbol }}{{ $plan->priceFor('month') }}</p>
                                <a href="{{ route('register', ['plan' => $plan->id, 'interval' => 'month']) }}"
                                   class="mt-5 inline-block text-sm font-semibold text-white underline underline-offset-4 hover:text-amber-200">
                                    Choose {{ $plan->name }}<span class="sr-only"> at the launch price</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        {{-- Showcases --}}
        <section id="showcases" aria-labelledby="showcases-heading" class="scroll-mt-8 py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 id="showcases-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">Made for every kind of shoot</h2>
                    <p class="mt-4 text-lg text-stone-600 dark:text-stone-400">Open a sample portfolio to see how your work could look.</p>
                </div>
                <ul role="list" class="mt-12 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($showcases as $slug => $showcase)
                        <li @class(['group relative', 'lg:col-span-2' => $loop->first])>
                            <div class="grid grid-cols-3 gap-1.5 overflow-hidden rounded-xl">
                                <x-marketing.photo :key="$slug.'-1'" class="col-span-2 row-span-2" ratio="1 / 1"
                                                   sizes="(min-width: 1024px) {{ $loop->first ? '40vw' : '20vw' }}, (min-width: 640px) 33vw, 66vw"
                                                   imgClass="size-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                                <x-marketing.photo :key="$slug.'-2'" ratio="1 / 1" sizes="(min-width: 1024px) 15vw, 33vw" />
                                <x-marketing.photo :key="$slug.'-3'" ratio="1 / 1" sizes="(min-width: 1024px) 15vw, 33vw" />
                            </div>
                            <h3 class="mt-4 flex flex-wrap items-center gap-2 font-display text-2xl">
                                <a href="{{ route('examples.show', $slug) }}" class="focus:outline-hidden focus-visible:underline">
                                    <span class="absolute inset-0" aria-hidden="true"></span>{{ $showcase['title'] }}
                                </a>
                                @isset($showcase['coming_soon'])<span class="rounded-full bg-amber-200 px-2.5 py-0.5 font-body text-xs font-semibold text-amber-950">Video coming soon</span>@endisset
                            </h3>
                            <p class="mt-1 text-stone-600 dark:text-stone-400">{{ $showcase['blurb'] }}</p>
                            <p class="mt-2 text-sm font-semibold text-stone-900 dark:text-white">View sample portfolio <span aria-hidden="true">→</span></p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Feature demos --}}
        <div id="features" class="scroll-mt-8 space-y-24 bg-white py-20 sm:space-y-32 sm:py-28 dark:bg-stone-900">
            {{-- 1. Gallery with lightbox --}}
            <section aria-labelledby="gallery-heading" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 id="gallery-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">A portfolio that gets out of the way</h2>
                    <p class="mt-4 text-lg text-stone-600 dark:text-stone-400">Your photos fill the screen in a clean grid that works on any device. Click any photo to open it full size, just as your visitors will.</p>
                </div>
                <div x-data="lightbox({{ count($galleryKeys) }})" x-on:keydown.window="key($event)" class="mt-10">
                    <ul role="list" class="columns-2 gap-3 md:columns-3 [&>li]:mb-3">
                        @foreach($galleryKeys as $i => $key)
                            <li class="break-inside-avoid">
                                <button type="button" x-ref="thumb{{ $i }}" x-on:click="show({{ $i }})"
                                        class="block w-full overflow-hidden rounded-lg focus:outline-hidden focus-visible:ring-4 focus-visible:ring-amber-400">
                                    <span class="sr-only">Open photo {{ $i + 1 }} of {{ count($galleryKeys) }}</span>
                                    <x-marketing.photo :key="$key" sizes="(min-width: 768px) 30vw, 48vw" imgClass="h-auto w-full transition duration-300 hover:opacity-90" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <div x-show="open" x-cloak x-trap.noscroll="open" role="dialog" aria-modal="true" aria-label="Photo viewer"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/95 p-4" x-on:click.self="close()" x-on:keydown.escape="close()">
                        @foreach($galleryKeys as $i => $key)
                            <div x-show="index === {{ $i }}" class="max-h-full max-w-6xl">
                                <x-marketing.photo :key="$key" sizes="100vw" imgClass="max-h-[85svh] w-auto object-contain" />
                            </div>
                        @endforeach
                        <p class="absolute top-5 left-5 text-sm text-stone-300" aria-live="polite"><span x-text="index + 1"></span> / {{ count($galleryKeys) }}</p>
                        <button type="button" x-on:click="close()" class="absolute top-3 right-3 rounded-full p-2 text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">
                            <x-ui.icon name="x-mark" class="size-7" label="Close" />
                        </button>
                        <button type="button" x-on:click="prev()" class="absolute left-3 rounded-full bg-stone-950/50 p-2 text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">
                            <x-ui.icon name="chevron-left" class="size-7" label="Previous photo" />
                        </button>
                        <button type="button" x-on:click="next()" class="absolute right-3 rounded-full bg-stone-950/50 p-2 text-white hover:bg-white/10 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">
                            <x-ui.icon name="chevron-right" class="size-7" label="Next photo" />
                        </button>
                    </div>
                </div>
            </section>

            {{-- 2. Private client galleries --}}
            <section aria-labelledby="private-heading" class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <h2 id="private-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">Private galleries for every client</h2>
                    <p class="mt-4 text-lg text-stone-600 dark:text-stone-400">Deliver each shoot in its own gallery, locked with a password. Only the people you share it with can see it, and it never shows on your public portfolio.</p>
                    <ul role="list" class="mt-6 space-y-2 text-stone-700 dark:text-stone-300">
                        <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-1 size-4 shrink-0 text-green-700 dark:text-green-400" />Password-protected galleries</li>
                        <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-1 size-4 shrink-0 text-green-700 dark:text-green-400" />Hidden from your public portfolio</li>
                        <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-1 size-4 shrink-0 text-green-700 dark:text-green-400" />Clients heart their favourites and send you the list, ready for Lightroom</li>
                    </ul>
                </div>
                <div class="rounded-2xl bg-stone-100 p-4 shadow-sm ring-1 ring-stone-200 sm:p-6 dark:bg-stone-800 dark:ring-stone-700"
                     x-data="{ unlocked: false, picks: [] }">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-display text-xl">Spring wedding · Client gallery</p>
                        <span class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-400"><x-ui.icon name="lock-closed" variant="m" class="size-4" />Private</span>
                    </div>
                    <div class="relative mt-4">
                        <ul role="list" class="grid grid-cols-2 gap-2" :class="unlocked ? '' : 'blur-md select-none'" :aria-hidden="!unlocked">
                            @foreach(['weddings-3', 'weddings-4', 'weddings-5', 'weddings-6'] as $i => $key)
                                <li class="relative overflow-hidden rounded-lg">
                                    <x-marketing.photo :key="$key" ratio="4 / 3" sizes="(min-width: 1024px) 22vw, 45vw" />
                                    <button type="button" x-show="unlocked" x-cloak
                                            x-on:click="picks.includes({{ $i }}) ? picks = picks.filter(p => p !== {{ $i }}) : picks.push({{ $i }})"
                                            :aria-pressed="picks.includes({{ $i }})"
                                            class="absolute top-2 right-2 rounded-full bg-white/90 p-1.5 text-stone-900 shadow focus:outline-hidden focus-visible:ring-2 focus-visible:ring-amber-400">
                                        <x-ui.icon name="heart" variant="m" class="size-5" x-bind:class="picks.includes({{ $i }}) ? 'text-rose-600' : 'text-stone-400'" label="Favourite photo {{ $i + 1 }}" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div x-show="!unlocked" class="absolute inset-0 flex items-center justify-center">
                            <form x-on:submit.prevent="unlocked = true" class="w-full max-w-xs rounded-xl bg-white p-5 text-center shadow-xl dark:bg-stone-900">
                                <x-ui.icon name="lock-closed" class="mx-auto size-7 text-stone-500" />
                                <p class="mt-2 font-semibold">This gallery is private</p>
                                <label for="demo-password" class="mt-3 block text-left text-sm text-stone-600 dark:text-stone-400">Password (try anything)</label>
                                <input id="demo-password" type="password" autocomplete="off" class="mt-1 w-full rounded-md border-stone-300 text-sm dark:border-stone-600 dark:bg-stone-800">
                                <button type="submit" class="mt-3 w-full rounded-full bg-stone-950 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 dark:bg-white dark:text-stone-950">Open gallery</button>
                            </form>
                        </div>
                    </div>
                    <p x-show="unlocked" x-cloak class="mt-3 text-sm text-stone-600 dark:text-stone-400" aria-live="polite">
                        <span x-text="picks.length"></span> favourites picked. In a real gallery, clients send their picks to you.
                    </p>
                </div>
            </section>

            {{-- 3. Watermark before/after --}}
            <section aria-labelledby="watermark-heading" class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="lg:order-2">
                    <h2 id="watermark-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">Your watermark, your way</h2>
                    <p class="mt-4 text-lg text-stone-600 dark:text-stone-400">Protect proofs and previews with your own watermark. Drag the slider to compare.</p>
                </div>
                <figure x-data="{ split: 50 }" class="lg:order-1">
                    <div class="relative overflow-hidden rounded-2xl">
                        <x-marketing.photo key="landscapes-4" ratio="3 / 2" sizes="(min-width: 1024px) 45vw, 100vw" />
                        <div class="absolute inset-0" :style="'clip-path: inset(0 0 0 ' + split + '%)'" style="clip-path: inset(0 0 0 50%)" aria-hidden="true">
                            <x-marketing.photo key="landscapes-4" ratio="3 / 2" sizes="(min-width: 1024px) 45vw, 100vw" alt="" />
                            <div class="absolute inset-0 grid grid-cols-2 grid-rows-3 place-items-center overflow-hidden">
                                @for($i = 0; $i < 6; $i++)
                                    <p class="-rotate-12 font-display text-xl font-semibold whitespace-nowrap text-white/60 sm:text-3xl" style="text-shadow: 0 1px 2px rgb(0 0 0 / .35)">© Your Studio</p>
                                @endfor
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-y-0 w-0.5 bg-white shadow" :style="'left: ' + split + '%'" style="left: 50%" aria-hidden="true"></div>
                    </div>
                    <label for="watermark-split" class="sr-only">Compare without and with watermark</label>
                    <input id="watermark-split" type="range" min="0" max="100" x-model="split" class="mt-4 w-full accent-stone-900 dark:accent-white">
                    <figcaption class="mt-1 flex justify-between text-sm text-stone-600 dark:text-stone-400"><span>Original</span><span>With watermark</span></figcaption>
                </figure>
            </section>

            {{-- 4. Branding and domain, video, prints --}}
            <section aria-labelledby="more-heading" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 id="more-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">Your brand, front and centre</h2>
                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    {{-- Branding / custom domain --}}
                    <article class="overflow-hidden rounded-2xl bg-stone-100 ring-1 ring-stone-200 dark:bg-stone-800 dark:ring-stone-700">
                        <div class="m-4 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-stone-950" aria-hidden="true">
                            <div class="flex items-center gap-2 border-b border-stone-200 px-3 py-2 dark:border-stone-800">
                                <span class="flex gap-1"><span class="size-2.5 rounded-full bg-stone-300"></span><span class="size-2.5 rounded-full bg-stone-300"></span><span class="size-2.5 rounded-full bg-stone-300"></span></span>
                                <span class="flex-1 truncate rounded-md bg-stone-100 px-3 py-1 text-xs text-stone-600 dark:bg-stone-800 dark:text-stone-300"><x-ui.icon name="lock-closed" variant="m" class="inline size-3" /> yourname.com</span>
                            </div>
                            <div class="relative">
                                <x-marketing.photo key="portraits-3" ratio="16 / 10" sizes="(min-width: 1024px) 28vw, 90vw" alt="" />
                                <p class="absolute top-3 left-3 font-display text-lg text-white drop-shadow">Your Studio</p>
                            </div>
                        </div>
                        <div class="px-6 pb-6">
                            <h3 class="font-display text-2xl">Your logo and your look</h3>
                            <p class="mt-2 text-stone-600 dark:text-stone-400">Add your logo and choose a template that suits your work.</p>
                            <p class="mt-3 text-sm"><span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900 dark:bg-amber-950 dark:text-amber-200">Coming soon</span> Use your own domain, like yourname.com.</p>
                        </div>
                    </article>

                    {{-- Video --}}
                    <article class="overflow-hidden rounded-2xl bg-stone-100 ring-1 ring-stone-200 dark:bg-stone-800 dark:ring-stone-700">
                        <div class="relative m-4 overflow-hidden rounded-lg bg-stone-950" aria-hidden="true">
                            <x-marketing.photo key="film-2" ratio="16 / 9" sizes="(min-width: 1024px) 28vw, 90vw" alt="" />
                            <div class="absolute inset-0 grid place-items-center bg-stone-950/30">
                                <span class="grid size-14 place-items-center rounded-full bg-white/90 text-stone-950"><x-ui.icon name="play" variant="s" class="size-7" /></span>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 bg-linear-to-t from-stone-950/80 px-3 py-2 text-xs text-white">
                                <span>0:00</span><span class="h-1 flex-1 rounded-full bg-white/30"><span class="block h-1 w-1/3 rounded-full bg-white"></span></span><span>2:14</span>
                            </div>
                        </div>
                        <div class="px-6 pb-6">
                            <h3 class="flex flex-wrap items-center gap-2 font-display text-2xl">Video galleries</h3>
                            <p class="mt-2 text-stone-600 dark:text-stone-400">Films and highlight reels next to your photos, on the Videographer plan.</p>
                        </div>
                    </article>

                    {{-- Prints --}}
                    <article class="overflow-hidden rounded-2xl bg-stone-100 ring-1 ring-stone-200 dark:bg-stone-800 dark:ring-stone-700">
                        <div class="m-4 grid place-items-center rounded-lg bg-stone-200 p-8 dark:bg-stone-700" aria-hidden="true">
                            <div class="w-3/4 bg-white p-3 shadow-xl">
                                <x-marketing.photo key="landscapes-5" ratio="4 / 5" sizes="(min-width: 1024px) 18vw, 60vw" alt="" />
                            </div>
                        </div>
                        <div class="px-6 pb-6">
                            <h3 class="flex flex-wrap items-center gap-2 font-display text-2xl">Sell prints <span class="rounded-full bg-amber-100 px-2 py-0.5 font-body text-xs font-semibold text-amber-900 dark:bg-amber-950 dark:text-amber-200">Coming soon</span></h3>
                            <p class="mt-2 text-stone-600 dark:text-stone-400">Clients will order prints from your galleries. We print and deliver, and you're paid your price minus the print cost and our fee.</p>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        {{-- Pricing --}}
        <section id="pricing" aria-labelledby="pricing-heading" class="scroll-mt-8 py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 id="pricing-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">Less than half the price of the big gallery platforms</h2>
                    <p class="mt-4 text-lg text-stone-600 dark:text-stone-400">Everything you need to show and deliver your work, without paying for what you don't. Start free and upgrade when you're ready.</p>
                </div>
                <ul role="list" class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach($plans as $plan)
                        <li @class(['flex flex-col rounded-2xl p-7 ring-1',
                                    'bg-stone-950 text-white ring-stone-950 dark:bg-white dark:text-stone-950 dark:ring-white' => $plan->recommended,
                                    'bg-white ring-stone-200 dark:bg-stone-900 dark:ring-stone-800' => ! $plan->recommended])>
                            <h3 class="flex items-center justify-between gap-2 font-display text-2xl">
                                {{ $plan->name }}
                                @if($plan->recommended)<span class="rounded-full bg-amber-300 px-2.5 py-0.5 font-body text-xs font-semibold text-stone-950">Most popular</span>@endif
                            </h3>
                            @if($offer->appliesTo($plan))
                                @php($discounted = $offer->discountedMonthlyPrice($plan))
                                <p class="mt-4 flex flex-wrap items-baseline gap-x-2">
                                    <span class="text-lg line-through opacity-60 tabular-nums"><span class="sr-only">Usually </span>{{ $currencySymbol }}{{ $plan->priceFor('month') }}</span>
                                    <span class="text-4xl font-semibold tabular-nums">{{ $currencySymbol }}{{ $discounted }}</span>
                                    <span class="text-sm opacity-75">/ month</span>
                                </p>
                                <p class="mt-1 text-sm font-medium {{ $plan->recommended ? 'text-amber-200 dark:text-amber-800' : 'text-green-800 dark:text-green-400' }}">
                                    {{ $currencySymbol }}{{ $discounted }}/mo for your first {{ $offer->months() }} months, then {{ $currencySymbol }}{{ $plan->priceFor('month') }}
                                </p>
                                <p class="mt-0.5 text-xs opacity-75">Launch offer ends {{ $offer->endsLabel() }}</p>
                            @else
                                <p class="mt-4 flex items-baseline gap-1">
                                    <span class="text-4xl font-semibold tabular-nums">{{ $currencySymbol }}{{ $plan->priceFor('month') }}</span>
                                    @unless($plan->isFree())<span class="text-sm opacity-75">/ month</span>@endunless
                                </p>
                                @if($plan->isFree())<p class="mt-1 text-sm font-medium opacity-80">Free for ever, no card needed</p>@endif
                            @endif
                            <ul role="list" class="mt-6 flex-1 space-y-2.5 text-sm">
                                @foreach($plan->features ?? [] as $feature)
                                    <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-0.5 size-4 shrink-0 opacity-70" />{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ auth()->check() ? route('pricing') : route('register', ['plan' => $plan->id, 'interval' => 'month']) }}"
                               @class(['mt-7 rounded-full px-5 py-3 text-center text-sm font-semibold focus:outline-hidden focus-visible:ring-4 focus-visible:ring-amber-300',
                                       'bg-white text-stone-950 hover:bg-amber-100 dark:bg-stone-950 dark:text-white dark:hover:bg-stone-800' => $plan->recommended,
                                       'bg-stone-100 text-stone-950 hover:bg-stone-200 dark:bg-stone-800 dark:text-white dark:hover:bg-stone-700' => ! $plan->recommended])>
                                {{ $plan->isFree() ? 'Start free' : 'Choose '.$plan->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-6 text-sm text-stone-600 dark:text-stone-400">
                    Prices in {{ \App\Support\Currency::code() }}. Yearly plans save more. <a href="{{ route('pricing') }}" class="font-semibold text-stone-900 underline underline-offset-4 dark:text-white">Compare all plans</a>
                </p>
            </div>
        </section>

        {{-- FAQ (mirrored in the FAQPage JSON-LD) --}}
        <section aria-labelledby="faq-heading" class="bg-white py-20 sm:py-28 dark:bg-stone-900">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <h2 id="faq-heading" class="font-display text-4xl font-medium tracking-tight sm:text-5xl">Questions</h2>
                <div class="mt-10 divide-y divide-stone-200 border-y border-stone-200 dark:divide-stone-800 dark:border-stone-800">
                    @foreach($page->faqs() as $faq)
                        <details class="group py-5" @if($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold [&::-webkit-details-marker]:hidden">
                                <h3>{{ $faq['q'] }}</h3>
                                <x-ui.icon name="plus" class="size-5 shrink-0 transition group-open:rotate-45" />
                            </summary>
                            <p class="mt-3 text-stone-600 dark:text-stone-400">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Closing call to action --}}
        <section class="relative isolate overflow-hidden bg-stone-950 text-white">
            <x-marketing.photo key="landscapes-6" sizes="100vw" class="absolute inset-0 -z-10 opacity-50" alt="" />
            <div class="mx-auto max-w-4xl px-4 py-24 text-center sm:px-6 sm:py-32 lg:px-8">
                <h2 class="font-display text-4xl font-medium tracking-tight text-balance sm:text-6xl">Put your best work where it belongs</h2>
                @if($offerActive)<p class="mt-5 text-lg text-stone-200">{{ $page->offerLine() }}.</p>@endif
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ $primaryHref }}" class="rounded-full bg-white px-7 py-3.5 font-semibold text-stone-950 hover:bg-amber-100 focus:outline-hidden focus-visible:ring-4 focus-visible:ring-amber-300">{{ $primaryLabel }}</a>
                    <a href="{{ $sampleHref }}" class="rounded-full px-5 py-3.5 font-semibold text-white ring-1 ring-white/60 hover:bg-white/10 focus:outline-hidden focus-visible:ring-4 focus-visible:ring-white">See a sample portfolio</a>
                </div>
            </div>
        </section>
    </main>

    @include('partials.marketing-footer')
    @livewireScriptConfig
    @include('partials.chatmesh')
    @include('partials.tracking')
</body>
</html>
