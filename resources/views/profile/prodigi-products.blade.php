<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Print Product Availability
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                    These products come directly from your synced Prodigi catalog. All products are enabled by default; uncheck any products you do not want clients to order.
                </p>

                @if (session('status') === 'prodigi-products-updated')
                    <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-sm p-3 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800">
                        Product availability updated.
                    </div>
                @endif

                @if ($products->isEmpty())
                    <div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-sm p-3 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800">
                        No catalog products found yet. Run the sync command: php artisan prodigi:sync-catalog --seed-user-preferences
                    </div>
                @else
                    <form method="POST" action="{{ route('profile.prodigi-products.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($products as $product)
                                <label class="flex items-start gap-3 rounded-sm border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                    <input
                                        type="checkbox"
                                        name="enabled_product_ids[]"
                                        value="{{ $product->id }}"
                                        class="mt-1 rounded-sm border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500"
                                        @checked(in_array($product->id, $enabledProductIds, true))
                                    >

                                    <span>
                                        <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $product->name }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">SKU: {{ $product->sku }}</span>
                                        <span class="block mt-2 text-xs text-gray-500 dark:text-gray-400">Client price (USD)</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.50"
                                            name="retail_prices[{{ $product->id }}]"
                                            value="{{ old('retail_prices.' . $product->id, $pricesByProductId[$product->id] ?? '29.99') }}"
                                            class="mt-1 w-28 rounded-sm border-gray-300 text-sm shadow-xs focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                        @if($product->category)
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded-sm text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $product->category }}
                                            </span>
                                        @endif
                                        @if($product->description)
                                            <span class="block text-xs text-gray-600 dark:text-gray-300 mt-1">{{ \Illuminate\Support\Str::limit($product->description, 120) }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <x-primary-button>Save Product Availability</x-primary-button>
                            <a href="{{ route('profile.customize') }}" class="text-sm text-gray-600 dark:text-gray-300 underline">Back to customization</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
