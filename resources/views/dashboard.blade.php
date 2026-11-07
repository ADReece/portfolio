<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <a href="{{ route('profile.view', ['username' => auth()->user()->username]) }}"
               target="_blank"
               class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                View Your Portfolio
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Welcome Section -->
            <div class="bg-linear-to-r from-indigo-500 to-purple-600 dark:from-indigo-700 dark:to-purple-800 overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-8 text-white">
                    <h3 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->name ?? auth()->user()->username }}! 👋</h3>
                    <p class="text-indigo-100">Manage your photography portfolio and collections from here.</p>
                </div>
            </div>

            <!-- Subscription Summary -->
            @php
                $user = auth()->user();
                $plan = $user->subscriptionPlan;
                $subscription = $user->subscription('default');
                $isSubscribed = $subscription && $subscription->valid();
                $onGrace = $subscription && $subscription->onGracePeriod();
                $nextBilling = null;
                $lastInvoice = null;
                if ($isSubscribed) {
                    try {
                        $stripeSub = $subscription->asStripeSubscription();
                        if (isset($stripeSub->current_period_end)) {
                            $nextBilling = \Carbon\Carbon::createFromTimestamp($stripeSub->current_period_end);
                        }
                    } catch (\Exception $e) {
                        $nextBilling = null;
                    }
                    // Get last invoice
                    $invoices = $user->invoices();
                    if (count($invoices) > 0) {
                        $lastInvoice = $invoices[0];
                    }
                }
            @endphp

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Subscription</h3>
                            @if($isSubscribed)
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Current Plan: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->name ?? '—' }}</span>
                                    @if($user->isFeatureOverrideActive())
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">Unlimited Access</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Status:
                                    @if($onGrace)
                                        <span class="text-yellow-700 dark:text-yellow-400 font-medium">Cancels on {{ optional($subscription->ends_at)->toDayDateTimeString() }}</span>
                                    @else
                                        <span class="text-green-700 dark:text-green-400 font-medium">Active</span>
                                    @endif
                                </p>
                                @if(!$user->isFeatureOverrideActive())
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Next Billing: <span class="font-medium">{{ $nextBilling ? $nextBilling->toDayDateTimeString() : '—' }}</span></p>
                                @endif
                                @if($lastInvoice)
                                    @php
                                        $invoiceAmount = (float)$lastInvoice->total() / 100;
                                    @endphp
                                    @if($invoiceAmount > 0)
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Last Invoice: <span class="font-medium">${{ number_format($invoiceAmount, 2) }}</span> <span class="text-xs">({{ $lastInvoice->date()->format('M d, Y') }})</span></p>
                                    @endif
                                @endif
                            @else
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Current Plan: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->name ?? 'Free' }}</span>
                                    @if($user->isFeatureOverrideActive())
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">Unlimited Access</span>
                                    @endif
                                </p>
                                @if(!$user->isFeatureOverrideActive())
                                    <p class="text-sm text-gray-600 dark:text-gray-400">You're not currently subscribed. Explore paid plans to unlock premium features.</p>
                                @else
                                    <p class="text-sm text-gray-600 dark:text-gray-400">You have unlimited access to all features.</p>
                                @endif
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            @if($isSubscribed)
                                <a href="{{ route('billing.portal') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Manage Billing</a>
                                @if($lastInvoice)
                                    <a href="{{ route('billing.invoice.download', $lastInvoice->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                        </svg>
                                        View Invoice
                                    </a>
                                @endif
                                @if($onGrace)
                                    <form action="{{ route('subscription.resume') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Resume</button>
                                    </form>
                                @else
                                    <form action="{{ route('subscription.cancel') }}" method="POST" data-confirm="You'll keep your current plan until the end of this billing period. You can resume any time before then." data-confirm-title="Cancel your subscription?" data-confirm-button="Cancel subscription" data-confirm-danger>
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Cancel</button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('pricing') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">View Plans</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            @php
                $collections = auth()->user()->collections()->count();
                $totalPhotos = \App\Models\Photo::where('user_id', auth()->id())->count();
                $publishedCollections = auth()->user()->collections()->where('status', 'Published')->count();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Collections Count -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0 bg-indigo-100 dark:bg-indigo-900 rounded-lg p-3">
                                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Collections</dt>
                                    <dd class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $collections }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photos Count -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0 bg-green-100 dark:bg-green-900 rounded-lg p-3">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Photos</dt>
                                    <dd class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $totalPhotos }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Published Count -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0 bg-purple-100 dark:bg-purple-900 rounded-lg p-3">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Published</dt>
                                    <dd class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $publishedCollections }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                    <!-- Manage Collections -->
                    <a href="{{ route('collections.index') }}"
                       class="block p-6 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-500 dark:hover:border-indigo-400 hover:shadow-lg transition-all group">
                        <div class="flex items-center mb-3">
                            <div class="shrink-0 bg-indigo-100 dark:bg-indigo-900 rounded-lg p-2 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800 transition-colors">
                                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h4 class="ml-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Manage Collections</h4>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">View, edit, and organize your photo collections</p>
                    </a>

                    <!-- Create Collection -->
                    <a href="{{ route('collections.create') }}"
                       class="block p-6 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:shadow-lg transition-all group">
                        <div class="flex items-center mb-3">
                            <div class="shrink-0 bg-green-100 dark:bg-green-900 rounded-lg p-2 group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <h4 class="ml-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Create Collection</h4>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Start a new photo collection or project</p>
                    </a>

                    <!-- Settings -->
                    <a href="{{ route('profile.edit') }}"
                       class="block p-6 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-purple-500 dark:hover:border-purple-400 hover:shadow-lg transition-all group">
                        <div class="flex items-center mb-3">
                            <div class="shrink-0 bg-purple-100 dark:bg-purple-900 rounded-lg p-2 group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition-colors">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h4 class="ml-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Settings</h4>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Configure your profile and portfolio settings</p>
                    </a>

                </div>
            </div>

            <!-- Recent Invoices -->
            @if($isSubscribed && count($user->invoices()) > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Invoices</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($user->invoices() as $invoice)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $invoice->date()->toDayDateTimeString() }}</td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                            ${{ number_format(((float)$invoice->total()) / 100, 2) }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                            <span class="px-2 py-1 text-xs rounded-sm @if($invoice->status === 'paid') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 @else bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 @endif">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            @if($invoice->status === 'paid')
                                            <a href="{{ route('billing.invoice.download', $invoice->id) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">Download</a>
                                            @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
