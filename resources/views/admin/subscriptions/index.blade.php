<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Subscriptions</h2></x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($subs as $sub)
                        <tr>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $sub->name }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $sub->stripe_status }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $sub->stripe_price }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($sub->created_at)->toDayDateTimeString() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $subs->links() }}</div>
        </div>
    </div>
</x-app-layout>

