@php
    $currencyOptions = collect(\App\Support\Currency::SYMBOLS)->mapWithKeys(fn ($symbol, $code) => [$code => "$code ($symbol)"])->all();
@endphp

<x-app-layout title="Settings">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Settings" description="Platform-wide configuration." />

        <x-ui.card title="Currency" description="Used for plan prices, print sales and payouts. The symbol is set automatically.">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="max-w-sm">
                    <x-ui.select id="default_currency" name="settings[default_currency]" label="Currency"
                                 :options="$currencyOptions" :selected="\App\Support\Currency::code()" />
                </div>

                <x-ui.button type="submit">Save currency</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Subscription plans" description="Stripe product and price IDs are set on each plan.">
            <x-ui.button variant="secondary" icon-trailing="arrow-right" :href="route('admin.plans.index')">Manage plans</x-ui.button>
        </x-ui.card>
    </div>
</x-app-layout>
