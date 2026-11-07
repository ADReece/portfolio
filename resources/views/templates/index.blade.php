<x-app-layout title="Print templates">
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Print templates"
                          description="Design an overlay — names, dates, a border — that's added to photos when you create prints.">
            @if(auth()->user()->hasFeature('custom_templates'))
                <x-slot:actions>
                    <x-ui.button variant="secondary" icon="language" :href="route('fonts.index')">Fonts</x-ui.button>
                    <x-ui.button icon="plus" :href="route('templates.create')">New template</x-ui.button>
                </x-slot:actions>
            @endif
        </x-ui.page-header>

        @if(!auth()->user()->hasFeature('custom_templates'))
            <x-ui.alert tone="amber" title="Print templates aren't included in your plan">
                Upgrade to create templates and add them to photos across a whole collection.
                <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
            </x-ui.alert>
        @elseif($templates->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="swatch" title="No templates yet"
                                  description="Upload a PNG overlay and add text fields such as a name or date. Then apply the template to a whole collection in one go.">
                    <x-ui.button icon="plus" :href="route('templates.create')">Create your first template</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($templates as $template)
                    <li class="flex flex-col overflow-hidden rounded-card bg-white shadow-xs ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        @if($template->overlay_path)
                            <div class="flex h-36 items-center justify-center overflow-hidden bg-gray-900">
                                <img src="{{ $template->overlayUrl() }}" alt="" loading="lazy" class="h-full w-full object-contain">
                            </div>
                        @else
                            <div class="flex h-36 flex-col items-center justify-center gap-1 bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                                <x-ui.icon name="document-text" class="size-8" />
                                <span class="text-sm font-medium">Text only</span>
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $template->name }}</h2>
                            @if($template->description)
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $template->description }}</p>
                            @endif

                            @if(!empty($template->fields))
                                <div class="mt-3 flex flex-wrap gap-1">
                                    <span class="sr-only">Text fields:</span>
                                    @foreach($template->fields as $field)
                                        <x-ui.badge tone="brand">{{ $field['label'] }}</x-ui.badge>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-auto flex gap-2 pt-4">
                                <x-ui.button variant="secondary" size="sm" icon="pencil-square" class="flex-1" :href="route('templates.edit', $template)">
                                    Edit<span class="sr-only"> {{ $template->name }}</span>
                                </x-ui.button>
                                <form method="POST" action="{{ route('templates.destroy', $template) }}"
                                      data-confirm="Prints you've already generated won't be affected." data-confirm-title="Delete this template?" data-confirm-button="Delete template" data-confirm-danger>
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger-outline" size="sm" icon="trash">
                                        Delete<span class="sr-only"> {{ $template->name }}</span>
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
