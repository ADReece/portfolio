@php
    $user = auth()->user();
    $connected = (bool) $user->stripe_connect_enabled;
    $canSell = $user->hasFeature('selling');
    $fee = rtrim(rtrim(number_format(config('services.platform_fee_percent'), 2), '0'), '.');
@endphp

<x-app-layout title="Payouts">
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Payouts" description="Get paid for prints your clients order. Payouts go to your own Stripe account." />

        @if($connected)
            <x-ui.card>
                <div class="flex flex-wrap items-start gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300">
                        <x-ui.icon name="check" class="size-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Stripe is connected</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">When a client pays for a print, you receive the price minus the print and delivery cost and our {{ $fee }}% fee.</p>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-200 pt-5 dark:border-gray-700">
                    <x-ui.button variant="secondary" icon="shopping-bag" :href="route('profile.prodigi-products.edit')">Print products and prices</x-ui.button>
                    <form action="{{ route('connect.stripe.disconnect') }}" method="POST"
                          data-confirm="You won't receive payouts for print sales until you connect Stripe again." data-confirm-title="Disconnect Stripe?" data-confirm-button="Disconnect" data-confirm-danger>
                        @csrf
                        <x-ui.button type="submit" variant="ghost">Disconnect Stripe</x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @elseif($comingSoon ?? false)
            <x-ui.card title="Selling prints is coming soon">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Soon your clients will be able to order prints straight from your galleries, and you'll be paid the price you set, minus the print and delivery cost and our fee, into your own Stripe account.
                    We're finishing the payouts side now. Your print products and prices are saved, and you'll be able to connect Stripe here as soon as it's ready.
                </p>
            </x-ui.card>
        @else
            @unless($canSell)
                <x-ui.alert tone="amber" title="Selling prints isn't included in your plan">
                    Upgrade to sell prints to your clients and receive payouts.
                    <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
                </x-ui.alert>
            @endunless

            <x-ui.card title="Connect Stripe">
                <ul role="list" class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    @foreach([
                        'Clients pay by card when they order a print from your galleries.',
                        'We handle printing and delivery.',
                        "You receive the price you set, minus the print and delivery cost and our {$fee}% fee, in your Stripe account.",
                        'Stripe pays out to your bank on its normal schedule.',
                    ] as $point)
                        <li class="flex gap-2"><x-ui.icon name="check" variant="m" class="mt-0.5 size-4 shrink-0 text-green-600 dark:text-green-400" />{{ $point }}</li>
                    @endforeach
                </ul>
                <div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700">
                    @if($canSell)
                        <x-ui.button size="lg" icon-trailing="arrow-top-right-on-square" :href="$connectUrl">Connect with Stripe</x-ui.button>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">You'll sign in to Stripe (or create an account), then come back here.</p>
                    @else
                        <x-ui.button :href="route('pricing')">Compare plans</x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
