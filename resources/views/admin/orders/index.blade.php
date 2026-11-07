<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Orders</h2></x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Buyer</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($orders as $order)
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">#{{ $order->id }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $order->user->email ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $currencySymbol }}{{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ ucfirst($order->status) }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $order->created_at->toDayDateTimeString() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $orders->links() }}</div>
            <div class="mt-6 bg-white dark:bg-gray-800 rounded p-4">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Total Completed GMV</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $currencySymbol }}{{ number_format($totalCompleted, 2) }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

