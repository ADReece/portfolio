{{--
    Shows one-off session messages (success, error, warning, info) in a
    consistent, accessible banner. Included once per layout, so pages don't
    need their own flash blocks.
--}}
@php
    $styles = [
        'success' => ['bg-green-50 dark:bg-green-950/60 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200', 'status', 'M5 13l4 4L19 7'],
        'error' => ['bg-red-50 dark:bg-red-950/60 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200', 'alert', 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
        'warning' => ['bg-amber-50 dark:bg-amber-950/60 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200', 'alert', 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
        'info' => ['bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200 dark:border-indigo-800 text-indigo-900 dark:text-indigo-200', 'status', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    $messages = collect($styles)->keys()->mapWithKeys(fn ($type) => [$type => session($type)])->filter();
@endphp

@if($messages->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-3']) }}>
        @foreach($messages as $type => $message)
            <div x-data="{ open: true }" x-show="open" role="{{ $styles[$type][1] }}"
                 class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $styles[$type][0] }}">
                <svg class="h-5 w-5 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $styles[$type][2] }}"/>
                </svg>
                <p class="flex-1">{{ $message }}</p>
                <button type="button" x-on:click="open = false" class="shrink-0 rounded-sm opacity-70 hover:opacity-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-current" aria-label="Dismiss message">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
