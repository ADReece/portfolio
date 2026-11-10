@php
    $money = fn ($v) => $v === null ? '—' : $currencySymbol.number_format((float) $v, 2);
@endphp
<x-app-layout title="Print catalogue">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Print catalogue" description="The Prodigi products photographers can offer, with costs, default prices and minimums.">
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.print-catalogue.refresh-all') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" icon="arrow-path" :disabled="! $prodigiReady">Refresh all from Prodigi</x-ui.button>
                </form>
                <x-ui.button icon="plus" :href="route('admin.print-catalogue.create')">Add product</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @unless($prodigiReady)
            <x-ui.alert tone="amber" title="Prodigi isn't configured">Set PRODIGI_API_KEY to look up products and refresh costs.</x-ui.alert>
        @endunless
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Prodigi's API can't list the products saved in your Prodigi account, so add them here by SKU (you can paste several at once).
            “Refresh” updates the details and costs of products already in this catalogue.
        </p>
        @unless(\App\Billing\Payouts::available())
            <x-ui.alert title="Selling prints is still “coming soon” for photographers">
                The catalogue works fully now, so it's ready when Stripe Connect is switched on.
            </x-ui.alert>
        @endunless
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Prodigi: <strong>{{ $environment === 'live' ? 'live (orders cost real money; lookups and quotes are free)' : $environment }}</strong>.
            Costs are quotes for one copy to the UK with standard delivery. Clients pay the price (delivery included); the photographer receives price − cost − {{ rtrim(rtrim(number_format(config('services.platform_fee_percent'), 2), '0'), '.') }}% platform fee, and card fees come out of the platform fee. The minimum price keeps both at or above zero. At checkout the cost is quoted again for the client's address.
        </p>

        <form method="GET" class="flex flex-wrap items-end gap-3" role="search">
            <div class="min-w-56 flex-1"><x-ui.input name="q" label="Search" :value="$filters['q'] ?? ''" placeholder="SKU or name" /></div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Category</label>
                <select id="category" name="category" class="mt-1 rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">All</option>
                    @foreach($categories as $c)<option @selected(($filters['category'] ?? '') === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Status</label>
                <select id="status" name="status" class="mt-1 rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        </form>

        <x-ui.card :padding="false">
            @if($products->isEmpty())
                <x-ui.empty-state icon="swatch" title="No products" description="Add Prodigi products by SKU to offer them to photographers.">
                    <x-ui.button icon="plus" :href="route('admin.print-catalogue.create')">Add product</x-ui.button>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left dark:bg-gray-900/40">
                            <tr class="text-gray-900 dark:text-gray-100">
                                <th scope="col" class="px-4 py-3 font-semibold sm:pl-6">Product</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Category</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Cost</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Minimum</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Default price</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Margin</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Photographers</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                                <th scope="col" class="px-4 py-3 sm:pr-6"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($products as $product)
                                @php
                                    $cost = $product->totalCost();
                                    $default = $pricing->defaultFor($product);
                                    $margin = $pricing->payoutFor($product, $default);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 sm:pl-6">
                                        <div class="flex items-center gap-3">
                                            @if($url = $product->imageUrl())
                                                <img src="{{ $url }}" alt="" class="size-10 shrink-0 rounded-md object-cover" loading="lazy">
                                            @else
                                                <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-400 dark:bg-gray-800"><x-ui.icon name="photo" class="size-5" /></span>
                                            @endif
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.print-catalogue.edit', $product) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-100">{{ $product->name }}</a>
                                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $product->sku }}@if($product->size_label) · {{ $product->size_label }}@endif</div>
                                                @if($product->refresh_error)<div class="text-xs text-red-700 dark:text-red-400">Last refresh failed</div>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $product->category }}<div class="text-xs text-gray-500">#{{ $product->sort_order }}</div></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                        {{ $money($cost) }}
                                        @if($cost !== null)<div class="text-xs text-gray-500 dark:text-gray-400">{{ $money($product->cost_item) }} + {{ $money($product->cost_shipping) }} delivery</div>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ $money($pricing->minimumFor($product)) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ $money($default) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ $money($margin) }}<div class="text-xs text-gray-500 dark:text-gray-400">to photographer</div></td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        {{ number_format($product->enabled_count) }}
                                        @if($belowCost[$product->id] ?? 0)
                                            <div><x-ui.badge tone="red">{{ $belowCost[$product->id] }} below cost</x-ui.badge></div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('admin.print-catalogue.toggle', $product) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                                                @if($product->is_active)<x-ui.badge tone="green">Active</x-ui.badge>@else<x-ui.badge>Inactive</x-ui.badge>@endif
                                                <span class="sr-only">: {{ $product->is_active ? 'deactivate' : 'activate' }} {{ $product->name }}</span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap sm:pr-6">
                                        <form method="POST" action="{{ route('admin.print-catalogue.refresh', $product) }}" class="inline">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="ghost" icon="arrow-path" :disabled="! $prodigiReady">Refresh<span class="sr-only"> {{ $product->name }}</span></x-ui.button>
                                        </form>
                                        <x-ui.button size="sm" variant="secondary" :href="route('admin.print-catalogue.edit', $product)">Edit<span class="sr-only"> {{ $product->name }}</span></x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
