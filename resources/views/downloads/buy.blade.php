@php
    $photographerName = $photographer->name ?: $photographer->username;
    $symbol = \App\Support\Currency::symbol();
    $ids = $photos->pluck('id')->map(fn ($id) => (string) $id)->values();
    $selected = old('photo_ids', $preselected);
@endphp
<x-collection-layout :user="$photographer" :title="'Buy photos · '.$collection->name" :noindex="true">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
         x-data="{
            ids: @js($ids),
            selected: @js(array_values(array_map('strval', (array) $selected))),
            quotes: @js($quotes),
            symbol: @js($symbol),
            money(v) { return this.symbol + Number(v).toFixed(2) },
            get count() { return this.selected.length },
            get quote() { return this.count ? this.quotes[this.count] : null },
            get next() { return this.count && this.count < this.ids.length ? this.quotes[this.count + 1] : null },
            toggleAll() { this.selected = this.count === this.ids.length ? [] : [...this.ids] },
         }">
        <a href="{{ $galleryUrl }}" class="inline-flex items-center gap-1 text-sm opacity-80 hover:underline hover:opacity-100">
            <x-ui.icon name="chevron-left" class="size-4" /> Back to gallery
        </a>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold sm:text-3xl">Buy photos from {{ $collection->name }}</h1>
                <p class="mt-1 max-w-2xl opacity-80">
                    Choose the photos you'd like. You'll get the full-resolution originals from {{ $photographerName }}, without a watermark, by email straight after paying.
                </p>
            </div>
            <ul class="portfolio-neutral rounded-card bg-white px-4 py-3 text-sm text-gray-800 shadow-sm dark:bg-gray-800 dark:text-gray-100" aria-label="Prices">
                @if($offer['photo'] !== null)<li>{{ \App\Support\Currency::format($offer['photo']) }} per photo</li>@endif
                @foreach($offer['bundles'] as $bundle)<li>Any {{ $bundle['count'] }} for {{ \App\Support\Currency::format($bundle['price']) }}</li>@endforeach
                @if($offer['collection'] !== null)<li>All {{ $photos->count() }} for {{ \App\Support\Currency::format($offer['collection']) }}</li>@endif
            </ul>
        </div>

        @if($photographer->is_demo)
            <div class="portfolio-neutral mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100" role="status">
                {{ session('demo', 'This is a demo portfolio: pick photos and see the price, but nothing is charged.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('download-purchase.store', $collection) }}" class="mt-6 lg:grid lg:grid-cols-[1fr_22rem] lg:gap-8">
            @csrf
            <fieldset>
                <legend class="sr-only">Photos</legend>
                <div class="mb-3 flex items-center justify-between gap-3 text-sm">
                    <p aria-live="polite"><span x-text="count">{{ count($selected) }}</span> of {{ $photos->count() }} chosen</p>
                    <button type="button" x-on:click="toggleAll()" class="font-medium underline underline-offset-4"
                            x-text="count === ids.length ? 'Clear all' : 'Choose all'">Choose all</button>
                </div>
                @error('photo_ids')<p class="mb-3 text-sm font-medium text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                <ul role="list" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach($photos as $photo)
                        <li>
                            <label class="group relative block cursor-pointer overflow-hidden rounded-lg">
                                <input type="checkbox" name="photo_ids[]" value="{{ $photo->id }}" x-model="selected"
                                       class="peer absolute top-2 left-2 z-10 size-5 rounded border-white text-gray-900 shadow focus-visible:ring-2 focus-visible:ring-white"
                                       @checked(in_array((string) $photo->id, (array) $selected, true))>
                                <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ?: 'Photo '.$loop->iteration }}" loading="lazy"
                                     class="aspect-square w-full bg-black/5 object-cover ring-4 ring-transparent transition peer-checked:opacity-90 peer-checked:ring-current"
                                     draggable="false" oncontextmenu="return false;">
                            </label>
                        </li>
                    @endforeach
                </ul>
            </fieldset>

            <aside id="checkout" class="mt-8 scroll-mt-4 pb-20 lg:mt-0 lg:pb-0">
                <div class="portfolio-neutral space-y-4 rounded-card bg-white p-5 text-gray-900 shadow-sm lg:sticky lg:top-6 dark:bg-gray-800 dark:text-gray-100">
                    <h2 class="text-lg font-semibold">Your order</h2>
                    <div aria-live="polite">
                        <template x-if="!count"><p class="text-sm text-gray-600 dark:text-gray-400">Choose one or more photos.</p></template>
                        <template x-if="count && !quote"><p class="text-sm text-gray-600 dark:text-gray-400">Choose more photos to make up a bundle.</p></template>
                        <template x-if="quote">
                            <div class="space-y-2 text-sm">
                                <ul class="space-y-1">
                                    <template x-for="line in quote.lines" :key="line.label">
                                        <li class="flex justify-between gap-3">
                                            <span x-text="(line.quantity > 1 ? line.quantity + ' × ' : '') + line.label"></span>
                                            <span x-text="money(line.amount)"></span>
                                        </li>
                                    </template>
                                </ul>
                                <p class="flex justify-between border-t border-gray-200 pt-2 text-base font-semibold dark:border-gray-700">
                                    <span>Total</span><span x-text="money(quote.total)"></span>
                                </p>
                                <p x-show="quote.all && count < ids.length" class="rounded-md bg-green-50 p-2 text-green-800 dark:bg-green-950/40 dark:text-green-300">
                                    The whole collection costs the same or less, so you'll get all {{ $photos->count() }} photos.
                                </p>
                                <p x-show="!quote.all && next && next.total <= quote.total" class="rounded-md bg-green-50 p-2 text-green-800 dark:bg-green-950/40 dark:text-green-300">
                                    Choose one more photo at no extra cost.
                                </p>
                            </div>
                        </template>
                    </div>

                    <x-ui.input name="customer_email" type="email" label="Your email" autocomplete="email" :value="old('customer_email')" required
                                help="We'll send your download link here." />
                    <x-ui.input name="customer_name" label="Your name" autocomplete="name" :value="old('customer_name')" optional />

                    <button type="submit" x-bind:disabled="!quote"
                            class="portfolio-button w-full rounded-control px-4 py-3 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                            style="background: var(--portfolio-accent, #4f46e5)">
                        <span x-text="quote ? 'Pay ' + money(quote.total) : 'Choose photos'">Continue to payment</span>
                    </button>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Secure card payment by Stripe. Downloads are for your personal use; the photographer keeps the copyright.</p>
                </div>
            </aside>
        </form>

        {{-- On phones the order sits below the photos: keep the total in view. --}}
        <div x-show="count" x-cloak class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white px-4 py-3 text-gray-900 shadow-lg lg:hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <p class="text-sm"><span x-text="count"></span> chosen<span x-show="quote"> · <strong x-text="quote ? money(quote.total) : ''"></strong></span></p>
                <a href="#checkout" class="portfolio-button rounded-control px-4 py-2 text-sm font-semibold text-white" style="background: var(--portfolio-accent, #4f46e5)">Checkout</a>
            </div>
        </div>
    </div>
</x-collection-layout>
