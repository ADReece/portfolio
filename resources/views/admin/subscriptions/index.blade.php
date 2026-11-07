@php
    $statusTones = [
        'active' => 'green',
        'trialing' => 'blue',
        'past_due' => 'amber',
        'incomplete' => 'amber',
        'unpaid' => 'red',
        'incomplete_expired' => 'red',
        'canceled' => 'gray',
        'paused' => 'gray',
    ];
@endphp

<x-app-layout title="Subscriptions">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Subscriptions" description="Every Stripe subscription, newest first." />

        <x-ui.card :padding="false">
            @if($subs->isEmpty())
                <x-ui.empty-state icon="credit-card" title="No subscriptions yet" description="Subscriptions appear here once someone pays for a plan." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 sm:pl-6 dark:text-gray-100">User</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Status</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Stripe price</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Ends</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 sm:pr-6 dark:text-gray-100">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($subs as $sub)
                                <tr>
                                    <td class="px-4 py-3 sm:pl-6">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $sub->name }}</div>
                                        <div class="text-gray-600 dark:text-gray-400">{{ $sub->email }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-ui.badge :tone="$statusTones[$sub->stripe_status] ?? 'gray'">{{ ucfirst(str_replace('_', ' ', $sub->stripe_status)) }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $sub->stripe_price ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 tabular-nums dark:text-gray-300">
                                        {{ $sub->ends_at ? \Carbon\Carbon::parse($sub->ends_at)->toFormattedDateString() : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 tabular-nums sm:pr-6 dark:text-gray-300">
                                        <time datetime="{{ \Carbon\Carbon::parse($sub->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($sub->created_at)->toDayDateTimeString() }}</time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        @if($subs->hasPages())
            <div>{{ $subs->links() }}</div>
        @endif
    </div>
</x-app-layout>
