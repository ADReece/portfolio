<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Download Photo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gray-50 dark:bg-gray-900 py-12 px-4">
    <div class="max-w-2xl mx-auto space-y-6">

        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Download Photo</h1>
            @if($photo->caption)
                <p class="mt-1 text-gray-500 dark:text-gray-400">{{ $photo->caption }}</p>
            @endif
        </div>

        <!-- Photo preview -->
        <div class="flex justify-center">
            <img src="{{ $photo->getAwsThumbnail() }}"
                 alt="{{ $photo->caption ?? 'Photo' }}"
                 class="max-h-64 rounded-lg shadow-md object-contain">
        </div>

        <!-- Plain download -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-3">Original photo</h2>
            <div x-data="{ email: '', sending: false, sent: false, err: '' }">
                <div class="flex gap-3">
                    <input type="email" x-model="email" placeholder="your@email.com"
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                    <button @click="
                        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { err = 'Enter a valid email.'; return; }
                        err = ''; sending = true;
                        fetch('/photos/{{ $photo->id }}/request-download', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ email }),
                        }).then(r => r.json()).then(d => { sent = d.success; if (!d.success) err = d.message; }).finally(() => sending = false);
                    " :disabled="sending || sent"
                            class="px-4 py-2 text-sm bg-gray-800 dark:bg-gray-200 dark:text-gray-800 text-white rounded-lg hover:opacity-90 disabled:opacity-50 whitespace-nowrap">
                        <span x-show="!sending && !sent">Send link</span>
                        <span x-show="sending">Sending…</span>
                        <span x-show="sent">✓ Link sent!</span>
                    </button>
                </div>
                <p x-show="err" x-text="err" class="mt-1.5 text-sm text-red-500"></p>
                <p class="mt-1.5 text-xs text-gray-400">We'll email you a temporary download link.</p>
            </div>
        </div>

        @if($templates->isNotEmpty())
            <div class="space-y-4">
                <h2 class="text-base font-semibold text-gray-700 dark:text-gray-300 px-1">
                    Or download with a template
                </h2>

                @foreach($templates as $template)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $template->name }}</h3>
                                @if($template->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $template->description }}</p>
                                @endif
                            </div>
                            @if($template->overlayUrl())
                                <img src="{{ $template->overlayUrl() }}" alt="Preview"
                                     class="w-16 h-16 object-cover rounded-sm border border-gray-200 dark:border-gray-700 ml-4 shrink-0">
                            @endif
                        </div>

                        <form method="POST"
                              action="{{ route('photos.download-with-template', [$photo, $template]) }}">
                            @csrf
                            @if($template->fields)
                                <div class="space-y-3 mb-4">
                                    @foreach($template->fields as $field)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {{ $field['label'] ?? $field['key'] }}
                                            </label>
                                            <input type="text"
                                                   name="fields[{{ $field['key'] }}]"
                                                   value="{{ old('fields.' . $field['key'], $photo->caption ?? '') }}"
                                                   placeholder="{{ $field['label'] ?? $field['key'] }}"
                                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <button type="submit"
                                    class="w-full py-2 px-4 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Generate &amp; Download with "{{ $template->name }}"
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
    @livewireScriptConfig
</body>
</html>
