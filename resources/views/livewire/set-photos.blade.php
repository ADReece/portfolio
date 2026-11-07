<div>
    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Photos Grid -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg"
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
        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Photos ({{ $set->photos->count() }})</h3>
                @if($set->photos->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-on:change="toggleAll()" x-bind:checked="allSelected"
                                   class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                            Select all
                        </label>
                        <button type="button" x-show="selected.length" x-cloak x-on:click="deleteSelected()"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                            x-text="`Delete ${selected.length} selected`">
                        </button>
                    </div>
                @endif
            </div>

            @if($set->photos->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">No photos yet. Upload your first photos above.</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($set->photos as $photo)
                        <div class="relative group photo-item" data-photo-id="{{ $photo->id }}" wire:key="photo-{{ $photo->id }}">
                            <input type="checkbox" x-model="selected" value="{{ $photo->id }}"
                                aria-label="Select {{ $photo->caption ?: 'photo' }}"
                                class="absolute top-2 left-2 z-10 w-5 h-5 rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">

                            @if($set->collection->cover_photo_id === $photo->id)
                                <div class="absolute top-2 right-2 z-10 px-2 py-1 bg-green-500 text-white rounded-full text-xs font-semibold shadow-lg flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Cover
                                </div>
                            @endif

                            <img src="{{ $photo->getAwsThumbnail() }}"
                                alt="{{ $photo->caption ?? 'Photo' }}"
                                class="w-full h-48 object-cover rounded-lg">

                            <div class="absolute inset-0 bg-black/30 can-hover:bg-black/50 transition-opacity rounded-lg flex items-center justify-center opacity-100 can-hover:opacity-0 can-hover:group-hover:opacity-100 group-focus-within:opacity-100">
                                <div class="flex flex-col gap-2">
                                    <div class="flex gap-2">
                                        <button wire:click="editPhoto('{{ $photo->id }}')"
                                            class="px-3 py-1 bg-white text-gray-800 rounded-sm text-sm hover:bg-gray-100">
                                            Edit
                                        </button>
                                        <button @click="window.confirmAction({
                                                    title: 'Delete Photo?',
                                                    message: 'Are you sure you want to delete this photo? This action cannot be undone.',
                                                    confirmText: 'Delete',
                                                    isDanger: true,
                                                    onConfirm: () => $wire.call('deletePhoto', '{{ $photo->id }}')
                                                })"
                                            class="px-3 py-1 bg-red-600 text-white rounded-sm text-sm hover:bg-red-700">
                                            Delete
                                        </button>
                                    </div>
                                    @if($set->collection->cover_photo_id === $photo->id)
                                        <span class="px-3 py-1 bg-green-500 text-white rounded-sm text-xs text-center">
                                            ★ Cover Photo
                                        </span>
                                    @else
                                        <button wire:click="setAsCover('{{ $photo->id }}')"
                                            class="px-3 py-1 bg-indigo-600 text-white rounded-sm text-sm hover:bg-indigo-700">
                                            Set as Cover
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($photo->caption)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 truncate">{{ $photo->caption }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Photo Modal -->
    @if($editingPhotoId)
    <div class="fixed inset-0 bg-gray-600/50 overflow-y-auto h-full w-full z-50"
         wire:click.self="$set('editingPhotoId', null)">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 dark:border-gray-700">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Edit Photo</h3>
                <form wire:submit.prevent="updatePhoto">
                    <div class="mb-4">
                        <label for="caption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Caption</label>
                        <input type="text" id="caption" wire:model="editingCaption"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                        @error('editingCaption')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <textarea id="description" wire:model="editingDescription" rows="3"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('editingDescription')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="editingHideFromPortfolio"
                                class="rounded-sm border-gray-300 dark:border-gray-600 text-indigo-600 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Hide from portfolio</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">When enabled, this photo won't appear on your main portfolio page.</p>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button type="button" wire:click="$set('editingPhotoId', null)"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>

