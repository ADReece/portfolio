<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Test Template: {{ $template->name }}
            </h2>
            <a href="{{ route('templates.edit', $template) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                ← Back to Editor
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Pick one of your photos, fill in the text field values, and see exactly how the finished image will look.
                    The image is generated instantly — no queue needed.
                </p>

                @if($errors->any())
                    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-sm text-sm">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                @if($photos->isEmpty())
                    <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                        <p class="mb-3">You don't have any photos uploaded yet.</p>
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline text-sm">Go upload some photos first</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('templates.runTest', $template) }}">
                        @csrf

                        {{-- Photo picker --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Choose a photo <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 max-h-64 overflow-y-auto p-1 border border-gray-200 dark:border-gray-700 rounded-lg">
                                @foreach($photos as $photo)
                                    <label class="relative cursor-pointer group">
                                        <input type="radio" name="photo_id" value="{{ $photo->id }}"
                                               class="sr-only peer" {{ old('photo_id') === $photo->id ? 'checked' : '' }}>
                                        <img src="{{ $photo->getAwsThumbnail() }}" alt="photo"
                                             class="w-full aspect-square object-cover rounded border-2 border-transparent
                                                    peer-checked:border-indigo-500 group-hover:border-indigo-300 transition-all">
                                        <div class="absolute inset-0 bg-indigo-600/20 opacity-0 peer-checked:opacity-100 rounded-sm transition-opacity"></div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Text field values --}}
                        @if(count($template->fields) > 0)
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Fill in text values
                                </label>
                                <div class="space-y-3">
                                    @foreach($template->fields as $field)
                                        <div>
                                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">
                                                {{ $field['label'] ?? $field['key'] }}
                                                <span class="text-xs text-gray-400">({{ $field['key'] }})</span>
                                            </label>
                                            <input type="text"
                                                   name="field_values[{{ $field['key'] }}]"
                                                   value="{{ old('field_values.' . $field['key'], '') }}"
                                                   placeholder="Enter value for {{ $field['label'] ?? $field['key'] }}"
                                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mb-6 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-sm text-sm text-amber-700 dark:text-amber-300">
                                This template has no text fields — only the overlay will be applied.
                            </div>
                        @endif

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="px-6 py-2.5 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                Generate Preview
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
