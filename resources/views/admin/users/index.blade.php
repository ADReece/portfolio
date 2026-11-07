<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Users</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form class="mb-4" method="GET">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name/email/username" class="w-full md:w-1/2 border border-gray-300 dark:border-gray-700 rounded-sm px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100" />
            </form>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Override</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Admin</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($users as $user)
                        <tr>
                            <td class="px-4 py-2">
                                <div class="text-gray-900 dark:text-gray-100 font-semibold">{{ $user->name }}</div>
                                <div class="text-gray-600 dark:text-gray-400 text-sm">{{ $user->email }}</div>
                                <div class="text-gray-600 dark:text-gray-400 text-xs">Joined {{ $user->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $user->subscriptionPlan->name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <div class="text-gray-800 dark:text-gray-200">{{ $user->feature_override ? 'Enabled' : 'Disabled' }}</div>
                                @if($user->feature_override)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Expires: {{ $user->feature_override_expires_at ? $user->feature_override_expires_at->toDateString() : 'Never' }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $user->is_admin ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-2 space-x-2">
                                <form action="{{ route('admin.users.toggle-override', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="px-3 py-1 rounded-sm bg-indigo-600 text-white text-sm">{{ $user->feature_override ? 'Disable Override' : 'Enable Override' }}</button>
                                </form>
                                <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="px-3 py-1 rounded-sm bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 text-sm">{{ $user->is_admin ? 'Revoke Admin' : 'Make Admin' }}</button>
                                </form>
                                @if(!$user->is_admin && $user->id !== auth()->id())
                                    <form action="{{ route('admin.users.impersonate', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button class="px-3 py-1 rounded-sm bg-purple-600 text-white text-sm hover:bg-purple-700">Impersonate</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.users.override-expiry', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="date" name="expires_at" class="border border-gray-300 dark:border-gray-700 rounded-sm px-2 py-1 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-gray-100" />
                                    <button class="px-3 py-1 rounded-sm bg-green-600 text-white text-sm">Set Expiry</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>

