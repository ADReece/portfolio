<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Total Users</div>
                    <div class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $totalUsers }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">New Users (30d)</div>
                    <div class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $newUsers30 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Active Subscribers</div>
                    <div class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $activeSubscribers }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                    <div class="text-gray-500 dark:text-gray-400 text-sm">Orders (30d)</div>
                    <div class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $ordersLast30 }}</div>
                </div>
            </div>

            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
                <div class="text-gray-500 dark:text-gray-400 text-sm">GMV (30 days)</div>
                <div class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $currencySymbol }}{{ number_format($gmvLast30, 2) }}</div>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="{{ route('admin.users.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm hover:ring-2 hover:ring-indigo-600 transition">
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Users</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Browse, search, and manage users</p>
                </a>
                <a href="{{ route('admin.plans.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm hover:ring-2 hover:ring-indigo-600 transition">
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Subscription Plans</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Manage pricing tiers and features</p>
                </a>
                <a href="{{ route('admin.subscriptions.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm hover:ring-2 hover:ring-indigo-600 transition">
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Subscriptions</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Monitor active and cancelled subscriptions</p>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm hover:ring-2 hover:ring-indigo-600 transition">
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Orders</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Review platform sales and payouts</p>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm hover:ring-2 hover:ring-indigo-600 transition">
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">Settings</div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Configure currency and Stripe integration</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

