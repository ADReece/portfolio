{{--
    Button or link styled as a button.
    <x-ui.button>Save</x-ui.button>
    <x-ui.button variant="secondary" href="/x" icon="arrow-left">Back</x-ui.button>
    <x-ui.button variant="danger" type="submit" size="sm">Delete</x-ui.button>
    Variants: primary, secondary, danger, danger-outline, ghost. Sizes: sm, md, lg.
    `wire:target` (optional) shows a spinner while that Livewire action runs.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconTrailing' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-control font-semibold whitespace-nowrap transition-colors '
        . 'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 '
        . 'disabled:opacity-50 disabled:cursor-not-allowed aria-disabled:opacity-50 aria-disabled:pointer-events-none';

    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-xs hover:bg-brand-700 focus-visible:ring-brand-500 dark:bg-brand-500 dark:hover:bg-brand-400',
        'secondary' => 'bg-white text-gray-800 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:ring-brand-500 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-600 dark:hover:bg-gray-700',
        'danger' => 'bg-red-600 text-white shadow-xs hover:bg-red-700 focus-visible:ring-red-500',
        'danger-outline' => 'text-red-700 ring-1 ring-inset ring-red-300 hover:bg-red-50 focus-visible:ring-red-500 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-950',
        'ghost' => 'text-gray-700 hover:bg-gray-100 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $iconSize = $size === 'lg' ? 'size-5' : 'size-4';
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
    $target = $attributes->get('wire:target');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-ui.icon :name="$icon" :class="$iconSize" />@endif
        {{ $slot }}
        @if($iconTrailing)<x-ui.icon :name="$iconTrailing" :class="$iconSize" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}
            @if($target) wire:loading.attr="disabled" @endif>
        @if($target)
            <svg wire:loading wire:target="{{ $target }}" class="{{ $iconSize }} animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        @endif
        @if($icon)<x-ui.icon :name="$icon" :class="$iconSize" />@endif
        {{ $slot }}
        @if($iconTrailing)<x-ui.icon :name="$iconTrailing" :class="$iconSize" />@endif
    </button>
@endif
