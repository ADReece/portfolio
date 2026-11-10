{{--
    Icon from the Heroicons set (https://heroicons.com).
    <x-ui.icon name="photo" />              outline, 20px
    <x-ui.icon name="check" variant="m" />  mini (solid, 20px grid)
    The SVG is always decorative; pass :label to add screen-reader text,
    e.g. for an icon-only button.
--}}
@props(['name', 'variant' => 'o', 'label' => null])

{{ svg('heroicon-'.$variant.'-'.$name, '', ['class' => $attributes->get('class', 'size-5 shrink-0'), 'aria-hidden' => 'true', 'focusable' => 'false'] + $attributes->except('class')->getAttributes()) }}@if($label)<span class="sr-only">{{ $label }}</span>@endif
