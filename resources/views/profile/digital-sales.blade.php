@php
    $symbol = \App\Support\Currency::symbol();
    $feeLabel = rtrim(rtrim(number_format(config('services.platform_fee_percent'), 2), '0'), '.');
    $rows = old('bundles', $bundles);
    $rows = array_values(array_pad(is_array($rows) ? $rows : [], \App\Downloads\DigitalPricing::MAX_BUNDLES, ['count' => null, 'price' => null]));
@endphp

<x-app-layout title="Photo downloads">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Photo downloads"
                          description="Sell full-resolution, unwatermarked downloads from your collections. Clients choose their photos, pay by card, and get a download link by email." />

        @unless($user->hasFeature('selling'))
            <x-ui.alert tone="amber" title="Selling isn't included in your plan">
                You can set your prices now, but clients won't be able to buy until you upgrade.
                <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
            </x-ui.alert>
        @endunless
        @unless($user->stripe_connect_enabled)
            @if(\App\Billing\Payouts::available())
                <x-ui.alert title="Set up payouts to get paid">
                    Download sales are paid into your bank through Stripe, so clients can only buy once payouts are set up.
                    <x-slot:actions><x-ui.button size="sm" variant="secondary" :href="route('connect.stripe')">Set up payouts</x-ui.button></x-slot:actions>
                </x-ui.alert>
            @else
                <x-ui.alert title="Selling is coming soon">
                    You can set your prices now. Clients will be able to buy once payouts are ready.
                </x-ui.alert>
            @endif
        @endunless

        <form method="POST" action="{{ route('profile.digital-sales.update') }}">
            @csrf
            @method('PATCH')
            <x-ui.card title="Your prices" description="Used by every collection you sell downloads from, unless you set a collection's own price. Leave a price empty to not offer it.">
                <div class="space-y-6">
                    <div class="max-w-xs">
                        <x-ui.input name="digital_photo_price" type="number" step="0.01" min="{{ $minimum }}" inputmode="decimal"
                                    label="Price per photo ({{ $symbol }})" :value="old('digital_photo_price', $user->digital_photo_price)"
                                    help="Leave empty to sell only in bundles or whole collections." />
                    </div>

                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-900 dark:text-gray-100">Bundles</legend>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">For example, any 5 photos for {{ $symbol }}20. Clients choose which photos.</p>
                        <div class="mt-3 space-y-3">
                            @foreach($rows as $i => $row)
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="w-36">
                                        <x-ui.input :id="'bundle-count-'.$i" :name="'bundles['.$i.'][count]'" type="number" min="2" step="1" inputmode="numeric"
                                                    :label="'Bundle '.($i + 1).': photos'" :value="$row['count'] ?? ''" />
                                    </div>
                                    <div class="w-36">
                                        <x-ui.input :id="'bundle-price-'.$i" :name="'bundles['.$i.'][price]'" type="number" step="0.01" min="{{ $minimum }}" inputmode="decimal"
                                                    :label="'Price ('.$symbol.')'" :value="$row['price'] ?? ''" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Clients always pay the cheapest mix of single photos and bundles for what they choose. You receive the price less the {{ $feeLabel }}% platform fee (card fees are included in it).
                        Prices start at {{ \App\Support\Currency::format($minimum) }}.
                    </p>
                </div>
                <div class="mt-6 flex justify-end border-t border-gray-200 pt-5 dark:border-gray-700">
                    <x-ui.button type="submit" icon="check">Save prices</x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <x-ui.card title="Your collections" description="Turn on sales, and set a whole-collection price, in each collection's settings. While a collection sells downloads, its free downloads are switched off." :padding="false">
            @if($collections->isEmpty())
                <x-ui.empty-state icon="rectangle-stack" title="No collections yet" description="Create a collection and upload photos to start selling." />
            @else
                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($collections as $collection)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $collection->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $collection->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $collection->photos_count) }}
                                    @if($collection->digital_photo_price !== null) · {{ \App\Support\Currency::format($collection->digital_photo_price) }} per photo @endif
                                    @if($collection->digital_collection_price !== null) · all for {{ \App\Support\Currency::format($collection->digital_collection_price) }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($collection->digital_sales)
                                    <x-ui.badge tone="green" icon="check">On sale</x-ui.badge>
                                @else
                                    <x-ui.badge>Not for sale</x-ui.badge>
                                @endif
                                <x-ui.button size="sm" variant="secondary" :href="route('collections.edit', $collection->id).'#downloads'">Settings</x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
