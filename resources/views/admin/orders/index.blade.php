@php
    $statusTones = [
        'completed' => 'green',
        'processing' => 'blue',
        'pending' => 'amber',
        'failed' => 'red',
        'refunded' => 'gray',
    ];
@endphp

<x-app-layout title="Orders">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Orders" description="Print orders across the platform, newest first." />

        <dl class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-card bg-white p-5 shadow-xs ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed sales volume</dt>
                <dd class="mt-1 text-2xl font-bold tracking-tight text-gray-900 tabular-nums dark:text-gray-100">{{ $currencySymbol }}{{ number_format($totalCompleted, 2) }}</dd>
                <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">All time</dd>
            </div>
        </dl>

        <x-ui.card :padding="false">
            @if($orders->isEmpty())
                <x-ui.empty-state icon="shopping-bag" title="No orders yet" description="Print orders from client galleries will show up here." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 sm:pl-6 dark:text-gray-100">Order</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Buyer</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Total</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Status</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 sm:pr-6 dark:text-gray-100">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 font-medium whitespace-nowrap text-gray-900 tabular-nums sm:pl-6 dark:text-gray-100">#{{ $order->id }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $order->user->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-gray-900 tabular-nums dark:text-gray-100">{{ $currencySymbol }}{{ number_format($order->total, 2) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-ui.badge :tone="$statusTones[$order->status] ?? 'gray'">{{ ucfirst($order->status) }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 tabular-nums sm:pr-6 dark:text-gray-300">
                                        <time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->toDayDateTimeString() }}</time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        @if($orders->hasPages())
            <div>{{ $orders->links() }}</div>
        @endif
    </div>
</x-app-layout>
