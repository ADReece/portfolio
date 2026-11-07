<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Download is Ready</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if($generatedPrint->isPending())
        <meta http-equiv="refresh" content="3">
    @endif
</head>
<body class="min-h-full bg-gray-50 dark:bg-gray-900 py-12 px-4">
    <div class="max-w-2xl mx-auto space-y-6 text-center">

        @if($generatedPrint->isFailed())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6">
                <p class="text-red-700 dark:text-red-300 font-medium">Generation failed.</p>
                <p class="text-sm text-red-500 mt-1">{{ $generatedPrint->error_message }}</p>
                <a href="{{ route('photos.download-options', $photo) }}"
                   class="mt-4 inline-block px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Try again
                </a>
            </div>

        @elseif($generatedPrint->isPending())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8">
                <div class="animate-spin w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-700 dark:text-gray-300 font-medium">Generating your photo…</p>
                <p class="text-sm text-gray-400 mt-1">This page will refresh automatically.</p>
            </div>

        @elseif($outputUrl)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">Your photo is ready!</span>
                    <a href="{{ $outputUrl }}" download target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                </div>
                <div class="p-4 bg-gray-900 flex justify-center">
                    <img src="{{ $outputUrl }}" alt="Generated photo"
                         class="max-w-full max-h-[60vh] object-contain rounded-sm shadow-sm">
                </div>
            </div>

            <a href="{{ route('photos.download-options', $photo) }}"
               class="text-sm text-gray-500 hover:underline">
                ← Back to download options
            </a>
        @endif

    </div>
    @livewireScriptConfig
</body>
</html>
