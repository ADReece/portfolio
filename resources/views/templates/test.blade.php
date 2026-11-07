<x-app-layout :title="'Try ' . $template->name">
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="'Try “' . $template->name . '” on a photo'"
                          description="Pick one of your photos and fill in the text to see exactly how the finished print will look."
                          :back="route('templates.edit', $template)" back-label="Back to template" />

        @if($errors->any())
            <x-ui.alert tone="red" title="Please fix the following">
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if($photos->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="photo" title="You haven't uploaded any photos yet"
                                  description="Upload a few photos to a collection, then come back to try this template.">
                    <x-ui.button icon="arrow-up-tray" :href="route('dashboard')">Go to your collections</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('templates.runTest', $template) }}" class="space-y-6">
                @csrf

                <x-ui.card title="1. Choose a photo" description="Showing your 50 most recent photos.">
                    <fieldset>
                        <legend class="sr-only">Choose a photo</legend>
                        <div class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto p-1 sm:grid-cols-6">
                            @foreach($photos as $photo)
                                <label class="group relative cursor-pointer">
                                    <input type="radio" name="photo_id" value="{{ $photo->id }}" required
                                           class="peer sr-only" {{ old('photo_id') === $photo->id ? 'checked' : '' }}>
                                    <img src="{{ $photo->getAwsThumbnail() }}" alt="Photo {{ $loop->iteration }}" loading="lazy"
                                         class="aspect-square w-full rounded-control object-cover ring-2 ring-transparent transition
                                                group-hover:ring-brand-300 peer-checked:ring-brand-600 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 dark:peer-focus-visible:ring-offset-gray-800">
                                    <span class="pointer-events-none absolute inset-0 rounded-control bg-brand-600/20 opacity-0 transition-opacity peer-checked:opacity-100"></span>
                                    <span class="pointer-events-none absolute right-1 top-1 hidden rounded-full bg-brand-600 p-0.5 text-white peer-checked:block">
                                        <x-ui.icon name="check" variant="m" class="size-4" />
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </x-ui.card>

                <x-ui.card title="2. Fill in the text">
                    @if(count($template->fields) > 0)
                        <div class="space-y-4">
                            @foreach($template->fields as $field)
                                <x-ui.input :id="'field-value-'.$loop->index"
                                            name="field_values[{{ $field['key'] }}]"
                                            :value="old('field_values.' . $field['key'], '')"
                                            :label="$field['label'] ?? $field['key']"
                                            :placeholder="'e.g. ' . ($field['label'] ?? $field['key'])" />
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">This template has no text fields, so only the overlay will be added.</p>
                    @endif
                </x-ui.card>

                <div class="flex justify-end">
                    <x-ui.button type="submit" size="lg" icon="sparkles">Create preview</x-ui.button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
