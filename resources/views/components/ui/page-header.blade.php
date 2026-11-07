{{--
    <x-ui.page-header title="Collections" description="…" back="/x" back-label="All collections">
        <x-slot:actions><x-ui.button …>New collection</x-ui.button></x-slot:actions>
    </x-ui.page-header>
--}}
@props(['title', 'description' => null, 'back' => null, 'backLabel' => 'Back'])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @if($back)
        <a href="{{ $back }}" class="inline-flex items-center gap-1 rounded-sm text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:text-gray-100">
            <x-ui.icon name="arrow-left" class="size-4" />
            {{ $backLabel }}
        </a>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 text-balance dark:text-gray-100">{{ $title }}</h1>
            @if($description)<p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>@endif
        </div>
        @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
    </div>
</div>
