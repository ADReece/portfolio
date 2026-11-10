<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-ui.page-header title="New collection" :back="route('collections.index')" back-label="Collections" />

    {{-- Steps --}}
    <ol class="flex items-center gap-3 text-sm" aria-label="Progress">
        @foreach([1 => 'Name it', 2 => 'Add sets'] as $step => $label)
            <li class="flex items-center gap-2" @if($currentStep === $step) aria-current="step" @endif>
                <span @class([
                    'flex size-7 items-center justify-center rounded-full text-xs font-semibold',
                    'bg-brand-600 text-white' => $currentStep >= $step,
                    'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $currentStep < $step,
                ])>
                    @if($currentStep > $step)<x-ui.icon name="check" variant="m" class="size-4" label="Done:" />@else{{ $step }}@endif
                </span>
                <span @class(['font-medium', 'text-gray-900 dark:text-gray-100' => $currentStep >= $step, 'text-gray-500 dark:text-gray-400' => $currentStep < $step])>{{ $label }}</span>
            </li>
            @if(! $loop->last)<li aria-hidden="true" class="h-px w-10 bg-gray-300 dark:bg-gray-700"></li>@endif
        @endforeach
    </ol>

    @if (session()->has('message'))
        <x-ui.alert tone="green" role="status">{{ session('message') }}</x-ui.alert>
    @endif

    @if($currentStep === 1)
        <form wire:submit="saveCollection">
            <x-ui.card title="What's this collection for?" description="Usually one shoot or event. You can change everything later.">
                <div class="space-y-5">
                    <x-ui.input name="name" wire:model="name" label="Name" placeholder="e.g. Sam & Maya's wedding" required autofocus />
                    <x-ui.input name="event_date" type="date" wire:model="event_date" label="Date of shoot" optional />

                    <details class="group border-t border-gray-200 pt-4 dark:border-gray-700">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-sm text-sm font-semibold text-gray-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-100">
                            <x-ui.icon name="chevron-right" class="size-4 transition group-open:rotate-90" />
                            Privacy and protection
                        </summary>
                        <div class="mt-4 space-y-4 pl-6">
                            <x-ui.checkbox id="private" wire:model="private" :unchecked-value="null" label="Private collection"
                                           description="Clients need a password, or a personal link you email them." />
                            <div x-show="$wire.private" x-cloak class="ml-7 max-w-sm">
                                <x-ui.input name="password" type="text" wire:model="password" label="Password" autocomplete="off" help="Clients type this to open the collection." />
                            </div>
                            <x-ui.checkbox id="watermarked" wire:model="watermarked" :unchecked-value="null" label="Watermark photos"
                                           description="Clients see and download watermarked copies." />
                            <x-ui.checkbox id="hide_from_portfolio" wire:model="hide_from_portfolio" :unchecked-value="null" label="Hide from my portfolio"
                                           description="Only people with the link can see it." />
                        </div>
                    </details>
                </div>
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                    <x-ui.button variant="ghost" :href="route('collections.index')">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon-trailing="arrow-right" wire:target="saveCollection">Continue</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @endif

    @if($currentStep === 2)
        <x-ui.card :title="'Add sets to “'.$collection->name.'”'" description="Sets group the photos in a collection, like “Ceremony” and “Reception”. Add at least one; you'll upload photos into it next.">
            <div class="space-y-5">
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-800 dark:text-gray-200">Suggested sets</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($quickSetTemplates as $suggestion)
                            @php($exists = $sets->contains('name', $suggestion))
                            <button type="button" wire:click="createSetFromTemplate('{{ $suggestion }}')" @disabled($exists)
                                    class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-medium ring-1 transition focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 disabled:cursor-default disabled:opacity-60 {{ $exists ? 'bg-green-50 text-green-800 ring-green-200 dark:bg-green-950 dark:text-green-300 dark:ring-green-900' : 'bg-white text-gray-800 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-700' }}">
                                <x-ui.icon :name="$exists ? 'check' : 'plus'" variant="m" class="size-4" />
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <form wire:submit="createSet" class="flex flex-wrap items-end gap-2">
                    <x-ui.input name="setName" wire:model="setName" label="Or name your own" placeholder="e.g. First dance" wrapper-class="min-w-48 flex-1" />
                    <x-ui.button type="submit" variant="secondary" icon="plus" wire:target="createSet">Add set</x-ui.button>
                </form>

                @if($sets->isNotEmpty())
                    <ul role="list" class="divide-y divide-gray-200 rounded-card ring-1 ring-gray-200 dark:divide-gray-700 dark:ring-gray-700">
                        @foreach($sets as $set)
                            <li class="flex flex-wrap items-center gap-3 p-3" wire:key="wizard-set-{{ $set->id }}">
                                @if($editingSetId === $set->id)
                                    <form wire:submit="updateSet" class="flex w-full flex-wrap items-end gap-2">
                                        <x-ui.input name="editingSetName" wire:model="editingSetName" label="Set name" wrapper-class="min-w-48 flex-1" autofocus />
                                        <x-ui.button type="submit" size="sm">Save</x-ui.button>
                                        <x-ui.button variant="secondary" size="sm" wire:click="cancelEdit">Cancel</x-ui.button>
                                    </form>
                                @else
                                    <x-ui.icon name="folder" class="size-5 text-gray-400" />
                                    <span class="min-w-0 flex-1 truncate font-medium text-gray-900 dark:text-gray-100">{{ $set->name }}</span>
                                    <button type="button" wire:click="editSet('{{ $set->id }}')" title="Rename"
                                            class="rounded-control p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <x-ui.icon name="pencil-square" class="size-4" label="Rename {{ $set->name }}" />
                                    </button>
                                    <button type="button" title="Remove"
                                            x-on:click="window.confirmAction({ title: {{ \Illuminate\Support\Js::from('Remove “'.$set->name.'”?') }}, message: 'The set is empty, so nothing else is removed.', confirmText: 'Remove', isDanger: true, onConfirm: () => $wire.deleteSet('{{ $set->id }}') })"
                                            class="rounded-control p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 dark:text-gray-400 dark:hover:bg-red-950 dark:hover:text-red-300">
                                        <x-ui.icon name="trash" class="size-4" label="Remove {{ $set->name }}" />
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $sets->count() }} {{ \Illuminate\Support\Str::plural('set', $sets->count()) }} added</p>
                <x-ui.button wire:click="finishAndRedirect" :disabled="$sets->isEmpty()" icon-trailing="arrow-right" wire:target="finishAndRedirect">
                    Continue to upload photos
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif
</div>
