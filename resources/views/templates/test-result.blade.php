<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Template Test Result
            </h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('templates.test', $template) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    ← Test again
                </a>
                <a href="{{ route('templates.edit', $template) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    Edit template
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if($generatedPrint->status === 'failed')
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-red-700 dark:text-red-300 mb-2">Generation Failed</h3>
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $generatedPrint->error_message ?? 'An unknown error occurred.' }}</p>
                    <div class="mt-4">
                        <a href="{{ route('templates.test', $template) }}"
                           class="px-4 py-2 text-sm bg-red-600 text-white rounded-sm hover:bg-red-700">
                            Try again
                        </a>
                    </div>
                </div>
            @endif

            @if($outputUrl)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Generated output &nbsp;·&nbsp;
                            <span class="font-normal text-gray-500">{{ $template->name }}</span>
                        </span>
                        <a href="{{ $outputUrl }}" download target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-sm hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download
                        </a>
                    </div>
                    <div class="p-4 bg-gray-900 flex justify-center">
                        <img src="{{ $outputUrl }}" alt="Generated output"
                             class="max-w-full max-h-[70vh] object-contain rounded-sm shadow-lg">
                    </div>
                </div>

                {{-- Field values used --}}
                @if(!empty($generatedPrint->print_data))
                    <div class="mt-4 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Values used</h4>
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2">
                            @foreach($generatedPrint->print_data as $key => $value)
                                <div>
                                    <dt class="text-xs text-gray-400">{{ $key }}</dt>
                                    <dd class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $value ?: '(empty)' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

            @elseif($generatedPrint->status === 'pending' || $generatedPrint->status === 'processing')
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-8 text-center">
                    <div class="animate-spin w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                    <p class="text-gray-600 dark:text-gray-400">Still generating... refresh in a moment.</p>
                    <meta http-equiv="refresh" content="3">
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
