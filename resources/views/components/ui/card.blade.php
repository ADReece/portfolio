{{--
    Panel for grouping related content.
    <x-ui.card title="Sharing" description="…"><x-slot:actions>…</x-slot:actions> … </x-ui.card>
--}}
@props(['title' => null, 'description' => null, 'padding' => true])

<section {{ $attributes->merge(['class' => 'rounded-card bg-white shadow-xs ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700']) }}>
    @if($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:px-6 dark:border-gray-700">
            <div class="min-w-0">
                @if($title)<h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>@endif
                @if($description)<p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['px-5 py-5 sm:px-6' => $padding])>
        {{ $slot }}
    </div>
</section>
