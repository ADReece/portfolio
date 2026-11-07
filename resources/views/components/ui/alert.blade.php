{{-- Inline message within a page. <x-ui.alert tone="amber" title="…">Body</x-ui.alert> --}}
@props(['tone' => 'brand', 'title' => null, 'icon' => null])

@php
    $tones = [
        'brand' => ['bg-brand-50 text-brand-900 ring-brand-200 dark:bg-brand-950/60 dark:text-brand-100 dark:ring-brand-800', 'information-circle'],
        'green' => ['bg-green-50 text-green-900 ring-green-200 dark:bg-green-950/60 dark:text-green-100 dark:ring-green-800', 'check-circle'],
        'amber' => ['bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950/60 dark:text-amber-100 dark:ring-amber-800', 'exclamation-triangle'],
        'red' => ['bg-red-50 text-red-900 ring-red-200 dark:bg-red-950/60 dark:text-red-100 dark:ring-red-800', 'exclamation-circle'],
    ];
    [$classes, $defaultIcon] = $tones[$tone] ?? $tones['brand'];
@endphp

<div {{ $attributes->merge(['class' => 'flex gap-3 rounded-card p-4 text-sm ring-1 ring-inset '.$classes]) }}>
    <x-ui.icon :name="$icon ?? $defaultIcon" class="mt-px size-5 shrink-0" />
    <div class="min-w-0 flex-1 space-y-1">
        @if($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="[&_a]:font-semibold [&_a]:underline">{{ $slot }}</div>
        @isset($actions)<div class="pt-2 flex flex-wrap gap-2">{{ $actions }}</div>@endisset
    </div>
</div>
