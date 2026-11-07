<x-app-layout title="Fonts">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Fonts"
                          description="Upload your own fonts to use for text on your print templates."
                          :back="route('templates.index')" back-label="Print templates" />

        @if($errors->any())
            <x-ui.alert tone="red" title="We couldn't upload that font">
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </x-ui.alert>
        @endif

        <x-ui.card title="Upload a font">
            <form method="POST" action="{{ route('fonts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid items-start gap-4 sm:grid-cols-[1fr_1fr_auto]">
                    <x-ui.input name="name" label="Font name" :value="old('name')" required
                                placeholder="e.g. Roboto Bold" help="How it appears in the font list." />
                    <div class="space-y-1">
                        <label for="font" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Font file</label>
                        <input id="font" type="file" name="font" accept=".ttf,.otf" required aria-describedby="font-help"
                               class="block w-full text-sm text-gray-600 dark:text-gray-400
                                      file:mr-3 file:rounded-control file:border-0 file:bg-brand-50 file:px-3 file:py-1.5
                                      file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100
                                      dark:file:bg-brand-950 dark:file:text-brand-300">
                        <p id="font-help" class="text-sm text-gray-600 dark:text-gray-400">TTF or OTF, up to 5 MB.</p>
                    </div>
                    <div class="sm:pt-6">
                        <x-ui.button type="submit" icon="arrow-up-tray">Upload</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Your fonts" :padding="false">
            @if($userFonts->isEmpty())
                <x-ui.empty-state icon="language" title="No fonts uploaded yet"
                                  description="Templates use OpenSans Bold unless you choose another font." />
            @else
                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($userFonts as $font)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $font->name }}</p>
                                <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $font->filename }} · uploaded {{ $font->created_at->diffForHumans() }}</p>
                            </div>
                            <form method="POST" action="{{ route('fonts.destroy', $font) }}"
                                  data-confirm="Templates using it will switch to the default font." data-confirm-title="Delete this font?" data-confirm-button="Delete font" data-confirm-danger>
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger-outline" size="sm" icon="trash">
                                    Delete<span class="sr-only"> {{ $font->name }}</span>
                                </x-ui.button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        @if($systemFonts->isNotEmpty())
            <x-ui.card title="Built-in fonts" description="Available to everyone — you can't remove these." :padding="false">
                <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($systemFonts as $font)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $font->name }}</p>
                                <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $font->filename }}</p>
                            </div>
                            <x-ui.badge>Built-in</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
