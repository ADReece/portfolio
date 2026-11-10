{{--
    Launch offer price for a paid plan on monthly billing (App\Billing\LaunchOffer).
    Shows the full price struck through, the offer price and when it ends.
    Callers render the normal price instead when $offer->appliesTo(...) is false.
    Expects: $plan, $offer, $currencySymbol; optional $size ('lg' | 'sm').
--}}
@php($size = $size ?? 'lg')
@php($discounted = $offer->discountedMonthlyPrice($plan))
<p class="mt-3 flex flex-wrap items-baseline gap-x-2">
    <span class="{{ $size === 'lg' ? 'text-lg' : 'text-sm' }} text-gray-500 line-through tabular-nums dark:text-gray-400"><span class="sr-only">Usually </span>{{ $currencySymbol }}{{ $plan->priceFor('month') }}</span>
    <span class="{{ $size === 'lg' ? 'text-4xl' : 'text-xl' }} font-bold tracking-tight text-gray-900 tabular-nums dark:text-gray-100">{{ $currencySymbol }}{{ $discounted }}</span>
    <span class="text-sm text-gray-600 dark:text-gray-400">/ month</span>
</p>
<p class="mt-1 text-sm font-medium text-green-700 dark:text-green-400">
    {{ $currencySymbol }}{{ $discounted }}/mo for your first {{ $offer->months() }} months, then {{ $currencySymbol }}{{ $plan->priceFor('month') }}
</p>
<p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">Launch offer ends {{ $offer->endsLabel() }}</p>
