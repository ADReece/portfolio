{{--
    Icon from the Heroicons set (https://heroicons.com).
    <x-ui.icon name="photo" />            outline, 20px
    <x-ui.icon name="check" variant="m" />  mini (solid, 20px grid)
    Decorative by default; pass :label to make it meaningful to screen readers.
--}}
@props(['name', 'variant' => 'o', 'label' => null])

@php
    $attrs = ['class' => $attributes->get('class', 'size-5 shrink-0')];
    $attrs += $label ? ['role' => 'img', 'aria-label' => $label] : ['aria-hidden' => 'true', 'focusable' => 'false'];
@endphp

{{ svg('heroicon-'.$variant.'-'.$name, '', $attrs + $attributes->except('class')->getAttributes()) }}
