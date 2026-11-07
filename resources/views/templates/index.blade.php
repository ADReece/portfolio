<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Print Templates
            </h2>
            @if(auth()->user()->hasFeature('custom_templates'))
                <a href="{{ route('templates.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Template
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if(!auth()->user()->hasFeature('custom_templates'))
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200 px-4 py-4 rounded-lg mb-6">
                    <p class="font-medium">Template batching is a premium feature.</p>
                    <p class="text-sm mt-1">Upgrade your plan to create and apply print templates to your photos.</p>
                    <a href="{{ route('pricing') }}" class="mt-3 inline-block px-4 py-2 bg-amber-600 text-white rounded-md text-sm hover:bg-amber-700">
                        View Plans
                    </a>
                </div>
            @else
                @if($templates->isEmpty())
                    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No templates yet</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                            Create a template by uploading a PNG overlay and defining text fields (e.g. player name, jersey number). Then apply it to any collection in one click.
                        </p>
                        <a href="{{ route('templates.create') }}"
                           class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Create your first template
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($templates as $template)
                            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                                {{-- Overlay preview --}}
                                @if($template->overlay_path)
                                    <div class="h-36 bg-gray-900 flex items-center justify-center overflow-hidden">
                                        <img src="{{ $template->overlayUrl() }}"
                                             alt="{{ $template->name }}"
                                             class="h-full w-full object-contain">
                                    </div>
                                @else
                                    <div class="h-36 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                        <span class="text-white text-2xl font-bold opacity-50">Text Only</span>
                                    </div>
                                @endif

                                <div class="p-5">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ $template->name }}</h3>
                                    @if($template->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $template->description }}</p>
                                    @endif

                                    @if(!empty($template->fields))
                                        <div class="flex flex-wrap gap-1 mb-4">
                                            @foreach($template->fields as $field)
                                                <span class="inline-block px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs rounded-full">
                                                    {{ $field['label'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="flex gap-2">
                                        <a href="{{ route('templates.edit', $template) }}"
                                           class="flex-1 text-center px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('templates.destroy', $template) }}"
                                              onsubmit="return confirm('Delete this template? Existing generated prints will not be affected.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-sm bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/70">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
