{{-- Polls while photos are still being prepared, so they appear without a reload. --}}
<div @if($set->photos->contains('status', \App\Models\Photo::PROCESSING)) wire:poll.4s="refreshPhotos" @endif>
    @if (session()->has('message'))
        <x-ui.alert tone="green" role="status" class="mb-4">{{ session('message') }}</x-ui.alert>
    @endif

    <section class="rounded-card bg-white shadow-xs ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700"
         aria-labelledby="set-photos-heading"
         x-data="{
            selected: [],
            all: @js($set->photos->pluck('id')->map(fn ($id) => (string) $id)->values()),
            get allSelected() { return this.all.length > 0 && this.selected.length === this.all.length },
            toggleAll() { this.selected = this.allSelected ? [] : [...this.all] },
            deleteSelected() {
                const ids = [...this.selected];
                window.confirmAction({
                    title: `Delete ${ids.length} ${ids.length === 1 ? 'photo' : 'photos'}?`,
                    message: 'They are removed from this set and from your portfolio. This can\'t be undone.',
                    confirmText: 'Delete',
                    isDanger: true,
                    onConfirm: () => { this.$wire.bulkDelete(ids); this.selected = []; },
                });
            },
         }"
         x-effect="all = @js($set->photos->pluck('id')->map(fn ($id) => (string) $id)->values()); selected = selected.filter(id => all.includes(id))">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:px-6 dark:border-gray-700">
            <h2 id="set-photos-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">
                Photos <span class="font-normal text-gray-500 dark:text-gray-400">({{ $set->photos->count() }})</span>
            </h2>
            @if($set->photos->isNotEmpty())
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-on:change="toggleAll()" x-bind:checked="allSelected"
                               class="size-4 rounded-sm border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                        Select all
                    </label>
                    <x-ui.button variant="secondary" size="sm" icon="arrow-down-tray" x-show="selected.length" x-cloak
                                 x-on:click="window.portfolioDownload({ set_id: @js((string) $set->id), photo_ids: [...selected] }, $el)">
                        <span x-text="`Download ${selected.length} selected`"></span>
                    </x-ui.button>
                    <x-ui.button variant="secondary" size="sm" icon="arrow-down-tray" x-show="!selected.length"
                                 data-download="{{ json_encode(['set_id' => (string) $set->id]) }}">
                        Download set
                    </x-ui.button>
                    <x-ui.button variant="danger" size="sm" icon="trash" x-show="selected.length" x-cloak x-on:click="deleteSelected()">
                        <span x-text="`Delete ${selected.length} selected`"></span>
                    </x-ui.button>
                </div>
            @endif
        </header>

        <div class="p-5 sm:p-6">
            @if($set->photos->isEmpty())
                <x-ui.empty-state icon="photo" title="No photos in this set yet" description="Drag photos into the upload box above, or choose files from your computer." class="py-6" />
            @else
                <ul role="list" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($set->photos as $photo)
                        @php($isCover = (string) $set->collection->cover_photo_id === (string) $photo->id)
                        <li class="group relative" wire:key="photo-{{ $photo->id }}">
                            <div class="relative overflow-hidden rounded-control bg-gray-100 ring-2 transition dark:bg-gray-900"
                                 :class="selected.includes(@js((string) $photo->id)) ? 'ring-brand-500' : 'ring-transparent'">
                                @if($photo->status === \App\Models\Photo::PROCESSING)
                                    <div class="flex aspect-square w-full flex-col items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-300" role="status">
                                        <svg class="size-6 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                        Preparing…
                                    </div>
                                @elseif($photo->status === \App\Models\Photo::FAILED)
                                    <div class="flex aspect-square w-full flex-col items-center justify-center gap-2 bg-red-50 p-3 text-center text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200" role="alert">
                                        <x-ui.icon name="exclamation-triangle" class="size-6" />
                                        <span>{{ $photo->processing_error ?: \App\Models\Photo::FAILED_MESSAGE }}</span>
                                        <span class="flex gap-3">
                                            <button type="button" wire:click="retryPhoto('{{ $photo->id }}')" class="font-semibold underline underline-offset-4">Retry</button>
                                            <button type="button" wire:click="deletePhoto('{{ $photo->id }}')" class="font-semibold underline underline-offset-4">Remove</button>
                                        </span>
                                    </div>
                                @else
                                    <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ?: 'Photo '.$loop->iteration }}" loading="lazy"
                                         class="aspect-square w-full object-cover">
                                @endif
                                <input type="checkbox" x-model="selected" value="{{ $photo->id }}"
                                       aria-label="Select {{ $photo->caption ?: 'photo '.$loop->iteration }}"
                                       class="absolute top-2 left-2 size-5 rounded-sm border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500">
                                @if($isCover)
                                    <span class="absolute top-2 right-2"><x-ui.badge tone="green" icon="star" class="shadow-sm">Cover</x-ui.badge></span>
                                @endif
                                @if($photo->hide_from_portfolio)
                                    <span class="absolute bottom-2 left-2"><x-ui.badge icon="eye-slash" class="shadow-sm">Hidden</x-ui.badge></span>
                                @endif
                            </div>
                            <div class="mt-2 flex items-center gap-1">
                                <p class="min-w-0 flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ $photo->caption ?: 'No caption' }}</p>
                                <button type="button" wire:click="editPhoto('{{ $photo->id }}')" title="Edit details"
                                        class="rounded-control p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <x-ui.icon name="pencil-square" class="size-4" label="Edit details" />
                                </button>
                                @unless($isCover)
                                    <button type="button" wire:click="setAsCover('{{ $photo->id }}')" title="Use as cover"
                                            class="rounded-control p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <x-ui.icon name="star" class="size-4" label="Use as cover" />
                                    </button>
                                @endunless
                                <button type="button" title="Delete"
                                        x-on:click="window.confirmAction({ title: 'Delete this photo?', message: 'It will be removed from this set and your portfolio. This can’t be undone.', confirmText: 'Delete', isDanger: true, onConfirm: () => $wire.deletePhoto('{{ $photo->id }}') })"
                                        class="rounded-control p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 dark:text-gray-400 dark:hover:bg-red-950 dark:hover:text-red-300">
                                    <x-ui.icon name="trash" class="size-4" label="Delete" />
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    @if($editingPhotoId)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-on:keydown.escape.window="$wire.set('editingPhotoId', null)">
            <div class="fixed inset-0 bg-gray-950/50" wire:click="$set('editingPhotoId', null)" aria-hidden="true"></div>
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
                <div role="dialog" aria-modal="true" aria-labelledby="edit-photo-title" x-trap.noscroll="true"
                     class="relative w-full overflow-hidden rounded-card bg-white shadow-xl sm:max-w-md dark:bg-gray-800">
                    <form wire:submit="updatePhoto">
                        <div class="space-y-4 p-5 sm:p-6">
                            <h2 id="edit-photo-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">Photo details</h2>
                            <x-ui.input id="caption" name="editingCaption" wire:model="editingCaption" label="Caption" optional
                                        help="Shown under the photo in the gallery and used as its description for screen readers." />
                            <x-ui.textarea id="description" name="editingDescription" wire:model="editingDescription" label="Description" optional rows="3" />
                            <x-ui.checkbox id="editingHideFromPortfolio" wire:model="editingHideFromPortfolio" :unchecked-value="null"
                                           label="Hide from my portfolio" description="It still appears in this collection." />
                        </div>
                        <div class="flex flex-col-reverse gap-2 bg-gray-50 px-5 py-3 sm:flex-row sm:justify-end sm:px-6 dark:bg-gray-900/60">
                            <x-ui.button variant="secondary" wire:click="$set('editingPhotoId', null)">Cancel</x-ui.button>
                            <x-ui.button type="submit" wire:target="updatePhoto">Save</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
