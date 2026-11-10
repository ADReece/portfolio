@php
    $user = auth()->user();
    $feeRate = config('services.platform_fee_percent') / 100;
    $feeLabel = rtrim(rtrim(number_format(config('services.platform_fee_percent'), 2), '0'), '.');
    $byCategory = $products->groupBy(fn ($p) => $p->category ?: 'Other');
@endphp

<x-app-layout title="Print products">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Print products"
                          description="Choose which prints clients can order from your galleries, and set your prices. Orders are printed and shipped for you." />

        @unless($user->hasFeature('selling'))
            <x-ui.alert tone="amber" title="Selling prints isn't included in your plan">
                You can set this up now, but clients won't see “Order a print” until you upgrade.
                <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
            </x-ui.alert>
        @endunless

        @unless($user->stripe_connect_enabled)
            @if(\App\Billing\Payouts::available())
            <x-ui.alert title="Connect Stripe to get paid">
                Print earnings are paid to your Stripe account. Connect it before clients start ordering.
                <x-slot:actions><x-ui.button size="sm" variant="secondary" :href="route('connect.stripe')">Set up payouts</x-ui.button></x-slot:actions>
            </x-ui.alert>
            @else
            <x-ui.alert title="Selling prints is coming soon">
                You can set up your print products and prices now. Clients will be able to order once payouts are ready.
            </x-ui.alert>
            @endif
        @endunless

        @if($belowCost)
            <x-ui.alert tone="red" title="Some prices no longer cover the cost">
                Prodigi's price has gone up for {{ count($belowCost) }} {{ \Illuminate\Support\Str::plural('product', count($belowCost)) }} below. Clients can't order {{ count($belowCost) === 1 ? 'it' : 'them' }} until you raise the price to at least the minimum shown.
            </x-ui.alert>
        @endif

        @if($products->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="shopping-bag" title="No print products available yet"
                                  description="We're still setting up the print catalogue. Please check back soon or contact support." />
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('profile.prodigi-products.update') }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @foreach($byCategory as $category => $items)
                    <x-ui.card :title="$category" :padding="false">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($items as $product)
                                @php
                                    $price = old('retail_prices.'.$product->id, $pricesByProductId[$product->id] ?? '');
                                    $default = $pricing->defaultFor($product);
                                    $minimum = $pricing->minimumFor($product);
                                    $isBelowCost = in_array($product->id, $belowCost, true);
                                @endphp
                                <li @class(['flex flex-wrap items-center gap-4 px-5 py-4 sm:px-6', 'bg-red-50 dark:bg-red-950/30' => $isBelowCost])
                                    x-data="{ on: @js(in_array($product->id, $enabledProductIds, true)), price: @js($price === '' ? null : (float) $price) }">
                                    @if($url = $product->imageUrl())
                                        <img src="{{ $url }}" alt="" class="size-12 shrink-0 rounded-md object-cover" loading="lazy">
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <x-ui.checkbox :id="'product-'.$product->id" name="enabled_product_ids[]" :value="$product->id" :unchecked-value="null"
                                                       :checked="in_array($product->id, $enabledProductIds, true)" x-model="on"
                                                       :label="$product->name.($product->size_label ? ' ('.$product->size_label.')' : '')" :description="$product->description ? \Illuminate\Support\Str::limit($product->description, 110) : null" />
                                        @if($isBelowCost)
                                            <p class="mt-1 ml-7 text-sm font-medium text-red-700 dark:text-red-400">Your price is below the new cost. Raise it to at least {{ $currencySymbol }}{{ number_format($minimum, 2) }}.</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4" :class="on ? '' : 'opacity-50'">
                                        <div>
                                            <label for="price-{{ $product->id }}" class="sr-only">Price for {{ $product->name }}</label>
                                            <div class="flex rounded-control shadow-xs">
                                                <span class="inline-flex items-center rounded-l-control border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $currencySymbol }}</span>
                                                <input id="price-{{ $product->id }}" type="number" step="0.01" min="{{ number_format($minimum ?? 0.5, 2, '.', '') }}" max="9999.99" inputmode="decimal"
                                                       name="retail_prices[{{ $product->id }}]" value="{{ $price }}" x-model.number="price" placeholder="{{ number_format($default, 2, '.', '') }}"
                                                       aria-describedby="price-help-{{ $product->id }}"
                                                       class="w-24 rounded-r-control border-gray-300 text-sm tabular-nums focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                            </div>
                                            <p id="price-help-{{ $product->id }}" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Empty uses {{ $currencySymbol }}{{ number_format($default, 2) }}@if($minimum !== null) · min {{ $currencySymbol }}{{ number_format($minimum, 2) }}@endif
                                            </p>
                                            @error('retail_prices.'.$product->id)<p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                                        </div>
                                        <p class="w-32 text-sm text-gray-600 dark:text-gray-400">
                                            You receive<br>
                                            <span class="font-semibold text-gray-900 tabular-nums dark:text-gray-100" x-text="@js($currencySymbol) + Math.max(0, (price || {{ $default }}) * {{ 1 - $feeRate }}).toFixed(2)"></span>
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endforeach

                <div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-card sm:border sm:px-5 dark:border-gray-800 dark:bg-gray-950/95">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Clients pay the price shown, with no delivery charge added. You receive the price minus our {{ $feeLabel }}% fee.</p>
                    <x-ui.button type="submit">Save print products</x-ui.button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
