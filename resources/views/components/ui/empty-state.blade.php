{{--
    <x-ui.empty-state icon="photo" title="No photos yet" description="Upload photos to start this set.">
        <x-ui.button …>Upload photos</x-ui.button>
    </x-ui.empty-state>
--}}
@props(['icon' => 'sparkles', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="mb-4 inline-flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
        <x-ui.icon :name="$icon" class="size-6" />
    </span>
    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
    @if($description)<p class="mt-1 max-w-sm text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>@endif
    @if(trim($slot))<div class="mt-5 flex flex-wrap justify-center gap-3">{{ $slot }}</div>@endif
</div>
