<x-app-layout title="Add print product">
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Add print product" description="Look a product up on Prodigi by its SKU, check what it is, then add it." :back="route('admin.print-catalogue.index')" back-label="Print catalogue" />

        @unless($prodigiReady)
            <x-ui.alert tone="amber" title="Prodigi isn't configured">Set PRODIGI_API_KEY to look up products.</x-ui.alert>
        @endunless

        <x-ui.card title="1. Look up a SKU" description="Find SKUs in Prodigi's product catalogue or dashboard, e.g. GLOBAL-FAP-10X8.">
            <form method="POST" action="{{ route('admin.print-catalogue.lookup') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-56 flex-1">
                    <x-ui.input name="sku" label="Prodigi SKU" :value="old('sku', $lookup['sku'] ?? '')" autocomplete="off" autocapitalize="characters" spellcheck="false" required />
                </div>
                <x-ui.button type="submit" icon="magnifying-glass" variant="secondary">Look up</x-ui.button>
            </form>
        </x-ui.card>

        @unless($lookup)
            <x-ui.card title="Or add many at once" description="Each SKU is checked with Prodigi in the background, added as inactive with Prodigi's description as its name and a guessed category, and its cost quoted. SKUs already in the catalogue are skipped. Lookups and quotes are free.">
                <div class="space-y-8">
                    <form method="POST" action="{{ route('admin.print-catalogue.discover') }}" class="space-y-3">
                        @csrf
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Discover Prodigi products</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Tries every common size of each family (GLOBAL-<em>family</em>-<em>size</em>, about {{ $candidatesPerFamily }} per family) and adds the ones Prodigi has.
                            Sizes that don't exist are simply skipped.
                        </p>
                        <fieldset>
                            <legend class="sr-only">Product families</legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($families as $code => $label)
                                    <x-ui.checkbox name="families[]" id="family_{{ $code }}" :value="$code" :unchecked-value="null"
                                                   :checked="in_array($code, old('families', array_keys($families)), true)"
                                                   label="{{ $label }}" description="GLOBAL-{{ $code }}-…" />
                                @endforeach
                            </div>
                        </fieldset>
                        @error('families')<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                        <x-ui.button type="submit" icon="magnifying-glass" :disabled="! $prodigiReady">Discover products</x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('admin.print-catalogue.import') }}" enctype="multipart/form-data" class="space-y-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @csrf
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Import a CSV</h3>
                        <p id="file-help" class="text-sm text-gray-600 dark:text-gray-400">
                            For example a price sheet from your Prodigi dashboard (Product information → pricing and shipping), or any spreadsheet saved as CSV.
                            It needs a column headed “SKU”; optional “Name” and “Category” columns are used too. Prices in the sheet are ignored: costs are quoted from Prodigi.
                        </p>
                        <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required aria-describedby="file-help" class="block text-sm">
                        @error('file')<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                        <x-ui.button type="submit" icon="arrow-up-tray" variant="secondary" :disabled="! $prodigiReady">Import CSV</x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('admin.print-catalogue.store-many') }}" class="space-y-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @csrf
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Paste SKUs</h3>
                        <x-ui.textarea name="skus" label="Prodigi SKUs, one per line (or separated by commas)" rows="6" placeholder="GLOBAL-FAP-10X8&#10;GLOBAL-CAN-16X20&#10;GLOBAL-CFPM-16X20">{{ old('skus') }}</x-ui.textarea>
                        <x-ui.button type="submit" icon="plus" variant="secondary" :disabled="! $prodigiReady">Add all</x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @endunless

        @if($lookup)
            <x-ui.card title="2. Check and add">
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-[auto_1fr]">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">SKU</dt><dd class="font-mono">{{ $lookup['sku'] }}</dd>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Prodigi description</dt><dd>{{ $lookup['description'] ?? '—' }}</dd>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Size</dt><dd>{{ $lookup['size_label'] ?? '—' }}</dd>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Options</dt>
                    <dd>
                        @forelse($lookup['attributes'] as $name => $values)
                            <div><span class="font-medium">{{ \App\Models\ProdigiProduct::labelAttribute($name) }}:</span> {{ implode(', ', $values) }}</div>
                        @empty
                            None
                        @endforelse
                    </dd>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Print areas</dt><dd>{{ implode(', ', $lookup['print_areas']) ?: '—' }}</dd>
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Variants</dt><dd>{{ $lookup['variants'] }}</dd>
                </dl>

                <form method="POST" action="{{ route('admin.print-catalogue.store') }}" class="mt-6 space-y-4 border-t border-gray-200 pt-5 dark:border-gray-700">
                    @csrf
                    <input type="hidden" name="sku" value="{{ $lookup['sku'] }}">
                    <x-ui.input name="name" label="Name shown to photographers and clients" :value="old('name', $lookup['description'] ?? $lookup['sku'])" required />
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Category</label>
                        <select id="category" name="category" class="mt-1 w-full rounded-control border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
                            @foreach(\App\Models\ProdigiProduct::CATEGORIES as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">It's added as <strong>inactive</strong> and its UK cost is quoted straight away (free; no order is placed). Then set its price and options, and make it active.</p>
                    <x-ui.button type="submit" icon="plus">Add to catalogue</x-ui.button>
                </form>
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
