@php
    $features = array_values(array_filter((array) old('features', $plan->features ?? []), fn ($f) => filled($f)));
    $flags = [
        'private_collections' => ['Private collections', 'Password-protected and link-only galleries.', false],
        'watermarking' => ['Watermarking', 'Watermark photos in client galleries.', false],
        'selling' => ['Print sales', 'Sell prints from client galleries.', false],
        'video_upload' => ['Video upload', 'Upload videos alongside photos.', false],
        'custom_templates' => ['Custom templates', 'Choose portfolio and gallery templates.', false],
    ];
@endphp

<x-ui.card title="Basics">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="name" label="Plan name" :value="old('name', $plan->name ?? '')" required />
        <x-ui.input name="slug" label="Slug" help="URL-friendly identifier, e.g. photographer." :value="old('slug', $plan->slug ?? '')" required />
        <x-ui.input type="number" name="sort_order" label="Sort order" help="Lower numbers appear first." min="0" :value="old('sort_order', $plan->sort_order ?? '0')" required />
    </div>
</x-ui.card>

<x-ui.card title="Pricing" description="Prices are in {{ $currencySymbol }}.">
    <div class="grid gap-5 sm:grid-cols-3">
        <x-ui.input type="number" step="0.01" min="0" name="price" label="Monthly price ({{ $currencySymbol }})" :value="old('price', $plan->price ?? '0.00')" required />
        <x-ui.input type="number" step="0.01" min="0" name="annual_price" label="Annual price ({{ $currencySymbol }})" optional :value="old('annual_price', $plan->annual_price ?? '')" />
        <x-ui.input type="number" min="0" max="100" name="annual_discount_percent" label="Annual discount (%)" optional :value="old('annual_discount_percent', $plan->annual_discount_percent ?? '')" />
    </div>
</x-ui.card>

<x-ui.card title="Stripe" description="Copy these IDs from the product in your Stripe dashboard.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="stripe_product_id" label="Product ID" optional placeholder="prod_…" class="font-mono" :value="old('stripe_product_id', $plan->stripe_product_id ?? '')" />
        <x-ui.input name="stripe_price_id" label="Monthly price ID" optional placeholder="price_…" class="font-mono" :value="old('stripe_price_id', $plan->stripe_price_id ?? '')" />
        <x-ui.input name="annual_stripe_price_id" label="Annual price ID" optional placeholder="price_…" class="font-mono" :value="old('annual_stripe_price_id', $plan->annual_stripe_price_id ?? '')" />
    </div>
</x-ui.card>

<x-ui.card title="Limits" description="Leave blank for unlimited.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input type="number" min="0" name="photo_limit" label="Photo limit" optional :value="old('photo_limit', $plan->photo_limit ?? '')" />
        <x-ui.input type="number" min="0" name="collection_limit" label="Collection limit" optional :value="old('collection_limit', $plan->collection_limit ?? '')" />
    </div>
</x-ui.card>

<x-ui.card title="Included features">
    <div class="grid gap-5 sm:grid-cols-2">
        @foreach($flags as $field => [$label, $description, $default])
            <x-ui.checkbox :name="$field" :label="$label" :description="$description" :checked="(bool) old($field, $plan->{$field} ?? $default)" />
        @endforeach
    </div>
</x-ui.card>

<x-ui.card title="Visibility">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.checkbox name="active" label="Active" description="Show this plan on the pricing page and allow new subscriptions." :checked="(bool) old('active', $plan->active ?? true)" />
        <x-ui.checkbox name="recommended" label="Recommended" description="Highlight this plan on the pricing page." :checked="(bool) old('recommended', $plan->recommended ?? false)" />
    </div>
</x-ui.card>

<x-ui.card title="Feature list" description="Bullet points shown on the pricing page. Empty rows are ignored.">
    <div x-data="{ features: {{ \Illuminate\Support\Js::from($features ?: ['']) }} }" class="space-y-3">
        @error('features')<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
        @if($errors->has('features.*'))<p class="text-sm text-red-700 dark:text-red-400">{{ $errors->first('features.*') }}</p>@endif

        <ul role="list" class="space-y-2">
            <template x-for="(feature, index) in features" :key="index">
                <li class="flex items-center gap-2">
                    <label :for="'feature-' + index" class="sr-only" x-text="'Feature ' + (index + 1)"></label>
                    <input type="text" :id="'feature-' + index" x-model="features[index]" maxlength="255"
                           :name="features[index].trim() === '' ? null : 'features[]'"
                           placeholder="e.g. Unlimited galleries"
                           class="block w-full rounded-control border-gray-300 shadow-xs focus:border-brand-500 focus:ring-brand-500 sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <button type="button" x-on:click="features.splice(index, 1); if (features.length === 0) features.push('')"
                            class="inline-flex shrink-0 items-center justify-center rounded-control p-2 text-gray-500 hover:bg-red-50 hover:text-red-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 dark:text-gray-400 dark:hover:bg-red-950 dark:hover:text-red-300">
                        <x-ui.icon name="trash" class="size-5" />
                        <span class="sr-only" x-text="'Remove feature ' + (index + 1)">Remove feature</span>
                    </button>
                </li>
            </template>
        </ul>

        <x-ui.button variant="secondary" size="sm" icon="plus"
                     x-on:click="features.push(''); $nextTick(() => document.getElementById('feature-' + (features.length - 1))?.focus())">Add feature</x-ui.button>
    </div>
</x-ui.card>
