@php
    $prices = $enabledProducts->mapWithKeys(fn ($p) => [$p->id => (float) ($p->pivot->retail_price ?? 29.99)]);
    $inputClass = 'mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500';
    $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-200';
@endphp
<x-profile-layout :user="$photographer">
    <div class="max-w-5xl mx-auto px-4 py-10"
         x-data="{
            prices: @js($prices),
            product: @js(old('prodigi_product_id', $enabledProducts->count() === 1 ? $enabledProducts->first()->id : '')),
            quantity: {{ (int) old('quantity', 1) }},
            symbol: @js($currencySymbol),
            get unit() { return this.prices[this.product] ?? 0 },
            get total() { return this.unit * Math.max(1, Math.min(10, this.quantity || 1)) },
            money(v) { return this.symbol + v.toFixed(2) },
         }">
        @if($returnUrl)
            <a href="{{ $returnUrl }}" class="inline-flex items-center gap-1 text-sm text-gray-600 dark:text-gray-300 hover:underline mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to gallery
            </a>
        @endif

        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Order a print</h1>
        <p class="text-gray-600 dark:text-gray-300 mt-1 mb-8">Printed and shipped for {{ $photographer->name ?? $photographer->username }}. You'll pay securely with Stripe on the next step.</p>

        @if($errors->any())
            <div class="mb-6 rounded-md border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 p-4 text-sm text-red-800 dark:text-red-200" role="alert">
                Please check the highlighted fields below.
            </div>
        @endif

        <form method="POST" action="{{ route('print-purchase.store', ['photo' => $photo->id]) }}" class="grid gap-6 lg:grid-cols-[1fr_20rem] items-start">
            @csrf

            <div class="space-y-6 min-w-0">
                <fieldset class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-5">
                    <legend class="sr-only">Print options</legend>
                    <div>
                        <label for="prodigi_product_id" class="{{ $labelClass }}">Print size and type</label>
                        <select id="prodigi_product_id" name="prodigi_product_id" x-model="product" class="{{ $inputClass }}" required>
                            <option value="">Choose a print</option>
                            @foreach($enabledProducts as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} · {{ $currencySymbol }}{{ number_format($prices[$product->id], 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('prodigi_product_id')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="quantity" class="{{ $labelClass }}">Quantity</label>
                        <input id="quantity" type="number" min="1" max="10" name="quantity" x-model.number="quantity" class="{{ $inputClass }} w-32!" required />
                        @error('quantity')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <fieldset class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <legend class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 float-left w-full">Your details</legend>
                    <div>
                        <label for="customer_name" class="{{ $labelClass }}">Full name</label>
                        <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" class="{{ $inputClass }}" />
                        @error('customer_name')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="customer_email" class="{{ $labelClass }}">Email</label>
                        <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" autocomplete="email" class="{{ $inputClass }}" required />
                        @error('customer_email')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="customer_phone" class="{{ $labelClass }}">Phone number</label>
                        <input id="customer_phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" aria-describedby="customer_phone_help" class="{{ $inputClass }}" required />
                        <p id="customer_phone_help" class="text-xs text-gray-500 dark:text-gray-400 mt-1">The courier uses this if there's a problem with delivery.</p>
                        @error('customer_phone')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                <fieldset class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <legend class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 float-left w-full">Delivery address</legend>
                    <div class="md:col-span-2">
                        <label for="shipping_country_code" class="{{ $labelClass }}">Country</label>
                        <select id="shipping_country_code" name="shipping_country_code" autocomplete="country" class="{{ $inputClass }}" required>
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}" @selected(old('shipping_country_code', $defaultCountry) === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('shipping_country_code')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="shipping_address_line1" class="{{ $labelClass }}">Address line 1</label>
                        <input id="shipping_address_line1" type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1') }}" autocomplete="address-line1" class="{{ $inputClass }}" required />
                        @error('shipping_address_line1')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="shipping_address_line2" class="{{ $labelClass }}">Address line 2 <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span></label>
                        <input id="shipping_address_line2" type="text" name="shipping_address_line2" value="{{ old('shipping_address_line2') }}" autocomplete="address-line2" class="{{ $inputClass }}" />
                        @error('shipping_address_line2')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="shipping_city" class="{{ $labelClass }}">Town or city</label>
                        <input id="shipping_city" type="text" name="shipping_city" value="{{ old('shipping_city') }}" autocomplete="address-level2" class="{{ $inputClass }}" required />
                        @error('shipping_city')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="shipping_county" class="{{ $labelClass }}">County, state or region</label>
                        <input id="shipping_county" type="text" name="shipping_county" value="{{ old('shipping_county') }}" autocomplete="address-level1" class="{{ $inputClass }}" required />
                        @error('shipping_county')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="shipping_postcode" class="{{ $labelClass }}">Postcode or ZIP</label>
                        <input id="shipping_postcode" type="text" name="shipping_postcode" value="{{ old('shipping_postcode') }}" autocomplete="postal-code" class="{{ $inputClass }}" required />
                        @error('shipping_postcode')<p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>
                </fieldset>
            </div>

            <aside class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 space-y-4 lg:sticky lg:top-6" aria-labelledby="summary-heading">
                <h2 id="summary-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Order summary</h2>
                <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ? 'Photo: '.$photo->caption : 'The photo you are ordering' }}" class="rounded-md w-full max-h-64 object-contain bg-gray-100 dark:bg-gray-900" />
                <dl class="text-sm space-y-2" aria-live="polite">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600 dark:text-gray-300">Price per print</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100" x-text="unit ? money(unit) : '–'">–</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600 dark:text-gray-300">Quantity</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100" x-text="quantity || 1">1</dd></div>
                    <div class="flex justify-between gap-4 border-t border-gray-200 dark:border-gray-700 pt-2 font-semibold"><dt class="text-gray-900 dark:text-gray-100">Total</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100" x-text="unit ? money(total) : '–'">–</dd></div>
                </dl>
                <p class="text-xs text-gray-500 dark:text-gray-400">Stripe shows the final amount, including any taxes, before you pay.</p>
                <button type="submit" class="w-full inline-flex justify-center items-center px-5 py-3 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                    Continue to payment
                </button>
            </aside>
        </form>
    </div>
</x-profile-layout>
