@props(['name' => null, 'id' => null, 'label' => null, 'help' => null, 'optional' => false, 'rows' => 3])

@php
    $id ??= $name ?? 'textarea-'.\Illuminate\Support\Str::random(6);
    $error = $name ? $errors->first(str_replace(['[', ']'], ['.', ''], $name)) : null;
    $describedBy = trim(($help ? "$id-help " : '').($error ? "$id-error" : ''));
@endphp

<x-ui.field :id="$id" :label="$label" :help="$help" :error="$error" :optional="$optional">
    <textarea id="{{ $id }}" @if($name) name="{{ $name }}" @endif rows="{{ $rows }}"
              @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
              @if($error) aria-invalid="true" @endif
              {{ $attributes->class([
                  'block w-full rounded-control shadow-xs sm:text-sm dark:bg-gray-900 dark:text-gray-100',
                  'border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600' => ! $error,
                  'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-700' => $error,
              ]) }}>{{ $slot }}</textarea>
</x-ui.field>
