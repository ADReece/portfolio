{{--
    <x-ui.checkbox name="private" label="Private gallery" description="Clients need a password or link." :checked="$x" />
    Sends 0 when unticked (hidden input) unless :unchecked-value="null".
--}}
@props(['name' => null, 'id' => null, 'label', 'description' => null, 'checked' => false, 'value' => '1', 'uncheckedValue' => '0'])

@php($id ??= $name ?? 'checkbox-'.\Illuminate\Support\Str::random(6))

<div class="flex items-start gap-3">
    @if($name && ! is_null($uncheckedValue))
        <input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}">
    @endif
    <input id="{{ $id }}" type="checkbox" value="{{ $value }}" @if($name) name="{{ $name }}" @endif @checked($checked)
           @if($description) aria-describedby="{{ $id }}-description" @endif
           {{ $attributes->class('mt-0.5 size-4 rounded-sm border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900') }}>
    <div class="text-sm">
        <label for="{{ $id }}" class="font-medium text-gray-800 dark:text-gray-200">{{ $label }}</label>
        @if($description)
            <p id="{{ $id }}-description" class="text-gray-600 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>
</div>
