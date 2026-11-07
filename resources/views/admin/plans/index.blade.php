<x-app-layout title="Subscription plans">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Subscription plans" description="Pricing tiers shown on the pricing page, in sort order.">
            <x-slot:actions>
                <x-ui.button icon="plus" :href="route('admin.plans.create')">New plan</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card :padding="false">
            @if($plans->isEmpty())
                <x-ui.empty-state icon="rectangle-stack" title="No plans yet" description="Create a plan so photographers can subscribe.">
                    <x-ui.button icon="plus" :href="route('admin.plans.create')">New plan</x-ui.button>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 sm:pl-6 dark:text-gray-100">Order</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Plan</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Monthly</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Annual</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">Status</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Subscribers</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-900 sm:pr-6 dark:text-gray-100"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($plans as $plan)
                                @php($subscriberCount = $plan->users()->count())
                                <tr>
                                    <td class="px-4 py-3 text-right text-gray-500 tabular-nums sm:pl-6 dark:text-gray-400">{{ $plan->sort_order }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->name }}</span>
                                            @if($plan->recommended)<x-ui.badge tone="brand" icon="star">Recommended</x-ui.badge>@endif
                                        </div>
                                        <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $plan->slug }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-gray-900 tabular-nums dark:text-gray-100">
                                        {{ $currencySymbol }}{{ number_format($plan->price, 2) }}<span class="text-gray-500 dark:text-gray-400">/mo</span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-gray-900 tabular-nums dark:text-gray-100">
                                        @if($plan->annual_price)
                                            {{ $currencySymbol }}{{ number_format($plan->annual_price, 2) }}<span class="text-gray-500 dark:text-gray-400">/yr</span>
                                            @if($plan->annual_discount_percent)
                                                <div class="text-xs text-green-700 dark:text-green-400">{{ $plan->annual_discount_percent }}% off</div>
                                            @endif
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($plan->active)
                                            <x-ui.badge tone="green">Active</x-ui.badge>
                                        @else
                                            <x-ui.badge>Inactive</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900 tabular-nums dark:text-gray-100">{{ number_format($subscriberCount) }}</td>
                                    <td class="px-4 py-3 sm:pr-6">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-ui.button size="sm" variant="secondary" icon="pencil-square" :href="route('admin.plans.edit', $plan)">Edit</x-ui.button>
                                            @if($subscriberCount === 0)
                                                <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST"
                                                      data-confirm="The {{ $plan->name }} plan will be removed from the pricing page. This can't be undone."
                                                      data-confirm-title="Delete this plan?"
                                                      data-confirm-button="Delete plan"
                                                      data-confirm-danger>
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.button type="submit" size="sm" variant="danger-outline" icon="trash">Delete</x-ui.button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
