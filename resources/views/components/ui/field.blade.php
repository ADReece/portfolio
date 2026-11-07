{{--
    Label, help text and error message around a form control. Used by
    x-ui.input / select / textarea; use directly for custom controls:
    <x-ui.field id="cover" label="Cover photo" help="…">…control…</x-ui.field>
--}}
@props(['id', 'label' => null, 'help' => null, 'error' => null, 'optional' => false])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
            {{ $label }}
            @if($optional)<span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($help)
        <p id="{{ $id }}-help" class="text-sm text-gray-600 dark:text-gray-400">{{ $help }}</p>
    @endif
    @if($error)
        <p id="{{ $id }}-error" class="text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
