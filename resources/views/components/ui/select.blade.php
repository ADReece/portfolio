{{--
    <x-ui.select name="status" label="Status" :options="['Draft' => 'Draft', 'Published' => 'Published']" :selected="$value" />
    or pass <option> elements in the slot.
--}}
@props(['name' => null, 'id' => null, 'label' => null, 'help' => null, 'optional' => false, 'options' => null, 'selected' => null, 'placeholder' => null])

@php
    $id ??= $name ?? 'select-'.\Illuminate\Support\Str::random(6);
    $error = $name ? $errors->first($name) : null;
    $describedBy = trim(($help ? "$id-help " : '').($error ? "$id-error" : ''));
@endphp

<x-ui.field :id="$id" :label="$label" :help="$help" :error="$error" :optional="$optional">
    <select id="{{ $id }}" @if($name) name="{{ $name }}" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($error) aria-invalid="true" @endif
            {{ $attributes->class([
                'block w-full rounded-control shadow-xs sm:text-sm dark:bg-gray-900 dark:text-gray-100',
                'border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600' => ! $error,
                'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-700' => $error,
            ]) }}>
        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @if(is_iterable($options))
            @foreach($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
            @endforeach
        @endif
        {{ $slot }}
    </select>
</x-ui.field>
