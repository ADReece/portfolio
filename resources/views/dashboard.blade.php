@php
    $firstName = \Illuminate\Support\Str::of($user->name ?? '')->before(' ')->value() ?: $user->username;
    $doneSteps = $steps->where('done', true)->count();
    $allDone = $doneSteps === $steps->count();
    $nextStep = $steps->firstWhere('done', false);
@endphp

<x-app-layout title="Dashboard">
    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="($isNewUser ? 'Welcome, ' : 'Welcome back, ').$firstName"
                          description="Here's what's happening with your galleries.">
            <x-slot:actions>
                @if($user->username)
                    <x-ui.button variant="secondary" icon="globe-alt" :href="route('profile.view', $user->username)" target="_blank" rel="noopener">View portfolio</x-ui.button>
                @endif
                <x-ui.button icon="plus" :href="route('collections.create')">New collection</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @unless($allDone)
            <x-ui.card :padding="false">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:px-6 dark:border-gray-700">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Get set up</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $doneSteps }} of {{ $steps->count() }} done</p>
                    </div>
                    <div class="h-2 w-40 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-label="Setup progress" aria-valuemin="0" aria-valuemax="{{ $steps->count() }}" aria-valuenow="{{ $doneSteps }}">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ round($doneSteps / max(1, $steps->count()) * 100) }}%"></div>
                    </div>
                </div>
                <ol class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($steps as $step)
                        <li class="flex flex-wrap items-center gap-4 px-5 py-4 sm:px-6">
                            @if($step['done'])
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300">
                                    <x-ui.icon name="check" variant="m" class="size-5" label="Done" />
                                </span>
                            @else
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-gray-300 text-sm font-semibold text-gray-500 dark:border-gray-600 dark:text-gray-400">{{ $loop->iteration }}</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p @class(['text-sm font-medium', 'text-gray-500 line-through decoration-gray-300 dark:text-gray-400 dark:decoration-gray-600' => $step['done'], 'text-gray-900 dark:text-gray-100' => ! $step['done']])>{{ $step['title'] }}</p>
                                @unless($step['done'])<p class="text-sm text-gray-600 dark:text-gray-400">{{ $step['description'] }}</p>@endunless
                            </div>
                            @unless($step['done'])
                                <div class="w-full pl-12 sm:w-auto sm:pl-0">
                                    <x-ui.button size="sm" :variant="$step === $nextStep ? 'primary' : 'secondary'" :href="$step['href']">{{ $step['action'] }}</x-ui.button>
                                </div>
                            @endunless
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        @endunless

        <section aria-labelledby="stats-heading">
            <h2 id="stats-heading" class="sr-only">Overview</h2>
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @php
                    $tiles = [
                        ['Collections', number_format($stats['collections']), $stats['published'].' published', route('collections.index')],
                        ['Photos', number_format($stats['photos']), $stats['photoLimit'] ? 'of '.number_format($stats['photoLimit']).' on your plan' : 'No limit on your plan', null],
                    ];
                    if (! is_null($stats['orders30'])) {
                        $tiles[] = ['Print orders', number_format($stats['orders30']), 'Last 30 days', null];
                        $tiles[] = ['Print earnings', $currencySymbol.number_format($stats['earnings30'], 2), 'Last 30 days, after fees', route('connect.stripe')];
                    }
                @endphp
                @foreach($tiles as [$label, $value, $hint, $href])
                    <div class="rounded-card bg-white p-5 shadow-xs ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-bold tracking-tight text-gray-900 tabular-nums dark:text-gray-100">{{ $value }}</dd>
                        <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</dd>
                        @if($stats['photoLimit'] && $label === 'Photos')
                            <dd class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700" role="progressbar" aria-label="Photo storage used" aria-valuemin="0" aria-valuemax="{{ $stats['photoLimit'] }}" aria-valuenow="{{ $stats['photos'] }}">
                                <span class="block h-full {{ $stats['photos'] >= $stats['photoLimit'] ? 'bg-red-500' : 'bg-brand-500' }}" style="width: {{ min(100, round($stats['photos'] / $stats['photoLimit'] * 100)) }}%"></span>
                            </dd>
                        @endif
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <x-ui.card title="Recent collections" class="lg:col-span-2" :padding="false">
                <x-slot:actions>
                    <x-ui.button variant="ghost" size="sm" :href="route('collections.index')" icon-trailing="arrow-right">All collections</x-ui.button>
                </x-slot:actions>
                @if($recentCollections->isEmpty())
                    <x-ui.empty-state icon="rectangle-stack" title="No collections yet" description="Create a collection for each shoot or event, then upload photos and share it with your client.">
                        <x-ui.button icon="plus" :href="route('collections.create')">New collection</x-ui.button>
                    </x-ui.empty-state>
                @else
                    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentCollections as $collection)
                            <li>
                                <a href="{{ route('collections.edit', $collection) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 focus:outline-hidden focus-visible:bg-gray-50 sm:px-6 dark:hover:bg-gray-700/40 dark:focus-visible:bg-gray-700/40">
                                    @if($collection->coverPhoto)
                                        <img src="{{ $collection->coverPhoto->getAwsThumbnail() }}" alt="" class="size-12 shrink-0 rounded-md object-cover" loading="lazy">
                                    @else
                                        <span class="flex size-12 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-400 dark:bg-gray-700"><x-ui.icon name="photo" class="size-6" /></span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $collection->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $collection->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $collection->photos_count) }} · {{ $collection->sets_count }} {{ \Illuminate\Support\Str::plural('set', $collection->sets_count) }} · updated {{ $collection->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="hidden shrink-0 gap-1.5 sm:flex">
                                        @if($collection->private)<x-ui.badge tone="brand" icon="lock-closed">Private</x-ui.badge>@endif
                                        @include('collections.partials.status-badge', ['status' => $collection->status])
                                    </div>
                                    <x-ui.icon name="chevron-right" class="size-5 text-gray-400" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Your plan" class="self-start">
                <div class="space-y-4">
                    <div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $plan->name ?? 'No plan yet' }}</p>
                        @if($user->isFeatureOverrideActive())
                            <x-ui.badge tone="green" class="mt-1">Unlimited access</x-ui.badge>
                        @elseif($subscription?->onGracePeriod())
                            <p class="text-sm text-amber-700 dark:text-amber-400">Ends on {{ $subscription->ends_at->toFormattedDateString() }}. You can resume before then.</p>
                        @elseif($subscription?->valid())
                            <p class="text-sm text-gray-600 dark:text-gray-400">Active subscription</p>
                        @elseif(! $plan)
                            <p class="text-sm text-gray-600 dark:text-gray-400">Choose a plan to unlock private galleries, print sales and branding.</p>
                        @endif
                    </div>
                    <x-ui.button variant="secondary" :href="route('pricing')" class="w-full">{{ $plan ? 'Manage plan & billing' : 'Compare plans' }}</x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
