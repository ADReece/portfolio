<x-app-layout title="Users">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Users" description="Search accounts, grant unlimited access, manage admins and sign in as a user to help them." />

        <form method="GET" role="search" class="flex flex-wrap items-end gap-3">
            <x-ui.input type="search" name="search" label="Search users" :value="request('search')"
                        placeholder="Name, email or username" wrapper-class="w-full sm:w-96" />
            <x-ui.button type="submit" variant="secondary" icon="magnifying-glass">Search</x-ui.button>
            @if(request('search'))
                <x-ui.button variant="ghost" :href="route('admin.users.index')">Clear</x-ui.button>
            @endif
        </form>

        <x-ui.card :padding="false">
            @if($users->isEmpty())
                <x-ui.empty-state icon="users" title="No users found"
                                  description="{{ request('search') ? 'Nobody matches that search. Try a different name, email or username.' : 'Nobody has signed up yet.' }}" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 sm:pl-6 dark:text-gray-100">User</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Plan</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Unlimited access</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Role</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 sm:pr-6 dark:text-gray-100">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($users as $user)
                                <tr class="align-top">
                                    <td class="px-4 py-4 sm:pl-6">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</div>
                                        <div class="text-gray-600 dark:text-gray-400">{{ $user->email }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Joined {{ $user->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $user->subscriptionPlan->name ?? '—' }}</td>
                                    <td class="px-4 py-4">
                                        @if($user->feature_override)
                                            <x-ui.badge tone="green" icon="check">On</x-ui.badge>
                                            <div class="mt-1 text-xs whitespace-nowrap text-gray-500 tabular-nums dark:text-gray-400">
                                                Expires {{ $user->feature_override_expires_at ? $user->feature_override_expires_at->toDateString() : 'never' }}
                                            </div>
                                        @else
                                            <x-ui.badge>Off</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($user->is_admin)
                                            <x-ui.badge tone="brand" icon="shield-check">Admin</x-ui.badge>
                                        @else
                                            <span class="text-gray-600 dark:text-gray-400">User</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 sm:pr-6">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <form action="{{ route('admin.users.toggle-override', $user) }}" method="POST">
                                                @csrf
                                                <x-ui.button type="submit" size="sm" variant="secondary">{{ $user->feature_override ? 'Turn off unlimited' : 'Give unlimited' }}</x-ui.button>
                                            </form>

                                            @if(! $user->is_admin && $user->id !== auth()->id())
                                                <form action="{{ route('admin.users.impersonate', $user) }}" method="POST"
                                                      data-confirm="You'll be signed in as {{ $user->email }} and see the app as they do. Anything you do will happen on their account. Use Stop impersonating in the banner to return."
                                                      data-confirm-title="Sign in as {{ $user->name }}?"
                                                      data-confirm-button="Impersonate">
                                                    @csrf
                                                    <x-ui.button type="submit" size="sm" variant="secondary" icon="arrow-right-end-on-rectangle">Impersonate</x-ui.button>
                                                </form>
                                            @endif

                                            <form action="{{ route('admin.users.toggle-admin', $user) }}" method="POST"
                                                  @if($user->is_admin)
                                                      data-confirm="{{ $user->email }} will lose access to the admin area{{ $user->id === auth()->id() ? ', including you — you will be locked out of these pages' : '' }}."
                                                      data-confirm-title="Revoke admin access?"
                                                      data-confirm-button="Revoke admin"
                                                  @else
                                                      data-confirm="{{ $user->email }} will get full admin access: managing users, plans and settings, and impersonating other users."
                                                      data-confirm-title="Make {{ $user->name }} an admin?"
                                                      data-confirm-button="Make admin"
                                                  @endif
                                                  data-confirm-danger>
                                                @csrf
                                                <x-ui.button type="submit" size="sm" :variant="$user->is_admin ? 'danger-outline' : 'secondary'">{{ $user->is_admin ? 'Revoke admin' : 'Make admin' }}</x-ui.button>
                                            </form>
                                        </div>

                                        <form action="{{ route('admin.users.override-expiry', $user) }}" method="POST" class="mt-2 flex items-center justify-end gap-2">
                                            @csrf
                                            <label for="expires_at_{{ $user->id }}" class="text-xs whitespace-nowrap text-gray-600 dark:text-gray-400">Unlimited until</label>
                                            <input type="date" id="expires_at_{{ $user->id }}" name="expires_at"
                                                   class="rounded-control border-gray-300 py-1 text-sm shadow-xs focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                                            <x-ui.button type="submit" size="sm" variant="ghost">Set expiry</x-ui.button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        @if($users->hasPages())
            <div>{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
