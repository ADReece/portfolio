{{-- <x-ui.badge tone="green" icon="check">Published</x-ui.badge>  Tones: gray, brand, green, amber, red, blue. --}}
@props(['tone' => 'gray', 'icon' => null])

@php
    $tones = [
        'gray' => 'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-400/20',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20 dark:bg-brand-950 dark:text-brand-300 dark:ring-brand-400/30',
        'green' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-950 dark:text-green-300 dark:ring-green-400/30',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-400/30',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950 dark:text-red-300 dark:ring-red-400/30',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950 dark:text-blue-300 dark:ring-blue-400/30',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.($tones[$tone] ?? $tones['gray'])]) }}>
    @if($icon)<x-ui.icon :name="$icon" variant="m" class="size-3.5" />@endif
    {{ $slot }}
</span>
