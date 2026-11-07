<div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-ui.page-header :title="'Make prints for '.$collection->name" :back="route('collections.edit', $collection)" :back-label="$collection->name"
                      description="Add one of your print templates to every photo in this collection, then download the results." />

    {{-- Feature gate --}}
    @unless(auth()->user()->hasFeature('custom_templates'))
        <x-ui.alert tone="amber" title="Print templates aren't included in your plan">
            Upgrade to add a template to every photo in a collection at once.
            <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
        </x-ui.alert>
    @else

    {{-- ══════════════════════════════════
         STEP 1 — SELECT TEMPLATE
    ══════════════════════════════════ --}}
    @if($step === 'select_template')
        <div class="space-y-5">
            <div>
                <p class="text-sm font-medium text-brand-700 dark:text-brand-300">Step 1 of 3</p>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Choose a template</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Pick the overlay and text fields to add to this collection's photos.
                </p>
            </div>

            @error('selectedTemplateId')
                <x-ui.alert tone="red">{{ $message }}</x-ui.alert>
            @enderror

            @if($templates->isEmpty())
                <x-ui.empty-state icon="swatch" title="You haven't created any templates yet"
                                  description="Create a template with an overlay and text fields, then come back here to use it.">
                    <x-ui.button icon="plus" :href="route('templates.create')">Create a template</x-ui.button>
                </x-ui.empty-state>
            @else
                <ul role="list" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($templates as $tmpl)
                        @php $isSelected = $selectedTemplateId === $tmpl->id; @endphp
                        <li>
                            <button type="button"
                                    wire:click="selectTemplate('{{ $tmpl->id }}')"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                    @class([
                                        'relative block w-full overflow-hidden rounded-card text-left transition focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                                        'ring-2 ring-brand-600 dark:ring-brand-400' => $isSelected,
                                        'ring-1 ring-gray-200 hover:ring-brand-300 dark:ring-gray-700 dark:hover:ring-brand-700' => ! $isSelected,
                                    ])>
                                @if($tmpl->overlay_path)
                                    <span class="flex h-24 items-center justify-center overflow-hidden bg-gray-900">
                                        <img src="{{ $tmpl->overlayUrl() }}" alt="" loading="lazy" class="h-full w-full object-contain">
                                    </span>
                                @else
                                    <span class="flex h-24 flex-col items-center justify-center gap-1 bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                                        <x-ui.icon name="document-text" class="size-6" />
                                        <span class="text-xs font-medium">Text only</span>
                                    </span>
                                @endif

                                @if($isSelected)
                                    <span class="absolute right-2 top-2 rounded-full bg-brand-600 p-0.5 text-white shadow">
                                        <x-ui.icon name="check" variant="m" class="size-4" />
                                    </span>
                                @endif

                                <span class="block bg-white p-3 dark:bg-gray-800">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tmpl->name }}</span>
                                    @if(!empty($tmpl->fields))
                                        <span class="mt-1 block text-xs text-gray-600 dark:text-gray-400">
                                            {{ count($tmpl->fields) }} text {{ \Illuminate\Support\Str::plural('field', count($tmpl->fields)) }}:
                                            {{ implode(', ', array_column($tmpl->fields, 'label')) }}
                                        </span>
                                    @else
                                        <span class="mt-1 block text-xs text-gray-600 dark:text-gray-400">Overlay only</span>
                                    @endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="flex justify-end">
                    <x-ui.button wire:click="proceed" wire:target="proceed" icon-trailing="arrow-right">Continue</x-ui.button>
                </div>
            @endif
        </div>

    {{-- ══════════════════════════════════
         STEP 2 — ENTER PER-PHOTO DATA
    ══════════════════════════════════ --}}
    @elseif($step === 'enter_data')
        <div class="space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-brand-700 dark:text-brand-300">Step 2 of 3</p>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Add the text for each photo</h3>
                    @if($selectedTemplate)
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Using <strong class="font-semibold text-gray-900 dark:text-gray-100">{{ $selectedTemplate->name }}</strong>. Fill in the text for each photo below.
                        </p>
                    @endif
                </div>
                <x-ui.button variant="ghost" size="sm" icon="arrow-left" wire:click="$set('step', 'select_template')">Change template</x-ui.button>
            </div>

            @if($photos->isEmpty())
                <x-ui.empty-state icon="photo" title="This collection has no photos yet"
                                  description="Upload photos to this collection, then come back to create prints." />
            @else
                <div class="overflow-x-auto rounded-card ring-1 ring-gray-200 dark:ring-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th scope="col" class="w-24 px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Photo
                                </th>
                                @if($selectedTemplate && !empty($selectedTemplate->fields))
                                    @foreach($selectedTemplate->fields as $field)
                                        <th scope="col" class="min-w-40 px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            {{ $field['label'] }}
                                        </th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @foreach($photos as $photo)
                                @php $photoNumber = $loop->iteration; @endphp
                                <tr wire:key="batch-photo-{{ $photo->id }}">
                                    <td class="px-4 py-3">
                                        @if($photo->thumbnail_url ?? null)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($photo->thumbnail_url, now()->addMinutes(30)) }}"
                                                 alt="Photo {{ $photoNumber }}"
                                                 class="size-16 rounded-control object-cover">
                                        @else
                                            <div class="flex size-16 items-center justify-center rounded-control bg-gray-100 text-gray-400 dark:bg-gray-700">
                                                <x-ui.icon name="photo" class="size-6" />
                                                <span class="sr-only">Photo {{ $photoNumber }} (no preview)</span>
                                            </div>
                                        @endif
                                    </td>

                                    @if($selectedTemplate && !empty($selectedTemplate->fields))
                                        @foreach($selectedTemplate->fields as $field)
                                            <td class="px-4 py-3">
                                                <label for="batch-{{ $photo->id }}-{{ $loop->index }}" class="sr-only">{{ $field['label'] }} for photo {{ $photoNumber }}</label>
                                                <input type="text" id="batch-{{ $photo->id }}-{{ $loop->index }}"
                                                       wire:model="photoData.{{ $photo->id }}.{{ $field['key'] }}"
                                                       placeholder="{{ $field['label'] }}"
                                                       class="block w-full rounded-control border-gray-300 text-sm shadow-xs focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    {{-- Uses the app's confirm dialog. Kept on a plain <button> because @js() can't be used inside component tag attributes. --}}
                    <button type="button"
                            x-data
                            x-on:click="window.confirmAction({
                                title: @js('Create '.$photos->count().' '.\Illuminate\Support\Str::plural('print', $photos->count()).'?'),
                                message: 'Prints are created in the background. You can leave this page and come back to download them.',
                                confirmText: 'Create prints',
                                onConfirm: () => $wire.generate(),
                            })"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-2 rounded-control bg-brand-600 px-4 py-2 text-sm font-semibold whitespace-nowrap text-white shadow-xs transition-colors hover:bg-brand-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-400 dark:focus-visible:ring-offset-gray-900">
                        <x-ui.icon name="sparkles" class="size-4" />
                        <span wire:loading.remove>Create {{ $photos->count() }} {{ \Illuminate\Support\Str::plural('print', $photos->count()) }}</span>
                        <span wire:loading>Starting…</span>
                    </button>
                </div>
            @endif
        </div>

    {{-- ══════════════════════════════════
         STEP 3 — PROCESSING / RESULTS
    ══════════════════════════════════ --}}
    @elseif($step === 'processing')
        @php
            $completed = $prints->where('status', 'completed')->count();
            $failed    = $prints->where('status', 'failed')->count();
            $pending   = $prints->whereIn('status', ['pending', 'processing'])->count();
            $total     = $prints->count();
            $finished  = $completed + $failed;
        @endphp

        {{-- Poll for status only while prints are still being made. --}}
        <div class="space-y-5" @unless($allDone) wire:poll.2000ms @endunless>
            <div>
                <p class="text-sm font-medium text-brand-700 dark:text-brand-300">Step 3 of 3</p>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $allDone ? 'Your prints are ready' : 'Creating your prints' }}
                </h3>
            </div>

            <p role="status" aria-live="polite" class="sr-only">
                @if($allDone)
                    All prints finished: {{ $completed }} ready@if($failed > 0), {{ $failed }} failed@endif.
                @else
                    {{ $finished }} of {{ $total }} prints finished.
                @endif
            </p>

            @if(!$allDone)
                <div>
                    <div class="mb-1 flex justify-between text-sm text-gray-600 dark:text-gray-400" aria-hidden="true">
                        <span class="tabular-nums">{{ $completed }} of {{ $total }} ready</span>
                        <span class="tabular-nums">{{ $pending }} to go</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                         role="progressbar" aria-label="Prints created"
                         aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $finished }}"
                         aria-valuetext="{{ $finished }} of {{ $total }} finished">
                        <div class="h-2 rounded-full bg-brand-600 transition-all motion-reduce:transition-none dark:bg-brand-500"
                             style="width: {{ $total > 0 ? round(($finished / $total) * 100) : 0 }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">You can leave this page — prints keep being created in the background.</p>
                </div>
            @else
                <x-ui.alert :tone="$failed > 0 ? 'amber' : 'green'" :title="$completed.' '.\Illuminate\Support\Str::plural('print', $completed).' ready'">
                    @if($failed > 0)
                        {{ $failed }} {{ \Illuminate\Support\Str::plural('print', $failed) }} couldn't be created.
                    @else
                        Every print was created. Download them below.
                    @endif
                </x-ui.alert>
            @endif

            <ul role="list" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                @foreach($prints as $print)
                    <li wire:key="batch-print-{{ $print->id }}" class="overflow-hidden rounded-card bg-white ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        @if($print->isCompleted())
                            <div class="group relative">
                                <img src="{{ $print->outputUrl() }}" alt="Print {{ $loop->iteration }}" class="aspect-square w-full object-cover">
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-100 transition-opacity can-hover:opacity-0 can-hover:group-hover:opacity-100 group-focus-within:opacity-100">
                                    <a href="{{ $print->outputUrl() }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 rounded-control bg-white px-3 py-1.5 text-xs font-semibold text-gray-900 hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
                                        <x-ui.icon name="arrow-down-tray" class="size-4" />
                                        Download<span class="sr-only"> print {{ $loop->iteration }}</span>
                                    </a>
                                </div>
                            </div>
                        @elseif($print->isFailed())
                            <div class="flex aspect-square flex-col items-center justify-center gap-1 bg-red-50 p-3 text-center dark:bg-red-950/40">
                                <x-ui.icon name="exclamation-triangle" class="size-8 text-red-600 dark:text-red-400" />
                                <p class="text-xs font-medium text-red-700 dark:text-red-300">Couldn't create</p>
                            </div>
                        @else
                            <div class="flex aspect-square items-center justify-center bg-gray-100 dark:bg-gray-700">
                                <svg class="size-8 animate-spin text-gray-400 motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                                    <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                                <span class="sr-only">Creating…</span>
                            </div>
                        @endif

                        <div class="p-2">
                            @foreach(array_slice($print->print_data ?? [], 0, 2) as $val)
                                @if($val)
                                    <p class="truncate text-xs text-gray-700 dark:text-gray-300">{{ $val }}</p>
                                @endif
                            @endforeach
                            @if(empty(array_filter($print->print_data ?? [])))
                                <p class="text-xs italic text-gray-500 dark:text-gray-400">No text</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @endunless
</div>
