<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pricing Plans') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @auth
                @php
                    $subscription = auth()->user()->subscription('default');
                    $isSubscribed = $subscription && $subscription->valid();
                    $onGrace = $subscription && $subscription->onGracePeriod();
                    $nextBilling = null;
                    if ($isSubscribed) {
                        try {
                            $stripeSub = $subscription->asStripeSubscription();
                            if (isset($stripeSub->current_period_end)) {
                                $nextBilling = \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end);
                            }
                        } catch (\Exception $e) {
                            $nextBilling = null;
                        }
                    }
                @endphp
                @if($isSubscribed)
                    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                    @if($onGrace)
                                        Your subscription is scheduled to cancel
                                    @else
                                        You're subscribed to {{ auth()->user()->subscriptionPlan->name }}
                                        @if(auth()->user()->isFeatureOverrideActive())
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200">Unlimited</span>
                                        @endif
                                    @endif
                                </h3>
                                <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                                    @if($onGrace)
                                        Your subscription will end on {{ optional($subscription->ends_at)->format('M d, Y') }}. You can resume it anytime before then.
                                    @else
                                        @if(auth()->user()->isFeatureOverrideActive())
                                            You have unlimited access to all features.
                                        @else
                                            Next billing date: {{ $nextBilling ? $nextBilling->format('M d, Y') : 'Not available' }}
                                        @endif
                                    @endif
                                </p>
                            </div>
                            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 whitespace-nowrap ml-4">Manage →</a>
                        </div>
                    </div>
                @endif
            @endauth

            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Choose Your Plan</h1>
                <p class="text-xl text-gray-600 dark:text-gray-300">Select the perfect plan for your photography or videography business</p>
            </div>

            @php($interval = $interval ?? request('interval', 'month'))

            <div class="flex justify-center mb-8">
                <div class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <a href="{{ route('pricing', ['interval' => 'month']) }}"
                       class="px-4 py-2 text-sm font-medium {{ $interval === 'month' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                        Monthly
                    </a>
                    <a href="{{ route('pricing', ['interval' => 'year']) }}"
                       class="px-4 py-2 text-sm font-medium {{ $interval === 'year' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                        Yearly <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">(Save {{ $plans->firstWhere('slug','photographer')->annual_discount_percent ?? 0 }}%)</span>
                    </a>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                @foreach($plans as $plan)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 {{ ($plan->recommended ?? false) ? 'ring-2 ring-indigo-600 transform scale-105' : '' }}">
                    @if($plan->recommended)
                    <div class="bg-indigo-600 text-white text-sm font-semibold px-3 py-1 rounded-full inline-block mb-4">RECOMMENDED</div>
                    @endif

                    <h3 class="text-2xl font-bold mb-2 dark:text-white">{{ $plan->name }}</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold dark:text-white">{{ $currencySymbol }}{{ $plan->priceFor($interval) }}</span>
                        @if(!$plan->isFree())
                            <span class="text-gray-600 dark:text-gray-400">/{{ $interval === 'year' ? 'year' : 'month' }}</span>
                        @endif
                        @if($interval === 'year' && $plan->annual_discount_percent)
                            <span class="ml-2 text-xs text-green-600 dark:text-green-400">Save {{ $plan->annual_discount_percent }}%</span>
                        @endif
                    </div>

                    <ul class="space-y-3 mb-8 min-h-[300px]">
                        @foreach($plan->features as $feature)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm dark:text-gray-300">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>

                    @auth
                        @if(auth()->user()->subscription_plan_id === $plan->id)
                            <button disabled class="block w-full text-center bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400 px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
                                Current Plan
                            </button>
                        @else
                            <a href="{{ route('checkout', ['plan' => $plan->id, 'interval' => $interval]) }}"
                               class="block w-full text-center {{ $plan->slug === 'photographer' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }} px-6 py-3 rounded-lg font-semibold transition">
                                {{ $plan->isFree() ? 'Switch to Free' : 'Subscribe Now' }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('checkout', ['plan' => $plan->id, 'interval' => $interval]) }}" class="block w-full text-center {{ $plan->slug === 'photographer' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }} px-6 py-3 rounded-lg font-semibold transition">
                            {{ $plan->isFree() ? 'Choose Free' : 'Subscribe Now' }}
                        </a>
                    @endauth
                </div>
                @endforeach
            </div>

            @auth
                @if(optional(auth()->user())->subscriptionPlan)
                <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-xl font-bold mb-4 dark:text-white">
                        Current Plan: {{ auth()->user()->subscriptionPlan->name }}
                        @if(auth()->user()->isFeatureOverrideActive())
                            <span class="ml-2 px-3 py-1 text-sm rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">Unlimited Access</span>
                        @endif
                    </h3>

                    @if(!auth()->user()->subscriptionPlan->isFree())
                    <div class="flex gap-4">
                        <a href="{{ route('billing.portal') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                            Manage Billing
                        </a>

                        @if(auth()->user()->subscribed('default'))
                            @if(auth()->user()->subscription('default')->onGracePeriod())
                                <form action="{{ route('subscription.resume') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                        Resume Subscription
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('subscription.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel your subscription?');">
                                    @csrf
                                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                                        Cancel Subscription
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                    @endif

                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Photos Used</p>
                            <p class="text-2xl font-bold dark:text-white">
                                {{ auth()->user()->photos()->count() }}
                                @if(auth()->user()->isFeatureOverrideActive())
                                    / <span class="text-green-600 dark:text-green-400">Unlimited</span>
                                @elseif(auth()->user()->subscriptionPlan->photo_limit)
                                    / {{ auth()->user()->subscriptionPlan->photo_limit }}
                                @else
                                    / Unlimited
                                @endif
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Collections Used</p>
                            <p class="text-2xl font-bold dark:text-white">
                                {{ auth()->user()->collections()->count() }}
                                @if(auth()->user()->isFeatureOverrideActive())
                                    / <span class="text-green-600 dark:text-green-400">Unlimited</span>
                                @elseif(auth()->user()->subscriptionPlan->collection_limit)
                                    / {{ auth()->user()->subscriptionPlan->collection_limit }}
                                @else
                                    / Unlimited
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            @endauth
        </div>
    </div>
</x-app-layout>
