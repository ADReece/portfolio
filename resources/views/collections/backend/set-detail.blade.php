<x-app-layout :title="$set->name.' · '.$set->collection->name">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="$set->name" :back="route('collections.edit', $set->collection)" :back-label="$set->collection->name"
                          description="Upload photos to this set, then edit captions, choose a cover or remove photos below." />

        <x-ui.card title="Upload photos">
            @livewire('photo-upload', ['setId' => $set->id], key('upload-'.$set->id))
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">JPEG, PNG or WebP, up to 100MB each. Thumbnails and watermarked copies are made automatically.</p>
        </x-ui.card>

        @livewire('set-photos', ['setId' => $set->id], key('photos-'.$set->id))

        <div x-data="{
                       selected: @js($set->templates->pluck('id')->map(fn ($id) => (string) $id)->values()),
                       saving: false,
                       status: '',
                       save() {
                           this.saving = true;
                           this.status = '';
                           fetch(@js(route('sets.templates.sync', $set)), {
                               method: 'POST',
                               headers: {
                                   'Content-Type': 'application/json',
                                   'Accept': 'application/json',
                                   'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                               },
                               body: JSON.stringify({ template_ids: this.selected }),
                           })
                           .then(r => { if (!r.ok) throw new Error(); this.status = 'Saved'; setTimeout(() => this.status = '', 2500); })
                           .catch(() => window.showErrorToast('We couldn’t save your template choices. Try again.'))
                           .finally(() => this.saving = false);
                       },
                   }">
        <x-ui.card title="Print templates for downloads"
                   description="Clients can add your design (names, dates, a border) when they download a photo from this set.">
            @if($userTemplates->isEmpty())
                <x-ui.empty-state icon="document-duplicate" title="No print templates yet"
                                  description="Design a template once, then offer it on any set." class="py-6">
                    <x-ui.button variant="secondary" icon="plus" :href="route('templates.create')">Create a template</x-ui.button>
                </x-ui.empty-state>
            @else
                <fieldset>
                    <legend class="sr-only">Templates offered on this set</legend>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($userTemplates as $tmpl)
                            <label class="flex cursor-pointer items-start gap-3 rounded-control p-3 ring-1 transition"
                                   :class="selected.includes(@js((string) $tmpl->id)) ? 'bg-brand-50 ring-brand-500 dark:bg-brand-950' : 'ring-gray-200 hover:ring-gray-300 dark:ring-gray-700 dark:hover:ring-gray-600'">
                                <input type="checkbox" value="{{ $tmpl->id }}" x-model="selected"
                                       class="mt-0.5 size-4 rounded-sm border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tmpl->name }}</span>
                                    @if($tmpl->description)
                                        <span class="block truncate text-sm text-gray-600 dark:text-gray-400">{{ $tmpl->description }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <x-ui.button variant="ghost" size="sm" icon="printer" :href="route('collections.batch', $set->collection)">Make prints for the whole collection</x-ui.button>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-green-700 dark:text-green-400" x-text="status" role="status" aria-live="polite"></span>
                        <x-ui.button x-on:click="save()" x-bind:disabled="saving">
                            <span x-text="saving ? 'Saving…' : 'Save templates'">Save templates</span>
                        </x-ui.button>
                    </div>
                </div>
            @endif
        </x-ui.card>
        </div>
    </div>
</x-app-layout>
