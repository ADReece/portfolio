@php
    $money = fn ($v) => $v === null ? '—' : $currencySymbol.number_format((float) $v, 2);
@endphp
<x-app-layout title="Print catalogue">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Print catalogue" description="The Prodigi products photographers can offer, with costs, default prices and minimums.">
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.print-catalogue.mockups') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" icon="photo">Generate mockups</x-ui.button>
                </form>
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
            Prodigi's API can't list its catalogue, so add products by SKU: one at a time, pasted, from a CSV, or with “Discover”, which finds the common sizes of each product family.
            “Refresh” updates the details and costs of products already in this catalogue.
        </p>
        @if($imports->isNotEmpty())
            @php $running = $imports->contains(fn ($i) => ! $i->isFinished()); @endphp
            <x-ui.card title="Bulk adds" description="New products are added as inactive. Check their prices and options, then make them active.">
                @if($running)
                    {{-- Reload while an import runs, so the counts move. --}}
                    <div x-data x-init="setTimeout(() => location.reload(), 8000)"></div>
                @endif
                <ul class="divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    @foreach($imports as $import)
                        <li class="py-3">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $import->label ?? ucfirst($import->source) }}</span>
                                <span class="text-gray-600 dark:text-gray-400">
                                    @if($import->isFinished())
                                        Finished {{ $import->finished_at->diffForHumans() }}
                                    @else
                                        Checking: {{ $import->processed() }} of {{ $import->total }}
                                    @endif
                                </span>
                            </div>
                            @unless($import->isFinished())
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-label="Progress" aria-valuemin="0" aria-valuemax="{{ $import->total }}" aria-valuenow="{{ $import->processed() }}">
                                    <div class="h-full bg-brand-600" style="width: {{ $import->total ? round(100 * $import->processed() / $import->total) : 0 }}%"></div>
                                </div>
                            @endunless
                            <p class="mt-1 text-gray-700 dark:text-gray-300">
                                {{ $import->added }} added · {{ $import->skipped }} already in the catalogue · {{ $import->not_found }} not on Prodigi @if($import->failed) · <span class="font-medium text-red-700 dark:text-red-400">{{ $import->failed }} couldn't be checked</span>@endif
                            </p>
                            @if($import->failed_skus)
                                <p class="mt-1 break-words text-xs text-gray-600 dark:text-gray-400">Couldn't reach Prodigi for: <span class="font-mono">{{ implode(', ', $import->failed_skus) }}</span>. Add them again to retry.</p>
                            @endif
                            @if($import->not_found_skus && $import->source !== 'discover')
                                <p class="mt-1 break-words text-xs text-gray-600 dark:text-gray-400">Not on Prodigi: <span class="font-mono">{{ implode(', ', $import->not_found_skus) }}</span>@if($import->not_found > count($import->not_found_skus)) and {{ $import->not_found - count($import->not_found_skus) }} more @endif</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
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

        @if($products->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="swatch" title="No products" description="Add Prodigi products by SKU to offer them to photographers.">
                    <x-ui.button icon="plus" :href="route('admin.print-catalogue.create')">Add product</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            @php
                $allIds = $products->pluck('id')->map(fn ($id) => (string) $id)->values();
                $expandAll = filled($filters['q'] ?? null);
            @endphp
            <form id="bulk-form" method="POST" action="{{ route('admin.print-catalogue.bulk-status') }}">@csrf</form>

            <div class="space-y-6"
                 x-data="{
                    selected: [],
                    all: @js($allIds),
                    has(ids) { return ids.length && ids.every(id => this.selected.includes(id)) },
                    some(ids) { return ids.some(id => this.selected.includes(id)) },
                    toggle(ids, on) { this.selected = on ? [...new Set([...this.selected, ...ids])] : this.selected.filter(id => !ids.includes(id)) },
                 }">
                {{-- Bulk actions for the ticked sizes. --}}
                <div class="sticky top-0 z-20 flex flex-wrap items-center justify-between gap-3 rounded-card border border-gray-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                        <input type="checkbox" class="size-4 rounded-sm border-gray-300 text-brand-600 dark:border-gray-600 dark:bg-gray-900"
                               :checked="has(all)" x-effect="$el.indeterminate = some(all) && !has(all)" x-on:change="toggle(all, $event.target.checked)">
                        Select all {{ $products->count() }} on this page
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm text-gray-600 dark:text-gray-400" aria-live="polite"><span x-text="selected.length">0</span> selected</p>
                        <x-ui.button type="submit" form="bulk-form" name="status" value="active" size="sm" variant="secondary" x-bind:disabled="!selected.length">Activate</x-ui.button>
                        <x-ui.button type="submit" form="bulk-form" name="status" value="inactive" size="sm" variant="secondary" x-bind:disabled="!selected.length">Deactivate</x-ui.button>
                    </div>
                </div>

                @foreach($grouped as $category => $groups)
                    @php
                        $categoryProducts = $groups->flatMap(fn ($g) => $g['products']);
                        $categoryIds = $categoryProducts->pluck('id')->map(fn ($id) => (string) $id)->values();
                        $categorySlug = \Illuminate\Support\Str::slug($category);
                    @endphp
                    <section x-data="{ open: true }" aria-labelledby="cat-{{ $categorySlug }}">
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="checkbox" aria-label="Select every {{ $category }} product" class="size-4 rounded-sm border-gray-300 text-brand-600 dark:border-gray-600 dark:bg-gray-900"
                                   :checked="has(@js($categoryIds))" x-effect="$el.indeterminate = some(@js($categoryIds)) && !has(@js($categoryIds))" x-on:change="toggle(@js($categoryIds), $event.target.checked)">
                            <h2 id="cat-{{ $categorySlug }}" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <button type="button" class="inline-flex items-center gap-1 rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                                        x-on:click="open = !open" :aria-expanded="open.toString()" aria-controls="cat-panel-{{ $categorySlug }}">
                                    <x-ui.icon name="chevron-down" class="size-5 transition-transform" x-bind:class="open ? '' : '-rotate-90'" />
                                    {{ $category }}
                                </button>
                            </h2>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $groups->count() }} {{ \Illuminate\Support\Str::plural('product', $groups->count()) }} · {{ $categoryProducts->count() }} {{ \Illuminate\Support\Str::plural('size', $categoryProducts->count()) }} · {{ $categoryProducts->where('is_active', true)->count() }} active</span>
                        </div>

                        <div id="cat-panel-{{ $categorySlug }}" x-show="open" class="mt-3 space-y-3">
                            @foreach($groups as $group)
                                @php
                                    $sizes = $group['products'];
                                    $ids = $sizes->pluck('id')->map(fn ($id) => (string) $id)->values();
                                    $activeCount = $sizes->where('is_active', true)->count();
                                    $costs = $sizes->map(fn ($p) => $p->totalCost())->filter(fn ($c) => $c !== null);
                                    $panelId = 'group-'.\Illuminate\Support\Str::slug($group['key']).'-'.$categorySlug;
                                    $preview = $sizes->first(fn ($p) => $p->image_path) ?? $sizes->first();
                                @endphp
                                <x-ui.card :padding="false" x-data="{ open: {{ $expandAll ? 'true' : 'false' }} }">
                                    <div class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-5">
                                        <input type="checkbox" aria-label="Select every size of {{ $group['label'] }}" class="size-4 rounded-sm border-gray-300 text-brand-600 dark:border-gray-600 dark:bg-gray-900"
                                               :checked="has(@js($ids))" x-effect="$el.indeterminate = some(@js($ids)) && !has(@js($ids))" x-on:change="toggle(@js($ids), $event.target.checked)">
                                        <button type="button" class="flex min-w-0 flex-1 items-center gap-3 rounded-md text-left focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                                                x-on:click="open = !open" :aria-expanded="open.toString()" aria-controls="{{ $panelId }}">
                                            <x-prints.preview :product="$preview" class="size-12" />
                                            <span class="min-w-0">
                                                <span class="block font-semibold text-gray-900 dark:text-gray-100">{{ $group['label'] }}</span>
                                                <span class="block text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="font-mono text-xs">{{ $group['key'] }}</span> ·
                                                    {{ $sizes->count() }} {{ \Illuminate\Support\Str::plural('size', $sizes->count()) }} · {{ $activeCount }} active
                                                    @if($costs->isNotEmpty()) · cost {{ $money($costs->min()) }}@if($costs->min() != $costs->max())–{{ $money($costs->max()) }}@endif @endif
                                                </span>
                                            </span>
                                            <x-ui.icon name="chevron-down" class="ml-auto size-5 shrink-0 text-gray-500 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                                        </button>
                                        <form method="POST" action="{{ route('admin.print-catalogue.bulk-status') }}">
                                            @csrf
                                            @foreach($ids as $id)<input type="hidden" name="ids[]" value="{{ $id }}">@endforeach
                                            @if($activeCount === $sizes->count())
                                                <input type="hidden" name="status" value="inactive">
                                                <x-ui.button type="submit" size="sm" variant="secondary">Deactivate all<span class="sr-only"> {{ $group['label'] }} sizes</span></x-ui.button>
                                            @else
                                                <input type="hidden" name="status" value="active">
                                                <x-ui.button type="submit" size="sm" variant="secondary">Activate all<span class="sr-only"> {{ $group['label'] }} sizes</span></x-ui.button>
                                            @endif
                                        </form>
                                    </div>

                                    <div id="{{ $panelId }}" x-show="open" @unless($expandAll) x-cloak @endunless class="border-t border-gray-200 dark:border-gray-700">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                                <caption class="sr-only">Sizes of {{ $group['label'] }}</caption>
                                                <thead class="bg-gray-50 text-left dark:bg-gray-900/40">
                                                    <tr class="text-gray-900 dark:text-gray-100">
                                                        <th scope="col" class="w-10 py-2 pl-4 sm:pl-5"><span class="sr-only">Select</span></th>
                                                        <th scope="col" class="px-3 py-2 font-semibold">Size</th>
                                                        <th scope="col" class="px-3 py-2 text-right font-semibold">Cost</th>
                                                        <th scope="col" class="px-3 py-2 text-right font-semibold">Minimum</th>
                                                        <th scope="col" class="px-3 py-2 text-right font-semibold">Default price</th>
                                                        <th scope="col" class="px-3 py-2 text-right font-semibold">Margin</th>
                                                        <th scope="col" class="px-3 py-2 text-right font-semibold">Photographers</th>
                                                        <th scope="col" class="px-3 py-2 font-semibold">Status</th>
                                                        <th scope="col" class="px-3 py-2 sm:pr-5"><span class="sr-only">Actions</span></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($sizes as $product)
                                                        @php
                                                            $cost = $product->totalCost();
                                                            $default = $pricing->defaultFor($product);
                                                            $margin = $pricing->payoutFor($product, $default);
                                                        @endphp
                                                        <tr>
                                                            <td class="py-2 pl-4 sm:pl-5">
                                                                <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $product->id }}" x-model="selected"
                                                                       aria-label="Select {{ $product->name }} ({{ $product->sku }})"
                                                                       class="size-4 rounded-sm border-gray-300 text-brand-600 dark:border-gray-600 dark:bg-gray-900">
                                                            </td>
                                                            <td class="min-w-48 px-3 py-2">
                                                                <a href="{{ route('admin.print-catalogue.edit', $product) }}" class="font-medium whitespace-nowrap text-gray-900 hover:underline dark:text-gray-100">{{ $product->size_label ?: $product->name }}</a>
                                                                <div class="font-mono text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $product->sku }}</div>
                                                                @if($product->size_label && $product->name !== $product->sku)<div class="text-xs text-gray-500 dark:text-gray-400">{{ $product->name }}</div>@endif
                                                                @if($product->refresh_error)<div class="text-xs text-red-700 dark:text-red-400">Last refresh failed</div>@endif
                                                            </td>
                                                            <td class="px-3 py-2 text-right whitespace-nowrap tabular-nums">
                                                                {{ $money($cost) }}
                                                                @if($cost !== null)<div class="text-xs text-gray-500 dark:text-gray-400">{{ $money($product->cost_item) }} + {{ $money($product->cost_shipping) }} delivery</div>@endif
                                                            </td>
                                                            <td class="px-3 py-2 text-right whitespace-nowrap tabular-nums">{{ $money($pricing->minimumFor($product)) }}</td>
                                                            <td class="px-3 py-2 text-right whitespace-nowrap tabular-nums">{{ $money($default) }}</td>
                                                            <td class="px-3 py-2 text-right whitespace-nowrap tabular-nums">{{ $money($margin) }}<div class="text-xs text-gray-500 dark:text-gray-400">to photographer</div></td>
                                                            <td class="px-3 py-2 text-right tabular-nums">
                                                                {{ number_format($product->enabled_count) }}
                                                                @if($belowCost[$product->id] ?? 0)
                                                                    <div><x-ui.badge tone="red">{{ $belowCost[$product->id] }} below cost</x-ui.badge></div>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2">
                                                                <form method="POST" action="{{ route('admin.print-catalogue.toggle', $product) }}">
                                                                    @csrf
                                                                    <button type="submit" class="rounded-full focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                                                                        @if($product->is_active)<x-ui.badge tone="green">Active</x-ui.badge>@else<x-ui.badge>Inactive</x-ui.badge>@endif
                                                                        <span class="sr-only">: {{ $product->is_active ? 'deactivate' : 'activate' }} {{ $product->name }}</span>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                            <td class="px-3 py-2 text-right whitespace-nowrap sm:pr-5">
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
                                        <form method="POST" action="{{ route('admin.print-catalogue.group-image') }}" enctype="multipart/form-data"
                                              class="flex flex-wrap items-center gap-3 border-t border-gray-200 px-4 py-3 text-sm sm:px-5 dark:border-gray-700">
                                            @csrf
                                            <input type="hidden" name="group" value="{{ $group['key'] }}">
                                            <label for="{{ $panelId }}-image" class="font-medium text-gray-800 dark:text-gray-200">Image for every size</label>
                                            <input id="{{ $panelId }}-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required class="text-sm">
                                            <x-ui.checkbox :id="$panelId.'-replace'" name="replace" label="Replace sizes' own images" :unchecked-value="null" />
                                            <x-ui.button type="submit" size="sm" variant="secondary" icon="arrow-up-tray">Upload</x-ui.button>
                                        </form>
                                    </div>
                                </x-ui.card>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
