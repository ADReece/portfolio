@php
    $user = auth()->user();
    $interval = ($interval ?? request('interval', 'month')) === 'year' ? 'year' : 'month';
    $maxSaving = (int) $plans->max('annual_discount_percent');
    $subscription = $user?->subscription('default');
    $isSubscribed = $subscription && $subscription->valid();
    $onGrace = $subscription && $subscription->onGracePeriod();
    $unlimited = $user?->isFeatureOverrideActive();
    $nextBilling = null;
    if ($isSubscribed && ! $onGrace && ! $unlimited) {
        // Ask Stripe at most every 10 minutes per user, rather than on every page load.
        $nextBilling = \Illuminate\Support\Facades\Cache::remember('billing.next.'.$user->id, 600, function () use ($subscription) {
            try {
                $end = $subscription->asStripeSubscription()->current_period_end ?? null;
                return $end ? \Carbon\Carbon::createFromTimestamp($end) : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }
@endphp

<x-app-layout :title="$user ? 'Plan & billing' : 'Pricing'">
    <div class="mx-auto max-w-6xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        @auth
            <x-ui.page-header title="Plan & billing" description="Your plan, usage and payment details." />

            <x-ui.card :padding="false">
                <div class="grid gap-6 p-5 sm:p-6 md:grid-cols-[1fr_auto] md:items-start">
                    <div class="space-y-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Current plan</p>
                        <p class="flex flex-wrap items-center gap-2 text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $user->subscriptionPlan->name ?? 'No plan' }}
                            @if($unlimited)<x-ui.badge tone="green">Unlimited access</x-ui.badge>@endif
                            @if($onGrace)<x-ui.badge tone="amber">Cancels {{ $subscription->ends_at->toFormattedDateString() }}</x-ui.badge>@endif
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if($unlimited)
                                You have access to every feature.
                            @elseif($onGrace)
                                You keep your plan until {{ $subscription->ends_at->toFormattedDateString() }}. Resume any time before then.
                            @elseif($isSubscribed)
                                {{ $nextBilling ? 'Renews on '.$nextBilling->toFormattedDateString().'.' : 'Active subscription.' }}
                            @elseif($user->subscriptionPlan?->isFree())
                                Upgrade any time for private galleries, print sales and your own branding.
                            @elseif($user->subscriptionPlan)
                                Your account is on this plan.
                            @else
                                Choose a plan below to get started.
                            @endif
                        </p>
                    </div>
                    @if($isSubscribed)
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button variant="secondary" icon="credit-card" :href="route('billing.portal')">Payment details & invoices</x-ui.button>
                            @if($onGrace)
                                <form action="{{ route('subscription.resume') }}" method="POST">
                                    @csrf
                                    <x-ui.button type="submit">Resume subscription</x-ui.button>
                                </form>
                            @else
                                <form action="{{ route('subscription.cancel') }}" method="POST"
                                      data-confirm="You'll keep your current plan until the end of this billing period. You can resume any time before then."
                                      data-confirm-title="Cancel your subscription?" data-confirm-button="Cancel subscription" data-confirm-danger>
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost">Cancel subscription</x-ui.button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
                @if($user->subscriptionPlan)
                    @php
                        $usage = [
                            ['Photos', $user->photos()->count(), $unlimited ? null : $user->subscriptionPlan->photo_limit],
                            ['Collections', $user->collections()->count(), $unlimited ? null : $user->subscriptionPlan->collection_limit],
                        ];
                    @endphp
                    <dl class="grid gap-px border-t border-gray-200 bg-gray-200 sm:grid-cols-2 dark:border-gray-700 dark:bg-gray-700">
                        @foreach($usage as [$label, $used, $limit])
                            <div class="bg-white px-5 py-4 sm:px-6 dark:bg-gray-800">
                                <dt class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-900 tabular-nums dark:text-gray-100">
                                    {{ number_format($used) }} <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ $limit ? 'of '.number_format($limit) : '· no limit' }}</span>
                                </dd>
                                @if($limit)
                                    <dd class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-label="{{ $label }} used" aria-valuemin="0" aria-valuemax="{{ $limit }}" aria-valuenow="{{ $used }}">
                                        <span class="block h-full {{ $used >= $limit ? 'bg-red-500' : 'bg-brand-500' }}" style="width: {{ min(100, round($used / $limit * 100)) }}%"></span>
                                    </dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                @endif
            </x-ui.card>
        @endauth

        <section aria-labelledby="plans-heading" class="space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="plans-heading" class="{{ $user ? 'text-xl' : 'text-3xl sm:text-4xl' }} font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {{ $user ? 'Compare plans' : 'Simple pricing for photographers' }}
                    </h2>
                    @guest<p class="mt-2 text-gray-600 dark:text-gray-400">Start free. Upgrade when you need private galleries, print sales and your own branding.</p>@endguest
                </div>
                <nav aria-label="Billing period" class="inline-flex rounded-control bg-gray-100 p-1 dark:bg-gray-800">
                    @foreach(['month' => 'Monthly', 'year' => 'Yearly'] as $value => $label)
                        <a href="{{ route('pricing', ['interval' => $value]) }}" @if($interval === $value) aria-current="page" @endif
                           @class(['rounded-[calc(var(--radius-control)-2px)] px-4 py-1.5 text-sm font-medium focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500',
                                   'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' => $interval === $value,
                                   'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $interval !== $value])>
                            {{ $label }}
                            @if($value === 'year' && $maxSaving)<span class="ml-1 text-xs font-semibold text-green-700 dark:text-green-400">save up to {{ $maxSaving }}%</span>@endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <ul role="list" class="grid gap-6 md:grid-cols-3">
                @foreach($plans as $plan)
                    @php($isCurrent = $user && $user->subscription_plan_id === $plan->id)
                    <li @class(['relative flex flex-col rounded-card bg-white p-6 shadow-xs ring-1 dark:bg-gray-800',
                                'ring-2 ring-brand-500' => $plan->recommended,
                                'ring-gray-200 dark:ring-gray-700' => ! $plan->recommended])>
                        @if($plan->recommended)
                            <x-ui.badge tone="brand" class="absolute -top-3 left-6">Most popular</x-ui.badge>
                        @endif
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h3>
                        <p class="mt-3 flex items-baseline gap-1">
                            <span class="text-4xl font-bold tracking-tight text-gray-900 tabular-nums dark:text-gray-100">{{ $currencySymbol }}{{ $plan->priceFor($interval) }}</span>
                            @unless($plan->isFree())<span class="text-sm text-gray-600 dark:text-gray-400">/ {{ $interval }}</span>@endunless
                        </p>
                        @if($interval === 'year' && $plan->annual_discount_percent)
                            <p class="mt-1 text-sm font-medium text-green-700 dark:text-green-400">{{ $plan->annual_discount_percent }}% less than monthly</p>
                        @endif

                        <ul role="list" class="mt-6 flex-1 space-y-2.5 text-sm text-gray-700 dark:text-gray-300">
                            @foreach($plan->features ?? [] as $feature)
                                <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-0.5 size-4 shrink-0 text-green-600 dark:text-green-400" />{{ $feature }}</li>
                            @endforeach
                        </ul>

                        <div class="mt-6">
                            @if($isCurrent)
                                <x-ui.button variant="secondary" class="w-full" disabled>Your current plan</x-ui.button>
                            @elseif($user)
                                <x-ui.button class="w-full" :variant="$plan->recommended ? 'primary' : 'secondary'"
                                             :href="route('checkout', ['plan' => $plan->id, 'interval' => $interval])">
                                    {{ $plan->isFree() ? 'Switch to '.$plan->name : 'Choose '.$plan->name }}
                                </x-ui.button>
                            @else
                                <x-ui.button class="w-full" :variant="$plan->recommended ? 'primary' : 'secondary'"
                                             :href="route('register', ['plan' => $plan->id, 'interval' => $interval])">
                                    {{ $plan->isFree() ? 'Start free' : 'Choose '.$plan->name }}
                                </x-ui.button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="text-sm text-gray-600 dark:text-gray-400">Prices in {{ \App\Support\Currency::code() }}. Change or cancel any time; cancelling keeps your plan until the end of the period you've paid for.</p>
        </section>
    </div>
</x-app-layout>
