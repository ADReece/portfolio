@php
    $money = fn ($v) => $v === null ? '—' : $currencySymbol.number_format((float) $v, 2);
    $minimum = $pricing->minimumFor($product);
    $allowed = $product->allowed_attributes ?? [];
@endphp
<x-app-layout :title="$product->name">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$product->name" :description="$product->sku.($product->size_label ? ' · '.$product->size_label : '')" :back="route('admin.print-catalogue.index')" back-label="Print catalogue">
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.print-catalogue.refresh', $product) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" icon="arrow-path" :disabled="! \App\Service\ProdigiService::configured()">Refresh from Prodigi</x-ui.button>
                </form>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card title="Cost from Prodigi" description="One copy to the UK with standard delivery.">
            <dl class="grid gap-4 text-sm sm:grid-cols-4">
                <div><dt class="text-gray-600 dark:text-gray-400">Print</dt><dd class="text-lg font-semibold tabular-nums">{{ $money($product->cost_item) }}</dd></div>
                <div><dt class="text-gray-600 dark:text-gray-400">Delivery</dt><dd class="text-lg font-semibold tabular-nums">{{ $money($product->cost_shipping) }}</dd></div>
                <div><dt class="text-gray-600 dark:text-gray-400">Minimum retail price</dt><dd class="text-lg font-semibold tabular-nums">{{ $money($minimum) }}</dd></div>
                <div><dt class="text-gray-600 dark:text-gray-400">Last refreshed</dt><dd>{{ $product->refreshed_at?->diffForHumans() ?? 'Never' }}</dd></div>
            </dl>
            @if($product->refresh_error)
                <x-ui.alert tone="red" title="Last refresh failed" class="mt-4">{{ $product->refresh_error }}</x-ui.alert>
            @endif
        </x-ui.card>

        <form method="POST" action="{{ route('admin.print-catalogue.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            <x-ui.card title="Shown to photographers and clients">
                <div class="space-y-4">
                    <x-ui.input name="name" label="Name" :value="old('name', $product->name)" required />
                    <x-ui.textarea name="description" label="Description" rows="3" optional>{{ old('description', $product->description) }}</x-ui.textarea>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Category</label>
                            <select id="category" name="category" class="mt-1 w-full rounded-control border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
                                @foreach($categories as $c)<option @selected(old('category', $product->category) === $c)>{{ $c }}</option>@endforeach
                            </select>
                        </div>
                        <x-ui.input name="sort_order" type="number" min="0" label="Sort order" help="Lower numbers are listed first." :value="old('sort_order', $product->sort_order)" required />
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-gray-800 dark:text-gray-200">Preview image</span>
                        <div class="mt-2 flex flex-wrap items-center gap-4">
                            @if($url = $product->imageUrl())
                                <img src="{{ $url }}" alt="Current preview of {{ $product->name }}" class="size-24 rounded-md object-cover">
                                <x-ui.checkbox name="remove_image" label="Remove image" :unchecked-value="null" />
                            @endif
                            <div>
                                <label for="image" class="sr-only">Upload a preview image</label>
                                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="text-sm">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG or WebP, up to 5 MB.</p>
                                @error('image')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            @if($product->prodigiAttributes())
                <x-ui.card title="Options offered to clients" description="Clients choose from the ticked options when they order.">
                    <div class="space-y-5">
                        @foreach($product->prodigiAttributes() as $name => $values)
                            <fieldset>
                                <legend class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ \App\Models\ProdigiProduct::labelAttribute($name) }}</legend>
                                <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
                                    @foreach($values as $value)
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="allowed_attributes[{{ $name }}][]" value="{{ $value }}" class="size-4 rounded-sm border-gray-300 text-brand-600 dark:border-gray-600 dark:bg-gray-900"
                                                   @checked(in_array($value, old("allowed_attributes.$name", $allowed[$name] ?? $values), true))>
                                            {{ $value }}
                                        </label>
                                    @endforeach
                                </div>
                                @error("allowed_attributes.$name")<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </fieldset>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card title="Price and availability">
                <div class="space-y-4">
                    <x-ui.input name="default_retail_price" type="number" step="0.01" min="0.50" label="Default retail price ({{ $currencySymbol }})" optional
                                :help="'Used when a photographer hasn\'t set their own price. Leave empty for '.$money(config('prints.fallback_retail_price')).($minimum !== null ? '. Minimum '.$money($minimum).'.' : '.')"
                                :value="old('default_retail_price', $product->default_retail_price)" />
                    <x-ui.checkbox name="is_active" label="Active" :checked="old('is_active', $product->is_active)"
                                   description="Inactive products are hidden from photographers and clients. Past orders and photographers' settings are kept." />
                </div>
            </x-ui.card>

            <div class="flex justify-end"><x-ui.button type="submit">Save</x-ui.button></div>
        </form>
    </div>
</x-app-layout>
