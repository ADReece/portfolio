<div>
    <!-- Header actions -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sets ({{ $sets->count() }})</h3>
        <a href="{{ route('collections.edit', $collectionId) }}"
           class="inline-flex items-center px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-hidden focus:ring-2 focus:ring-gray-500 text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Edit Collection Settings
        </a>
    </div>

    <!-- Success message -->
    @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800 p-3 rounded-sm mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Create New Set -->
    <div class="mb-6">
        <h4 class="text-md font-semibold mb-2 text-gray-900 dark:text-gray-100">Create New Set</h4>
        <form wire:submit.prevent="createSet" class="flex gap-2">
            <input type="text" wire:model="setName" placeholder="Set name..."
                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                Create Set
            </button>
        </form>
        @error('setName')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Existing Sets -->
    <div>
        @if($sets->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">No sets yet. Create your first set above to start organizing photos.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($sets as $set)
                    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                        @if($editingSetId === $set->id)
                            <!-- Edit Mode -->
                            <form wire:submit.prevent="updateSet" class="space-y-3">
                                <input type="text"
                                       wire:model="editingSetName"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @error('editingSetName')
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="flex-1 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                        Save
                                    </button>
                                    <button type="button"
                                            wire:click="cancelEdit"
                                            class="flex-1 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        @else
                            <!-- View Mode -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="text-md font-semibold text-gray-900 dark:text-gray-100">{{ $set->name }}</h5>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $set->photos->count() }} photos</p>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="editSet('{{ $set->id }}')" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-sm hover:bg-gray-200 dark:hover:bg-gray-600">Rename</button>
                                    <button @click="window.confirmAction({
                                                title: 'Delete Set?',
                                                message: 'Are you sure you want to delete this set? All photos will be removed.',
                                                confirmText: 'Delete',
                                                isDanger: true,
                                                onConfirm: () => $wire.call('deleteSet', '{{ $set->id }}')
                                            })" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('sets.detail', $set->id) }}" class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Manage Photos →</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

