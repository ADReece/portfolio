@php
    $fonts = [
        'system' => ['System default', 'system-ui, -apple-system, "Segoe UI", sans-serif'],
        'inter' => ['Inter', '"Inter", sans-serif'],
        'nunito' => ['Nunito', '"Nunito", sans-serif'],
        'roboto' => ['Roboto', '"Roboto", sans-serif'],
        'open-sans' => ['Open Sans', '"Open Sans", sans-serif'],
        'lato' => ['Lato', '"Lato", sans-serif'],
        'montserrat' => ['Montserrat', '"Montserrat", sans-serif'],
        'playfair' => ['Playfair Display', '"Playfair Display", serif'],
        'merriweather' => ['Merriweather', '"Merriweather", serif'],
    ];
    $presets = [
        ['Classic light', ['theme' => 'light', 'accent' => '#4F46E5', 'bg' => '#FFFFFF', 'text' => '#374151', 'heading' => '#111827']],
        ['Classic dark', ['theme' => 'dark', 'accent' => '#818CF8', 'bg' => '#111827', 'text' => '#E5E7EB', 'heading' => '#F9FAFB']],
        ['Rose', ['theme' => 'light', 'accent' => '#E11D48', 'bg' => '#FFF1F2', 'text' => '#881337', 'heading' => '#4C0519']],
        ['Forest', ['theme' => 'dark', 'accent' => '#10B981', 'bg' => '#064E3B', 'text' => '#D1FAE5', 'heading' => '#ECFDF5']],
        ['Golden hour', ['theme' => 'light', 'accent' => '#D97706', 'bg' => '#FFFBEB', 'text' => '#78350F', 'heading' => '#451A03']],
        ['Midnight', ['theme' => 'dark', 'accent' => '#A855F7', 'bg' => '#1E1B4B', 'text' => '#E9D5FF', 'heading' => '#F3E8FF']],
        ['Minimal', ['theme' => 'light', 'accent' => '#0F172A', 'bg' => '#F8FAFC', 'text' => '#475569', 'heading' => '#0F172A']],
        ['Ocean', ['theme' => 'dark', 'accent' => '#06B6D4', 'bg' => '#083344', 'text' => '#CFFAFE', 'heading' => '#ECFEFF']],
    ];
    $initial = [
        'theme' => old('portfolio_theme', $user->portfolio_theme ?: 'auto'),
        'font' => old('portfolio_font', $user->portfolio_font ?: 'system'),
        'accent' => old('portfolio_accent_color', $user->portfolio_accent_color ?: '#4F46E5'),
        'bg' => old('portfolio_background_color', $user->portfolio_background_color ?: ''),
        'text' => old('portfolio_text_color', $user->portfolio_text_color ?: ''),
        'heading' => old('portfolio_heading_color', $user->portfolio_heading_color ?: ''),
        'mode' => old('portfolio_display_mode', $user->portfolio_display_mode ?: 'grid'),
        'columns' => (int) old('masonry_columns', $user->masonry_columns ?: 4),
        'perPage' => (int) old('photos_per_page', $user->photos_per_page ?: 20),
        'copy' => (bool) old('copy_protection', $user->copy_protection ?? true),
    ];
    $canWatermark = $user->hasFeature('watermarking') || $user->isFeatureOverrideActive();
@endphp

<x-app-layout title="Customize portfolio">
    @push('styles')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,600,700|roboto:400,700|open-sans:400,700|lato:400,700|montserrat:400,600,700|playfair-display:400,700|merriweather:400,700&display=swap" rel="stylesheet">
    @endpush

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8"
         x-data="portfolioDesigner(@js($initial), @js(collect($fonts)->map(fn ($f) => $f[1])))"
         x-on:beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
        <x-ui.page-header title="Customize portfolio" description="Choose how your public portfolio looks. The preview updates as you go; save to publish the changes.">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="arrow-top-right-on-square" :href="route('profile.view', $user->username)" target="_blank" rel="noopener">Open portfolio</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
            {{-- Settings --}}
            <div class="space-y-6">
                <form method="POST" action="{{ route('profile.customize.update') }}" id="design-form" class="space-y-6" x-on:submit="dirty = false">
                    @csrf
                    @method('PATCH')

                    <x-ui.card title="Start from a style" description="Fills in the colours below. You can adjust them afterwards.">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach($presets as [$label, $p])
                                <button type="button" x-on:click="applyPreset(@js($p))"
                                        class="group rounded-control p-2 text-left ring-1 ring-gray-200 transition hover:ring-brand-400 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:ring-gray-700">
                                    <span class="flex h-12 items-end gap-1 rounded-md p-2" style="background: {{ $p['bg'] }}">
                                        <span class="h-2 w-8 rounded-full" style="background: {{ $p['heading'] }}"></span>
                                        <span class="ml-auto size-4 rounded-full" style="background: {{ $p['accent'] }}"></span>
                                    </span>
                                    <span class="mt-1.5 block text-sm font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Colours and type">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.select name="portfolio_theme" label="Light or dark" x-model="s.theme"
                                         :options="['auto' => 'Match the visitor’s device', 'light' => 'Always light', 'dark' => 'Always dark']" :selected="$initial['theme']" />
                            <x-ui.select name="portfolio_font" label="Font" x-model="s.font"
                                         :options="collect($fonts)->map(fn ($f) => $f[0])->all()" :selected="$initial['font']" />

                            @foreach([
                                'accent' => ['portfolio_accent_color', 'Accent colour', 'Buttons and links.', false],
                                'bg' => ['portfolio_background_color', 'Background', 'Leave unset to use the light/dark default.', true],
                                'text' => ['portfolio_text_color', 'Text', 'Leave unset to use the default.', true],
                                'heading' => ['portfolio_heading_color', 'Headings', 'Leave unset to use the default.', true],
                            ] as $key => [$field, $label, $help, $optional])
                                <div class="space-y-1">
                                    <span id="{{ $field }}-label" class="block text-sm font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                    <div class="flex items-center gap-2">
                                        <input type="color" aria-labelledby="{{ $field }}-label"
                                               x-bind:value="s.{{ $key }} || defaults.{{ $key }}" x-on:input="s.{{ $key }} = $event.target.value.toUpperCase()"
                                               class="h-10 w-14 cursor-pointer rounded-control border border-gray-300 bg-white p-1 dark:border-gray-600 dark:bg-gray-900">
                                        <input type="hidden" name="{{ $field }}" x-bind:value="s.{{ $key }}">
                                        <code class="text-sm text-gray-700 dark:text-gray-300" x-text="s.{{ $key }} || 'Default'"></code>
                                        @if($optional)
                                            <button type="button" x-show="s.{{ $key }}" x-on:click="s.{{ $key }} = ''"
                                                    class="ml-auto rounded-sm text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:text-white">Use default</button>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $help }}</p>
                                    @error($field)<p class="text-sm text-red-700 dark:text-red-400">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                        <p x-show="contrastWarning" x-cloak class="mt-4 flex items-start gap-2 text-sm text-amber-800 dark:text-amber-300" role="status">
                            <x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4 shrink-0" />
                            <span>Your text colour is hard to read on this background. Visitors may struggle to read your bio and captions.</span>
                        </p>
                    </x-ui.card>

                    <x-ui.card title="Layout">
                        <fieldset class="space-y-3">
                            <legend class="text-sm font-medium text-gray-800 dark:text-gray-200">Your portfolio's home page shows</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach(['grid' => ['All photos', 'A gallery of photos from your public collections.', 'squares-2x2'], 'collections' => ['Collections', 'Cover cards for each public collection.', 'rectangle-stack']] as $value => [$title, $desc, $icon])
                                    <label class="flex cursor-pointer gap-3 rounded-control p-3 ring-1 transition"
                                           :class="s.mode === @js($value) ? 'bg-brand-50 ring-2 ring-brand-500 dark:bg-brand-950' : 'ring-gray-200 hover:ring-gray-300 dark:ring-gray-700'">
                                        <input type="radio" name="portfolio_display_mode" value="{{ $value }}" x-model="s.mode" class="sr-only">
                                        <x-ui.icon :name="$icon" class="size-6 text-brand-600 dark:text-brand-300" />
                                        <span>
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</span>
                                            <span class="block text-sm text-gray-600 dark:text-gray-400">{{ $desc }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2" x-show="s.mode === 'grid'">
                            <x-ui.select name="masonry_columns" label="Columns on large screens" x-model.number="s.columns" help="Phones always show fewer columns."
                                         :options="[2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6']" :selected="$initial['columns']" />
                            <x-ui.select name="photos_per_page" label="Photos loaded at a time" x-model.number="s.perPage" help="Fewer loads faster on slow connections."
                                         :options="[10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50']" :selected="$initial['perPage']" />
                        </div>
                        <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-700">
                            <x-ui.checkbox name="copy_protection" label="Discourage saving photos" :checked="$initial['copy']" x-model="s.copy"
                                           description="Turns off right-click, dragging and the save shortcut on your photos. It can't stop screenshots; use watermarks for real protection." />
                        </div>
                    </x-ui.card>

                    <div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-card sm:border sm:px-5 dark:border-gray-800 dark:bg-gray-950/95">
                        <p class="text-sm" role="status" aria-live="polite">
                            <span x-show="dirty" x-cloak class="font-medium text-amber-700 dark:text-amber-400">You have unsaved changes</span>
                            <span x-show="!dirty" class="text-gray-600 dark:text-gray-400">All changes saved</span>
                        </p>
                        <div class="flex gap-2">
                            <x-ui.button variant="ghost" x-on:click="resetToDefaults()">Reset to defaults</x-ui.button>
                            <x-ui.button type="submit">Save design</x-ui.button>
                        </div>
                    </div>
                </form>

                <x-ui.card title="Logo" description="Shown at the top of your portfolio and galleries, and as the browser tab icon.">
                    @livewire('logo-uploader', ['user' => $user])
                </x-ui.card>

                <x-ui.card title="Watermark" description="Text added to watermarked collections and photos.">
                    @if($canWatermark)
                        <form method="POST" action="{{ route('profile.watermark.update') }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            @method('PATCH')
                            <x-ui.input name="watermark_text" label="Watermark text" :value="old('watermark_text', $user->watermark_text)" maxlength="50"
                                        :placeholder="'© '.($user->name ?: $user->username)" help="Up to 50 characters. Leave blank to use your name." wrapper-class="min-w-64 flex-1" />
                            <x-ui.button type="submit" variant="secondary">Save watermark</x-ui.button>
                        </form>
                    @else
                        <x-ui.alert title="Watermarking isn't included in your plan">
                            Upgrade to protect your photos with your own watermark.
                            <x-slot:actions><x-ui.button size="sm" :href="route('pricing')">Compare plans</x-ui.button></x-slot:actions>
                        </x-ui.alert>
                    @endif
                </x-ui.card>
            </div>

            {{-- Live preview --}}
            <aside class="lg:sticky lg:top-6" aria-label="Preview">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Preview</h2>
                    <div class="flex rounded-control bg-gray-100 p-0.5 text-xs font-medium dark:bg-gray-800" role="radiogroup" aria-label="Preview as" x-show="s.theme === 'auto'">
                        <button type="button" role="radio" :aria-checked="!previewDark" x-on:click="previewDark = false" :class="!previewDark ? 'bg-white shadow-xs dark:bg-gray-700' : ''" class="rounded-[calc(var(--radius-control)-2px)] px-2 py-1">Light</button>
                        <button type="button" role="radio" :aria-checked="previewDark" x-on:click="previewDark = true" :class="previewDark ? 'bg-white shadow-xs dark:bg-gray-700' : ''" class="rounded-[calc(var(--radius-control)-2px)] px-2 py-1">Dark</button>
                    </div>
                </div>
                <div class="overflow-hidden rounded-card shadow-sm ring-1 ring-gray-200 dark:ring-gray-700" :style="`background:${bg}; color:${text}; font-family:${fontFamily}`">
                    <div class="flex items-center gap-1.5 border-b border-black/5 bg-black/5 px-3 py-2" aria-hidden="true">
                        <span class="size-2.5 rounded-full bg-red-400"></span><span class="size-2.5 rounded-full bg-amber-400"></span><span class="size-2.5 rounded-full bg-green-400"></span>
                        <span class="ml-2 truncate text-[11px] opacity-60">{{ preg_replace('#^https?://#', '', route('profile.view', $user->username)) }}</span>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="flex items-center gap-3">
                            @if($user->logoUrl())
                                <img src="{{ $user->logoUrl() }}" alt="" class="size-12 rounded-md object-contain">
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-xl font-bold" :style="`color:${heading}`">{{ $user->name ?: $user->username }}</p>
                                <p class="text-xs opacity-70">{{ '@'.$user->username }}</p>
                            </div>
                        </div>
                        @if($user->bio)
                            <div class="line-clamp-3 text-sm [&_p]:m-0">{!! $user->bioHtml() !!}</div>
                        @endif
                        <span class="inline-block rounded-control px-3 py-1.5 text-sm font-semibold text-white" :style="`background:${accentColour}`">View collections</span>

                        <template x-if="s.mode === 'grid'">
                            <div class="grid gap-1.5" :style="`grid-template-columns: repeat(${Math.min(s.columns, 4)}, minmax(0, 1fr))`">
                                @forelse($previewPhotos as $photo)
                                    <img src="{{ $photo->getAwsThumbnail() }}" alt="" class="aspect-square w-full rounded-sm object-cover">
                                @empty
                                    @for($i = 0; $i < 8; $i++)<span class="aspect-square rounded-sm bg-current opacity-10"></span>@endfor
                                @endforelse
                            </div>
                        </template>
                        <template x-if="s.mode === 'collections'">
                            <div class="grid gap-3">
                                @forelse($previewCollections as $c)
                                    <div class="overflow-hidden rounded-md ring-1 ring-black/10">
                                        @if($c->coverPhoto)<img src="{{ $c->coverPhoto->getAwsThumbnail() }}" alt="" class="h-20 w-full object-cover">@else<span class="block h-20 bg-current opacity-10"></span>@endif
                                        <p class="px-2 py-1.5 text-sm font-semibold" :style="`color:${heading}`">{{ $c->name }}</p>
                                    </div>
                                @empty
                                    @for($i = 0; $i < 2; $i++)<span class="block h-24 rounded-md bg-current opacity-10"></span>@endfor
                                @endforelse
                            </div>
                        </template>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">A simplified preview. Open your portfolio to see the full page after saving.</p>
            </aside>
        </div>
    </div>

    @push('scripts')
    <script>
        function portfolioDesigner(initial, fontFamilies) {
            const defaults = { accent: '#4F46E5', bg: '', text: '', heading: '' };
            const themeDefaults = {
                light: { bg: '#FFFFFF', text: '#374151', heading: '#111827' },
                dark: { bg: '#111827', text: '#E5E7EB', heading: '#F9FAFB' },
            };
            const luminance = (hex) => {
                const c = hex.replace('#', '').match(/.{2}/g).map(v => {
                    const x = parseInt(v, 16) / 255;
                    return x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4;
                });
                return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
            };
            return {
                s: { ...initial },
                saved: JSON.stringify(initial),
                previewDark: window.matchMedia('(prefers-color-scheme: dark)').matches,
                defaults: { accent: '#4F46E5', bg: '#FFFFFF', text: '#374151', heading: '#111827' },
                get dirty() { return JSON.stringify(this.s) !== this.saved; },
                set dirty(v) { if (!v) this.saved = JSON.stringify(this.s); },
                get mode() { return this.s.theme === 'auto' ? (this.previewDark ? 'dark' : 'light') : this.s.theme; },
                get bg() { return this.s.bg || themeDefaults[this.mode].bg; },
                get text() { return this.s.text || themeDefaults[this.mode].text; },
                get heading() { return this.s.heading || themeDefaults[this.mode].heading; },
                get accentColour() { return this.s.accent || defaults.accent; },
                get fontFamily() { return fontFamilies[this.s.font] || fontFamilies.system; },
                get contrastWarning() {
                    const [a, b] = [luminance(this.bg), luminance(this.text)];
                    return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05) < 4.5;
                },
                applyPreset(p) {
                    Object.assign(this.s, { theme: p.theme, accent: p.accent, bg: p.bg, text: p.text, heading: p.heading });
                },
                resetToDefaults() {
                    Object.assign(this.s, { theme: 'auto', font: 'system', accent: defaults.accent, bg: '', text: '', heading: '', mode: 'grid', columns: 4, perPage: 20, copy: true });
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
