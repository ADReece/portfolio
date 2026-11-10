<x-app-layout :title="'Preview of ' . $template->name">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="'Preview of “' . $template->name . '”'"
                          :back="route('templates.test', $template)" back-label="Try another photo">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="pencil-square" :href="route('templates.edit', $template)">Edit template</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if($generatedPrint->status === 'failed')
            <x-ui.alert tone="red" title="We couldn't create this preview">
                {{ $generatedPrint->error_message ?? 'Something went wrong while adding the template to your photo.' }}
                <x-slot:actions>
                    <x-ui.button size="sm" variant="secondary" icon="arrow-path" :href="route('templates.test', $template)">Try again</x-ui.button>
                </x-slot:actions>
            </x-ui.alert>
        @endif

        @if($outputUrl)
            <x-ui.card title="Finished print" :padding="false">
                <x-slot:actions>
                    <x-ui.button size="sm" icon="arrow-down-tray" :href="$outputUrl" download target="_blank">Download</x-ui.button>
                </x-slot:actions>
                <div class="flex justify-center rounded-b-card bg-gray-900 p-4">
                    <img src="{{ $outputUrl }}" alt="Your photo with the {{ $template->name }} template applied"
                         class="max-h-[70vh] max-w-full rounded-sm object-contain shadow-lg">
                </div>
            </x-ui.card>

            @if(!empty($generatedPrint->print_data))
                <x-ui.card title="Text used">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3">
                        @foreach($generatedPrint->print_data as $key => $value)
                            <div>
                                <dt class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ $key }}</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    @if($value) {{ $value }} @else <span class="font-normal italic text-gray-500 dark:text-gray-400">(left blank)</span> @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>
            @endif

        @elseif($generatedPrint->status === 'pending' || $generatedPrint->status === 'processing')
            <x-ui.card>
                <div class="flex flex-col items-center gap-4 py-8 text-center" role="status" aria-live="polite">
                    <svg class="size-8 animate-spin text-brand-600 motion-reduce:animate-none dark:text-brand-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">Generating your preview…</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">This usually takes a few seconds. The page will update by itself — no need to refresh.</p>
                    </div>
                </div>
                <meta http-equiv="refresh" content="3">
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
