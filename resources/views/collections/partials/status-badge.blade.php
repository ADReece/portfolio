@switch($status)
    @case('Published')<x-ui.badge tone="green" icon="check">Published</x-ui.badge>@break
    @case('Archived')<x-ui.badge tone="gray" icon="archive-box">Archived</x-ui.badge>@break
    @default<x-ui.badge tone="amber">Draft</x-ui.badge>
@endswitch
