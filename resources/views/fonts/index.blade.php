<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Font Library
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Feedback --}}
            @if($errors->any())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{-- Upload --}}
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">Upload a Font</h3>
                <form method="POST" action="{{ route('fonts.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex gap-4 items-end flex-wrap">
                        <div class="flex-1 min-w-48">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Display name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   placeholder="e.g. Roboto Bold"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div class="flex-1 min-w-48">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Font file <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="font" accept=".ttf,.otf" required
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0
                                          file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700
                                          hover:file:bg-indigo-100 dark:file:bg-indigo-900/50 dark:file:text-indigo-300">
                            <p class="mt-1 text-xs text-gray-400">TTF or OTF &nbsp;·&nbsp; Max 5 MB</p>
                        </div>
                        <div>
                            <button type="submit"
                                    class="px-5 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Upload
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- System fonts --}}
            @if($systemFonts->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">
                        System Fonts
                        <span class="text-xs font-normal text-gray-400 ml-1">(available to all users)</span>
                    </h3>
                    <div class="space-y-2">
                        @foreach($systemFonts as $font)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $font->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $font->filename }}</p>
                                </div>
                                <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">System</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- User fonts --}}
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-4">Your Fonts</h3>

                @if($userFonts->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No custom fonts uploaded yet. Use the form above to add your own TTF or OTF fonts.
                    </p>
                @else
                    <div class="space-y-2">
                        @foreach($userFonts as $font)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $font->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $font->filename }} &nbsp;·&nbsp; uploaded {{ $font->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('fonts.destroy', $font) }}"
                                      onsubmit="return confirm('Delete this font? Templates using it will fall back to the default font.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="text-right">
                <a href="{{ route('templates.index') }}" class="text-sm text-gray-500 hover:underline">
                    ← Back to Templates
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
