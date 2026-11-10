<div class="space-y-4">
    @if (session()->has('message'))
        <x-ui.alert tone="green" role="status">{{ session('message') }}</x-ui.alert>
    @endif

    @if($sets->isEmpty())
        <x-ui.empty-state icon="squares-plus" title="Add a set to start uploading"
                          description="Sets group photos within a collection, like “Ceremony” and “Reception”. You need at least one." class="py-6" />
    @else
        <ul role="list" class="divide-y divide-gray-200 rounded-card ring-1 ring-gray-200 dark:divide-gray-700 dark:ring-gray-700">
            @foreach($sets as $set)
                <li wire:key="set-{{ $set->id }}" class="flex flex-wrap items-center gap-4 p-4">
                    @if($editingSetId === $set->id)
                        <form wire:submit="updateSet" class="flex w-full flex-wrap items-end gap-2">
                            <x-ui.input name="editingSetName" wire:model="editingSetName" label="Set name" wrapper-class="min-w-48 flex-1" autofocus />
                            <x-ui.button type="submit" size="sm" wire:target="updateSet">Save</x-ui.button>
                            <x-ui.button variant="secondary" size="sm" wire:click="cancelEdit">Cancel</x-ui.button>
                        </form>
                    @else
                        <div class="flex -space-x-2" aria-hidden="true">
                            @forelse($set->photos->take(3) as $photo)
                                <img src="{{ $photo->getAwsThumbnail() }}" alt="" loading="lazy" class="size-12 rounded-md object-cover ring-2 ring-white dark:ring-gray-800">
                            @empty
                                <span class="flex size-12 items-center justify-center rounded-md bg-gray-100 text-gray-400 dark:bg-gray-700"><x-ui.icon name="photo" class="size-6" /></span>
                            @endforelse
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $set->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $set->photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $set->photos->count()) }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <x-ui.button size="sm" :variant="$set->photos->isEmpty() ? 'primary' : 'secondary'" :icon="$set->photos->isEmpty() ? 'arrow-up-tray' : 'photo'" :href="route('sets.detail', $set->id)">
                                {{ $set->photos->isEmpty() ? 'Upload photos' : 'Manage photos' }}
                            </x-ui.button>
                            <x-ui.button variant="ghost" size="sm" wire:click="editSet('{{ $set->id }}')" class="!px-2" title="Rename">
                                <x-ui.icon name="pencil-square" class="size-4" label="Rename {{ $set->name }}" />
                            </x-ui.button>
                            <x-ui.button variant="ghost" size="sm" class="!px-2 text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950" title="Delete"
                                         x-on:click="window.confirmAction({
                                            title: {{ \Illuminate\Support\Js::from('Delete “'.$set->name.'”?') }},
                                            message: {{ \Illuminate\Support\Js::from($set->photos->count() ? 'This also deletes the '.$set->photos->count().' '.\Illuminate\Support\Str::plural('photo', $set->photos->count()).' in it. This can’t be undone.' : 'This set is empty.') }},
                                            confirmText: 'Delete set',
                                            isDanger: true,
                                            onConfirm: () => $wire.deleteSet('{{ $set->id }}'),
                                         })">
                                <x-ui.icon name="trash" class="size-4" label="Delete {{ $set->name }}" />
                            </x-ui.button>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <form wire:submit="createSet" class="flex flex-wrap items-end gap-2">
        <x-ui.input name="setName" wire:model="setName" label="New set" placeholder="e.g. Ceremony" wrapper-class="min-w-48 flex-1" />
        <x-ui.button type="submit" variant="secondary" icon="plus" wire:target="createSet">Add set</x-ui.button>
    </form>
</div>
