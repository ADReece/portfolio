<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Basic Information -->
    <div class="col-span-2">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Basic Information</h3>
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan Name *</label>
        <input type="text" name="name" id="name" value="{{ old('name', $plan->name ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('name') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug * <span class="text-xs">(URL-friendly identifier)</span></label>
        <input type="text" name="slug" id="slug" value="{{ old('slug', $plan->slug ?? '') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('slug') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Price (£) *</label>
        <input type="number" step="0.01" name="price" id="price" value="{{ old('price', $plan->price ?? '0.00') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('price') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="annual_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Annual Price (£)</label>
        <input type="number" step="0.01" name="annual_price" id="annual_price" value="{{ old('annual_price', $plan->annual_price ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('annual_price') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="annual_discount_percent" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Annual Discount %</label>
        <input type="number" name="annual_discount_percent" id="annual_discount_percent" value="{{ old('annual_discount_percent', $plan->annual_discount_percent ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('annual_discount_percent') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order *</label>
        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $plan->sort_order ?? '0') }}" required
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('sort_order') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Stripe Configuration -->
    <div class="col-span-2 mt-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Stripe Configuration</h3>
    </div>

    <div>
        <label for="stripe_product_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stripe Product ID</label>
        <input type="text" name="stripe_product_id" id="stripe_product_id" value="{{ old('stripe_product_id', $plan->stripe_product_id ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('stripe_product_id') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="stripe_price_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stripe Monthly Price ID</label>
        <input type="text" name="stripe_price_id" id="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('stripe_price_id') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div class="col-span-2">
        <label for="annual_stripe_price_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stripe Annual Price ID</label>
        <input type="text" name="annual_stripe_price_id" id="annual_stripe_price_id" value="{{ old('annual_stripe_price_id', $plan->annual_stripe_price_id ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('annual_stripe_price_id') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Limits -->
    <div class="col-span-2 mt-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Limits <span class="text-sm font-normal">(leave blank for unlimited)</span></h3>
    </div>

    <div>
        <label for="photo_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Photo Limit</label>
        <input type="number" name="photo_limit" id="photo_limit" value="{{ old('photo_limit', $plan->photo_limit ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('photo_limit') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="collection_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Collection Limit</label>
        <input type="number" name="collection_limit" id="collection_limit" value="{{ old('collection_limit', $plan->collection_limit ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        @error('collection_limit') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Features -->
    <div class="col-span-2 mt-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Features</h3>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="private_collections" id="private_collections" value="1" {{ old('private_collections', $plan->private_collections ?? false) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="private_collections" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Private Collections</label>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="watermarking" id="watermarking" value="1" {{ old('watermarking', $plan->watermarking ?? false) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="watermarking" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Watermarking</label>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="selling" id="selling" value="1" {{ old('selling', $plan->selling ?? false) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="selling" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Selling</label>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="video_upload" id="video_upload" value="1" {{ old('video_upload', $plan->video_upload ?? false) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="video_upload" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Video Upload</label>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="custom_templates" id="custom_templates" value="1" {{ old('custom_templates', $plan->custom_templates ?? false) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="custom_templates" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Custom Templates</label>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="active" id="active" value="1" {{ old('active', $plan->active ?? true) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="recommended" id="recommended" value="1" {{ old('recommended', $plan->recommended ?? false) ? 'checked' : '' }}
               class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <label for="recommended" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Recommended</label>
    </div>

    <!-- Features List -->
    <div class="col-span-2 mt-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Feature List</label>
        <div id="features-container">
            @php($features = old('features', $plan->features ?? []))
            @if(count($features) > 0)
                @foreach($features as $index => $feature)
                <div class="flex gap-2 mb-2 feature-item">
                    <input type="text" name="features[]" value="{{ $feature }}"
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" onclick="this.parentElement.remove()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg">
                        Remove
                    </button>
                </div>
                @endforeach
            @else
                <div class="flex gap-2 mb-2 feature-item">
                    <input type="text" name="features[]" value=""
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" onclick="this.parentElement.remove()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg">
                        Remove
                    </button>
                </div>
            @endif
        </div>
        <button type="button" onclick="addFeature()" class="mt-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
            Add Feature
        </button>
    </div>
</div>

<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2 mb-2 feature-item';
    div.innerHTML = `
        <input type="text" name="features[]" value=""
               class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
        <button type="button" onclick="this.parentElement.remove()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg">
            Remove
        </button>
    `;
    container.appendChild(div);
}
</script>

