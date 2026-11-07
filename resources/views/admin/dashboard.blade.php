<x-app-layout title="Admin">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Admin" description="Platform activity at a glance." />

        <section aria-labelledby="admin-stats-heading">
            <h2 id="admin-stats-heading" class="sr-only">Overview</h2>
            @php
                $tiles = [
                    ['Total users', number_format($totalUsers), null],
                    ['New users', number_format($newUsers30), 'Last 30 days'],
                    ['Active subscribers', number_format($activeSubscribers), null],
                    ['Orders', number_format($ordersLast30), 'Last 30 days'],
                    ['Sales volume', $currencySymbol.number_format($gmvLast30, 2), 'Completed orders, last 30 days'],
                ];
            @endphp
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                @foreach($tiles as [$label, $value, $hint])
                    <div @class(['rounded-card bg-white p-5 shadow-xs ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700', 'col-span-2 lg:col-span-1' => $loop->last])>
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold tracking-tight text-gray-900 tabular-nums dark:text-gray-100">{{ $value }}</dd>
                        @if($hint)<dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</dd>@endif
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="admin-manage-heading" class="space-y-3">
            <h2 id="admin-manage-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">Manage</h2>
            @php
                $links = [
                    ['Users', 'Search users, grant access and impersonate.', 'users', route('admin.users.index')],
                    ['Subscription plans', 'Pricing tiers, limits and features.', 'rectangle-stack', route('admin.plans.index')],
                    ['Subscriptions', 'Active and cancelled Stripe subscriptions.', 'credit-card', route('admin.subscriptions.index')],
                    ['Orders', 'Print sales across the platform.', 'shopping-bag', route('admin.orders.index')],
                    ['Settings', 'Currency and platform configuration.', 'cog-6-tooth', route('admin.settings.index')],
                ];
            @endphp
            <ul role="list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($links as [$label, $description, $icon, $href])
                    <li>
                        <a href="{{ $href }}" class="group flex h-full items-start gap-4 rounded-card bg-white p-5 shadow-xs ring-1 ring-gray-200 transition hover:ring-gray-300 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:bg-gray-800 dark:ring-gray-700 dark:hover:ring-gray-600">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                                <x-ui.icon :name="$icon" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $label }}</span>
                                <span class="mt-0.5 block text-sm text-gray-600 dark:text-gray-400">{{ $description }}</span>
                            </span>
                            <x-ui.icon name="chevron-right" class="size-5 shrink-0 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-app-layout>
