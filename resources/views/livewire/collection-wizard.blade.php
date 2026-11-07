<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-gray-900 dark:to-gray-900 py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <!-- Step 1 -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }} font-semibold">
                        @if($currentStep > 1)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @else
                            1
                        @endif
                    </div>
                    <span class="ml-2 text-sm font-medium {{ $currentStep >= 1 ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500' }}">Name Collection</span>
                </div>

                <!-- Connector -->
                <div class="w-16 h-1 mx-4 {{ $currentStep >= 2 ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>

                <!-- Step 2 -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }} font-semibold">
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium {{ $currentStep >= 2 ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500' }}">Add Sets</span>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if (session()->has('message'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-md">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
                </div>
            </div>
        @endif

        <!-- Step 1: Collection Details -->
        @if($currentStep === 1)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Collection in 30 Seconds</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Type a name, hit continue, done. You can tweak advanced settings later.</p>
                </div>

                <form wire:submit.prevent="saveCollection" class="p-6 space-y-6">
                    <!-- Collection Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Collection Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               wire:model="name"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="e.g., Sam & Maya Wedding"
                               autofocus>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="event_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date (optional)
                        </label>
                        <input type="date"
                               id="event_date"
                               wire:model="event_date"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('event_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <details class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-gray-100">Advanced privacy and display options</summary>

                        <div class="space-y-4 mt-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           id="private"
                                           wire:model="private"
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                                </div>
                                <div class="ml-3">
                                    <label for="private" class="text-sm font-medium text-gray-700 dark:text-gray-300">Private Collection</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Require a password to view this collection</p>
                                </div>
                            </div>

                            <div x-show="$wire.private" x-transition>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password"
                                       id="password"
                                       wire:model="password"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="Enter a secure password">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           id="watermarked"
                                           wire:model="watermarked"
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                                </div>
                                <div class="ml-3">
                                    <label for="watermarked" class="text-sm font-medium text-gray-700 dark:text-gray-300">Watermarked</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Add watermark to preview images</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox"
                                           id="hide_from_portfolio"
                                           wire:model="hide_from_portfolio"
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                                </div>
                                <div class="ml-3">
                                    <label for="hide_from_portfolio" class="text-sm font-medium text-gray-700 dark:text-gray-300">Hide from Public Portfolio</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Only visible in your dashboard/client links</p>
                                </div>
                            </div>
                        </div>
                    </details>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('collections.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 border border-transparent rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Continue
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Step 2: Manage Sets -->
        @if($currentStep === 2)
            <div class="space-y-6">
                <!-- Collection Info Card -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $collection->name }}</h2>
                            @if($collection->event_date)
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($collection->event_date)->format('F j, Y') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('collections.edit', $collectionId) }}"
                           class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Edit Settings
                        </a>
                    </div>
                </div>

                <!-- Create New Set Card -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create Sets Fast</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Tap a template or type your own.</p>
                    </div>

                    <div class="px-6 pt-6 pb-2 flex flex-wrap gap-2">
                        @foreach($quickSetTemplates as $template)
                            <button type="button"
                                    wire:click="createSetFromTemplate('{{ $template }}')"
                                    class="px-3 py-1.5 text-sm rounded-full border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-300 dark:border-indigo-800">
                                + {{ $template }}
                            </button>
                        @endforeach
                    </div>

                    <form wire:submit.prevent="createSet" class="p-6 pt-4">
                        <div class="flex gap-3">
                            <input type="text"
                                   wire:model="setName"
                                   placeholder="Type custom set name and press Enter"
                                   class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit"
                                    class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 border border-transparent rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 whitespace-nowrap">
                                Add
                            </button>
                        </div>
                        @error('setName')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </form>
                </div>

                <!-- Existing Sets -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Sets ({{ $sets->count() }})</h3>
                    </div>

                    <div class="p-6">
                        @if($sets->isEmpty())
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No sets yet</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first set above.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($sets as $set)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        @if($editingSetId === $set->id)
                                            <!-- Edit Mode -->
                                            <form wire:submit.prevent="updateSet" class="space-y-3">
                                                <input type="text"
                                                       wire:model="editingSetName"
                                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
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
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $set->name }}</h4>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $set->photos_count }} {{ Str::plural('photo', $set->photos_count) }}
                                                    </p>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button wire:click="editSet('{{ $set->id }}')"
                                                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                            title="Rename set">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button @click="window.confirmAction({
                                                                title: 'Delete Set?',
                                                                message: 'Are you sure you want to delete this set? All photos will be removed.',
                                                                confirmText: 'Delete',
                                                                isDanger: true,
                                                                onConfirm: () => $wire.call('deleteSet', '{{ $set->id }}')
                                                            })"
                                                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                                            title="Delete set">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                <a href="{{ route('sets.detail', $set->id) }}"
                                                   class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                                    Open Set
                                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center">
                    <a href="{{ route('collections.index') }}"
                       class="inline-flex items-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                        <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Collections
                    </a>

                    @if($sets->isNotEmpty())
                        <button wire:click="finishAndRedirect"
                                class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Finish Setup
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

