<div>
    {{-- Feature gate --}}
    @unless(auth()->user()->hasFeature('custom_templates'))
        <div class="p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg text-center">
            <p class="font-medium text-amber-800 dark:text-amber-200 mb-2">Template batching is a premium feature.</p>
            <a href="{{ route('pricing') }}" class="px-4 py-2 bg-amber-600 text-white rounded-md text-sm hover:bg-amber-700">
                Upgrade your plan
            </a>
        </div>
    @else

    {{-- ══════════════════════════════════
         STEP 1 — SELECT TEMPLATE
    ══════════════════════════════════ --}}
    @if($step === 'select_template')
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                Step 1 — Choose a template
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Select the overlay and text field configuration you want to apply to this collection's photos.
            </p>

            @error('selectedTemplateId')
                <div class="mb-4 text-red-600 dark:text-red-400 text-sm">{{ $message }}</div>
            @enderror

            @if($templates->isEmpty())
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <p class="mb-4">You haven't created any templates yet.</p>
                    <a href="{{ route('templates.create') }}"
                       class="px-5 py-2.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">
                        Create a template
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @foreach($templates as $tmpl)
                        <div wire:click="selectTemplate('{{ $tmpl->id }}')"
                             class="cursor-pointer border-2 rounded-lg overflow-hidden transition-all
                                    {{ $selectedTemplateId === $tmpl->id
                                        ? 'border-indigo-500 ring-2 ring-indigo-300'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300' }}">

                            {{-- Overlay thumbnail --}}
                            @if($tmpl->overlay_path)
                                <div class="h-24 bg-gray-900 flex items-center justify-center overflow-hidden">
                                    <img src="{{ $tmpl->overlayUrl() }}" alt="{{ $tmpl->name }}"
                                         class="h-full w-full object-contain">
                                </div>
                            @else
                                <div class="h-24 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                    <span class="text-white text-sm font-medium opacity-60">Text Only</span>
                                </div>
                            @endif

                            <div class="p-3">
                                <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $tmpl->name }}</p>
                                @if(!empty($tmpl->fields))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ count($tmpl->fields) }} field{{ count($tmpl->fields) !== 1 ? 's' : '' }}:
                                        {{ implode(', ', array_column($tmpl->fields, 'label')) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button wire:click="proceed"
                            wire:loading.attr="disabled"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove>Continue →</span>
                        <span wire:loading>Loading...</span>
                    </button>
                </div>
            @endif
        </div>

    {{-- ══════════════════════════════════
         STEP 2 — ENTER PER-PHOTO DATA
    ══════════════════════════════════ --}}
    @elseif($step === 'enter_data')
        <div>
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Step 2 — Enter photo data
                </h3>
                <button wire:click="$set('step', 'select_template')"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:underline">
                    ← Change template
                </button>
            </div>

            @if($selectedTemplate)
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Template: <strong class="text-gray-700 dark:text-gray-300">{{ $selectedTemplate->name }}</strong>
                    — Fill in the fields for each photo below.
                </p>
            @endif

            @if($photos->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    This collection has no photos yet.
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">
                                    Photo
                                </th>
                                @if($selectedTemplate && !empty($selectedTemplate->fields))
                                    @foreach($selectedTemplate->fields as $field)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ $field['label'] }}
                                        </th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach($photos as $photo)
                                <tr>
                                    {{-- Thumbnail --}}
                                    <td class="px-4 py-3">
                                        @if($photo->thumbnail_url ?? null)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($photo->thumbnail_url, now()->addMinutes(30)) }}"
                                                 alt="Photo"
                                                 class="w-16 h-16 object-cover rounded">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center text-xs text-gray-400">
                                                No preview
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Field inputs --}}
                                    @if($selectedTemplate && !empty($selectedTemplate->fields))
                                        @foreach($selectedTemplate->fields as $field)
                                            <td class="px-4 py-3">
                                                <input type="text"
                                                       wire:model.defer="photoData.{{ $photo->id }}.{{ $field['key'] }}"
                                                       placeholder="{{ $field['label'] }}"
                                                       class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-4">
                    {{-- wire:confirm is Livewire 3 only; use the app's confirm dialog instead. --}}
                    <button type="button"
                            x-data
                            x-on:click="window.confirmAction({
                                title: @js('Generate '.$photos->count().' '.\Illuminate\Support\Str::plural('print', $photos->count()).'?'),
                                message: 'Prints are created in the background. You can leave this page and come back to download them.',
                                confirmText: 'Generate',
                                onConfirm: () => $wire.generate(),
                            })"
                            wire:loading.attr="disabled"
                            class="px-5 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                        <span wire:loading.remove>Generate {{ $photos->count() }} Print{{ $photos->count() !== 1 ? 's' : '' }}</span>
                        <span wire:loading>Queuing jobs...</span>
                    </button>
                </div>
            @endif
        </div>

    {{-- ══════════════════════════════════
         STEP 3 — PROCESSING / RESULTS
    ══════════════════════════════════ --}}
    @elseif($step === 'processing')
        <div wire:poll.2000ms>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                Step 3 — Generating prints
            </h3>

            @php
                $completed = $prints->where('status', 'completed')->count();
                $failed    = $prints->where('status', 'failed')->count();
                $pending   = $prints->whereIn('status', ['pending', 'processing'])->count();
                $total     = $prints->count();
            @endphp

            {{-- Progress bar --}}
            @if(!$allDone)
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>{{ $completed }} / {{ $total }} completed</span>
                        <span>{{ $pending }} remaining</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all"
                             style="width: {{ $total > 0 ? round(($completed / $total) * 100) : 0 }}%"></div>
                    </div>
                </div>
            @else
                <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded text-green-700 dark:text-green-300 text-sm">
                    ✓ All prints processed — {{ $completed }} completed@if($failed > 0), {{ $failed }} failed@endif.
                </div>
            @endif

            {{-- Print grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($prints as $print)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {{-- Thumbnail or placeholder --}}
                        @if($print->isCompleted())
                            <div class="relative group">
                                <img src="{{ $print->outputUrl() }}" alt="Generated print"
                                     class="w-full aspect-square object-cover">
                                <div class="absolute inset-0 bg-black/40 transition-opacity flex items-center justify-center opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 group-focus-within:opacity-100">
                                    <a href="{{ $print->outputUrl() }}" target="_blank"
                                       class="px-3 py-1.5 bg-white text-gray-900 text-xs font-medium rounded hover:bg-gray-100">
                                        Download
                                    </a>
                                </div>
                            </div>
                        @elseif($print->isFailed())
                            <div class="aspect-square bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                                <div class="text-center p-3">
                                    <svg class="w-8 h-8 text-red-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <p class="text-xs text-red-600 dark:text-red-400">Failed</p>
                                </div>
                            </div>
                        @else
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        @endif

                        {{-- Caption (print_data summary) --}}
                        <div class="p-2">
                            @foreach(array_slice($print->print_data ?? [], 0, 2) as $val)
                                @if($val)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $val }}</p>
                                @endif
                            @endforeach
                            @if(empty(array_filter($print->print_data ?? [])))
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">No data</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @endunless
</div>
