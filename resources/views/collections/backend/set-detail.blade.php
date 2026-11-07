<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $set->name }} - {{ $set->collection->name }}
            </h2>
            <a href="{{ route('collections.edit', $set->collection) }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Collection
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Upload Section -->
            <div class="mb-8 bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Upload Photos</h3>
                    @livewire('photo-upload', ['setId' => $set->id], key('upload-'.$set->id))
                </div>
            </div>

            <!-- Template Assignment -->
            <div class="mb-8 bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg"
                 x-data="{
                     selected: {{ $set->templates->pluck('id')->toJson() }},
                     saving: false,
                     saved: false,
                     save() {
                         this.saving = true;
                         fetch('{{ route('sets.templates.sync', $set) }}', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             },
                             body: JSON.stringify({ template_ids: this.selected }),
                         })
                         .then(r => r.json())
                         .then(() => { this.saved = true; setTimeout(() => this.saved = false, 2500); })
                         .finally(() => { this.saving = false; });
                     }
                 }">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                            Download Templates
                        </h3>
                        <button @click="save()" :disabled="saving"
                                class="px-4 py-1.5 text-sm bg-indigo-600 text-white rounded-sm hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2">
                            <span x-show="!saving && !saved">Save</span>
                            <span x-show="saving">Saving…</span>
                            <span x-show="saved" class="text-green-300">✓ Saved</span>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Templates checked here will be offered to clients when they download a photo from this set.
                    </p>

                    @if($userTemplates->isEmpty())
                        <p class="text-sm text-gray-400">
                            You have no active templates yet.
                            <a href="{{ route('templates.create') }}" class="underline text-indigo-500">Create one</a>.
                        </p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($userTemplates as $tmpl)
                                <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer"
                                       :class="selected.includes('{{ $tmpl->id }}')
                                               ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                               : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300'">
                                    <input type="checkbox" value="{{ $tmpl->id }}"
                                           x-model="selected"
                                           class="mt-0.5 h-4 w-4 rounded-sm border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $tmpl->name }}</p>
                                        @if($tmpl->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $tmpl->description }}</p>
                                        @endif
                                        @if($tmpl->fields)
                                            <p class="text-xs text-gray-400 mt-0.5">{{ count($tmpl->fields) }} field(s)</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Photos Grid (Livewire Component for Live Updates) -->
            @livewire('set-photos', ['setId' => $set->id], key('photos-'.$set->id))
        </div>
    </div>


</x-app-layout>

