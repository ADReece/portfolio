@php
    $interval = ($interval ?? request('interval', 'month')) === 'year' ? 'year' : 'month';
    $stripePk = config('cashier.key');
@endphp
<x-app-layout title="Checkout">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="'Subscribe to '.$plan->name" :back="route('pricing', ['interval' => $interval])" back-label="Plans" />

        <div class="grid items-start gap-6 md:grid-cols-[1fr_20rem]">
            <x-ui.card title="Payment">
                @if(empty($stripePk))
                    <x-ui.alert tone="amber" title="Payments aren't available right now">
                        Please try again later or contact {{ config('app.support_email') }}.
                        @if(config('app.debug'))<br><span class="text-xs">Developer note: set STRIPE_KEY in .env.</span>@endif
                    </x-ui.alert>
                @endif
                <form id="payment-form" action="{{ route('checkout.process', $plan) }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="interval" value="{{ $interval }}" />
                    <div class="space-y-1">
                        <span id="card-label" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Card details</span>
                        <div id="card-element" aria-labelledby="card-label"
                             class="rounded-control border border-gray-300 bg-white px-3 py-3 shadow-xs dark:border-gray-600 dark:bg-gray-900"></div>
                        <p id="card-errors" role="alert" class="text-sm text-red-700 dark:text-red-400"></p>
                    </div>
                    <x-ui.button type="submit" id="submit-button" size="lg" class="w-full" icon="lock-closed" :disabled="empty($stripePk)">
                        @php($offer = app(\App\Billing\LaunchOffer::class))
                        Pay {{ $currencySymbol }}{{ $offer->appliesTo($plan, $interval) ? $offer->discountedMonthlyPrice($plan) : $plan->priceFor($interval) }} and subscribe
                    </x-ui.button>
                    <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                        Cancel any time. By subscribing you agree to our <a href="{{ route('terms') }}" class="font-medium text-brand-600 underline-offset-4 hover:underline dark:text-brand-400">Terms of Service</a>.
                    </p>
                </form>
            </x-ui.card>

            <x-ui.card title="Summary">
                <div class="space-y-4">
                    <div class="flex items-baseline justify-between gap-4">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</span>
                        @unless(app(\App\Billing\LaunchOffer::class)->appliesTo($plan, $interval))
                        <span class="text-right">
                            <span class="text-xl font-bold text-gray-900 tabular-nums dark:text-gray-100">{{ $currencySymbol }}{{ $plan->priceFor($interval) }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">/ {{ $interval }}</span>
                        </span>
                        @endunless
                    </div>
                    @if(app(\App\Billing\LaunchOffer::class)->appliesTo($plan, $interval))
                        @include('partials.launch-offer-price', ['plan' => $plan, 'offer' => app(\App\Billing\LaunchOffer::class), 'size' => 'sm'])
                    @endif
                    <ul role="list" class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @foreach($plan->features ?? [] as $feature)
                            <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-0.5 size-4 shrink-0 text-green-600 dark:text-green-400" />{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <p class="border-t border-gray-200 pt-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">Billed {{ $interval === 'year' ? 'yearly' : 'monthly' }} in {{ \App\Support\Currency::code() }}. Payments are processed securely by Stripe.</p>
                </div>
            </x-ui.card>
        </div>
    </div>
    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (function(){
            const publishableKey = @json(config('cashier.key'));
            const cardErrorsEl = document.getElementById('card-errors');

            function showError(msg){
                if (cardErrorsEl) cardErrorsEl.textContent = msg;
                console.error(msg);
            }

            if (!publishableKey) {
                return; // the page already explains payments aren't available
            }

            if (typeof window.Stripe === 'undefined') {
                showError('The secure card form didn’t load. If you use an ad blocker, allow js.stripe.com and reload the page.');
                return;
            }

            try {
                const stripe = Stripe(publishableKey);
                const elements = stripe.elements();
                const cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '16px',
                            // Match the page theme so card numbers stay readable in dark mode.
                            color: (document.documentElement.classList.contains('dark') || (!document.documentElement.classList.contains('light') && window.matchMedia('(prefers-color-scheme: dark)').matches)) ? '#f3f4f6' : '#111827',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                            '::placeholder': { color: '#9ca3af' }
                        },
                        invalid: { color: '#fa755a', iconColor: '#fa755a' }
                    }
                });

                const mountPoint = document.getElementById('card-element');
                if (!mountPoint) {
                    showError('Unable to find card element container on the page.');
                    return;
                }
                cardElement.mount('#card-element');

                cardElement.on('change', function(event) {
                    if (cardErrorsEl) cardErrorsEl.textContent = event.error ? event.error.message : '';
                });

                const form = document.getElementById('payment-form');
                const submitButton = document.getElementById('submit-button');
                if (!form || !submitButton) return;

                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    submitButton.disabled = true;
                    submitButton.dataset.label = submitButton.innerHTML;
                    submitButton.textContent = 'Processing…';

                    const { paymentMethod, error } = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardElement,
                        billing_details: { email: @json(auth()->user()->email) }
                    });

                    if (error) {
                        showError(error.message);
                        submitButton.disabled = false;
                        submitButton.innerHTML = submitButton.dataset.label;
                    } else {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'payment_method';
                        hiddenInput.value = paymentMethod.id;
                        form.appendChild(hiddenInput);
                        form.submit();
                    }
                });
            } catch (e) {
                showError('An error occurred initializing payment: ' + (e && e.message ? e.message : e));
            }
        })();
    </script>
    @endpush
</x-app-layout>
