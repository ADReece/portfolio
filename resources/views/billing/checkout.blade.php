<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Checkout') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg p-8">
                <h3 class="text-2xl font-bold mb-6 dark:text-white">Subscribe to {{ $plan->name }}</h3>

                <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-lg font-semibold dark:text-white">{{ $plan->name }} Plan</span>
                        <span class="text-2xl font-bold dark:text-white">{{ $currencySymbol }}{{ $plan->priceFor($interval ?? request('interval','month')) }}/{{ ($interval ?? request('interval','month')) === 'year' ? 'yr' : 'mo' }}</span>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        @foreach($plan->features as $feature)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ $feature }}
                        </li>
                        @endforeach
                    </ul>
                </div>

                @php($stripePk = config('cashier.key'))
                @if(empty($stripePk))
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-sm mb-4">
                        Stripe publishable key is not configured. Please set STRIPE_KEY=pk_test_xxx in your .env and reload this page.
                    </div>
                @endif

                <form id="payment-form" action="{{ route('checkout.process', $plan) }}" method="POST">
                    @csrf
                    <input type="hidden" name="interval" value="{{ $interval ?? request('interval','month') }}" />

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Card Information</label>
                        <div id="card-element" class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-900">
                            <!-- Stripe Card Element will be inserted here -->
                        </div>
                        <div id="card-errors" class="text-red-600 dark:text-red-400 text-sm mt-2"></div>
                    </div>

                    <button type="submit" id="submit-button" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                        Subscribe Now
                    </button>
                </form>

                <p class="text-sm text-gray-600 dark:text-gray-400 mt-4 text-center">
                    You can cancel anytime. By subscribing, you agree to our
                    <a href="{{ route('terms') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Terms of Service</a>.
                </p>
            </div>
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
                showError('Payment setup incomplete: Stripe publishable key is missing.');
                return;
            }

            if (typeof window.Stripe === 'undefined') {
                showError('Stripe.js failed to load. If you use an ad-blocker or privacy extension, please whitelist js.stripe.com and reload.');
                return;
            }

            try {
                const stripe = Stripe(publishableKey);
                const elements = stripe.elements();
                const cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#32325d',
                            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                            '::placeholder': { color: '#aab7c4' }
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
                    submitButton.textContent = 'Processing...';

                    const { paymentMethod, error } = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardElement,
                        billing_details: { email: @json(auth()->user()->email) }
                    });

                    if (error) {
                        showError(error.message);
                        submitButton.disabled = false;
                        submitButton.textContent = 'Subscribe Now';
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
