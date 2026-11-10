{{--
    <x-ui.input name="email" type="email" label="Email" :value="old('email')" help="…" required />
    Validation errors for `name` show automatically and mark the input invalid.
    Pass any other attribute (wire:model, x-model, autocomplete…) straight through.
--}}
@props(['name' => null, 'id' => null, 'label' => null, 'help' => null, 'optional' => false, 'type' => 'text', 'errorKey' => null, 'bag' => null])

@php
    $id ??= $name ?? 'input-'.\Illuminate\Support\Str::random(6);
    $errorKey ??= $name ? str_replace(['[', ']'], ['.', ''], $name) : null;
    $error = $errorKey ? ($bag ? $errors->getBag($bag) : $errors)->first($errorKey) : null;
    $describedBy = trim(($help ? "$id-help " : '').($error ? "$id-error" : ''));
@endphp

<x-ui.field :id="$id" :label="$label" :help="$help" :error="$error" :optional="$optional" class="{{ $attributes->get('wrapper-class') }}">
    <input id="{{ $id }}" type="{{ $type }}" @if($name) name="{{ $name }}" @endif
           @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
           @if($error) aria-invalid="true" @endif
           {{ $attributes->except('wrapper-class')->class([
               'block w-full rounded-control shadow-xs sm:text-sm dark:bg-gray-900 dark:text-gray-100',
               'border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600' => ! $error,
               'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-700' => $error,
           ]) }} />
</x-ui.field>
