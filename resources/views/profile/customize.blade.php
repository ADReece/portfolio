<x-app-layout>
    @push('styles')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700|inter:400,500,600,700|roboto:400,500,700|open-sans:400,600,700|lato:400,700|montserrat:400,500,600,700|playfair-display:400,700|merriweather:400,700" rel="stylesheet">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Customize Portfolio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Info Banner -->
            <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-sm">
                <div class="flex items-start">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Preview your changes:</strong> After saving, visit your public portfolio at
                            <a href="{{ route('profile.view', ['username' => $user->username]) }}" target="_blank" class="font-semibold underline hover:text-blue-600">
                                {{ _('/@' . $user->username) }}
                            </a>
                            to see your customizations applied.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-10">
                    <!-- Branding / Logo Uploader -->
                    <div class="mb-8">
                        @livewire('logo-uploader', ['user' => $user])
                    </div>

                    <!-- Watermark Settings -->
                    <div class="mb-8 border-t border-gray-200 dark:border-gray-700 pt-8">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2 mb-4">
                            <span>Image Watermarking</span>
                            @if(!($user->hasFeature('watermarking') || $user->isFeatureOverrideActive()))
                                <span class="px-2 py-0.5 text-xs rounded-sm bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Locked</span>
                            @endif
                        </h3>

                        @if(!($user->hasFeature('watermarking') || $user->isFeatureOverrideActive()))
                            <p class="text-xs text-gray-500 dark:text-gray-400">Watermark your photos to protect your work. <a href="{{ route('pricing') }}" class="text-indigo-600 dark:text-indigo-400 underline">Upgrade to unlock</a></p>
                        @else
                            <form method="post" action="{{ route('profile.watermark.update') }}" class="space-y-4">
                                @csrf
                                @method('patch')

                                <div>
                                    <x-input-label for="watermark_text" value="Watermark Text" />
                                    <x-text-input id="watermark_text" name="watermark_text" type="text" class="mt-1 block w-full" :value="old('watermark_text', $user->watermark_text)" autocomplete="off" />
                                    <x-input-error :messages="$errors->get('watermark_text')" class="mt-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Leave blank to use <strong>{{ _('@' . $user->username) }}</strong> as your watermark.
                                    </p>
                                </div>

                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-4 w-4 text-blue-400 dark:text-blue-300 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1 text-xs text-blue-700 dark:text-blue-300">
                                            Watermarks appear on photos/collections marked as "watermarked" and are repeated diagonally across images.
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button>Save Watermark</x-primary-button>
                                    @if (session('status') === 'watermark-updated')
                                        <p class="text-xs text-green-600 dark:text-green-400">Saved!</p>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </div>

                    <!-- Preview Section -->
                    <div class="mb-8 p-6 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600" id="preview-area">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                            🎨 Live Preview
                        </h3>
                        <div id="portfolio-preview" class="rounded-lg overflow-hidden" style="min-height: 300px;">
                            <div class="p-8 text-center">
                                <h1 class="text-4xl font-bold mb-2">{{ $user->name ?? $user->username }}</h1>
                                <p class="text-lg mb-4">{{ _('@' . $user->username) }}</p>
                                @if($user->bio)
                                    <div class="text-base mb-6 prose prose-sm dark:prose-invert mx-auto">
                                        {!! $user->bioHtml() !!}
                                    </div>
                                @endif
                                <button class="px-6 py-3 rounded-lg font-semibold">Sample Button</button>
                                <div class="mt-6">
                                    <a href="#" class="underline">Sample Link</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Presets (Collapsible) -->
                    <div class="mb-8" x-data="{ presetsOpen: false }">
                        <button type="button" @click="presetsOpen = !presetsOpen"
                                class="w-full flex items-center justify-between p-4 bg-linear-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg border border-indigo-200 dark:border-indigo-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🎯</span>
                                <div class="text-left">
                                    <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100">
                                        Quick Style Presets
                                    </h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Apply professionally designed color schemes instantly
                                    </p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                                 :class="{ 'rotate-180': presetsOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="presetsOpen"
                             x-collapse
                             class="mt-4 p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-500 transition-all"
                                        data-preset='{"theme":"light","accent":"#6366F1","bg":"#FFFFFF","text":"#1F2937","heading":"#111827"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-white border"></div>
                                    <div class="text-xs font-medium">Classic Light</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-500 transition-all"
                                        data-preset='{"theme":"dark","accent":"#818CF8","bg":"#111827","text":"#F3F4F6","heading":"#F9FAFB"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-gray-900 border border-gray-700"></div>
                                    <div class="text-xs font-medium">Classic Dark</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-rose-500 transition-all"
                                        data-preset='{"theme":"light","accent":"#E11D48","bg":"#FFF1F2","text":"#881337","heading":"#4C0519"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-rose-50 border border-rose-200"></div>
                                    <div class="text-xs font-medium">Rose Garden</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-emerald-500 transition-all"
                                        data-preset='{"theme":"dark","accent":"#10B981","bg":"#064E3B","text":"#D1FAE5","heading":"#ECFDF5"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-emerald-900 border border-emerald-700"></div>
                                    <div class="text-xs font-medium">Forest Night</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-amber-500 transition-all"
                                        data-preset='{"theme":"light","accent":"#F59E0B","bg":"#FFFBEB","text":"#78350F","heading":"#451A03"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-amber-50 border border-amber-200"></div>
                                    <div class="text-xs font-medium">Golden Hour</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-purple-500 transition-all"
                                        data-preset='{"theme":"dark","accent":"#A855F7","bg":"#1E1B4B","text":"#E9D5FF","heading":"#F3E8FF"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-purple-950 border border-purple-800"></div>
                                    <div class="text-xs font-medium">Purple Dream</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-slate-500 transition-all"
                                        data-preset='{"theme":"light","accent":"#0F172A","bg":"#F8FAFC","text":"#475569","heading":"#0F172A"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-slate-50 border border-slate-200"></div>
                                    <div class="text-xs font-medium">Minimal Gray</div>
                                </button>

                                <button type="button" class="preset-btn p-4 rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:border-cyan-500 transition-all"
                                        data-preset='{"theme":"dark","accent":"#06B6D4","bg":"#083344","text":"#CFFAFE","heading":"#ECFEFF"}'>
                                    <div class="w-full h-8 rounded-sm mb-2 bg-cyan-950 border border-cyan-800"></div>
                                    <div class="text-xs font-medium">Ocean Deep</div>
                                </button>
                            </div>
                            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-xs text-blue-800 dark:text-blue-200">
                                    <strong>💡 Tip:</strong> Click a preset to apply it instantly to your portfolio. You can fine-tune the settings below after applying.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Customization Form -->
                    @include('profile.partials.customize-portfolio-form')

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="mr-1">{{ __('Your public URL:') }}</span>
                        <code class="px-2 py-1 rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                            {{ _('/@' . $user->username) }}
                        </code>
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="mr-1">{{ __('Print product controls:') }}</span>
                        <a href="{{ route('profile.prodigi-products.edit') }}" class="underline text-indigo-600 dark:text-indigo-400">
                            Manage client product availability
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Live preview updates
        document.addEventListener('DOMContentLoaded', function() {
            const preview = document.getElementById('portfolio-preview');
            const form = document.getElementById('customize-form');

            function updatePreview() {
                if (!form) return;

                const formData = new FormData(form);
                const font = formData.get('portfolio_font') || 'system';
                const theme = formData.get('portfolio_theme') || 'auto';
                const accentColor = formData.get('portfolio_accent_color') || '#6366F1';
                const backgroundColor = formData.get('portfolio_background_color') || '';
                const textColor = formData.get('portfolio_text_color') || '';
                const headingColor = formData.get('portfolio_heading_color') || '';

                console.log('Updating preview with:', { font, theme, accentColor, backgroundColor, textColor, headingColor });

                // Apply font
                const fontMap = {
                    'system': 'system-ui, -apple-system, sans-serif',
                    'nunito': "'Nunito', sans-serif",
                    'inter': "'Inter', sans-serif",
                    'playfair': "'Playfair Display', serif",
                    'roboto': "'Roboto', sans-serif",
                    'open-sans': "'Open Sans', sans-serif",
                    'merriweather': "'Merriweather', serif",
                    'lato': "'Lato', sans-serif",
                    'montserrat': "'Montserrat', sans-serif",
                };
                preview.style.fontFamily = fontMap[font] || fontMap['system'];

                // Apply theme colors
                if (theme === 'dark') {
                    preview.style.backgroundColor = backgroundColor || '#111827';
                    preview.style.color = textColor || '#F3F4F6';
                } else if (theme === 'light') {
                    preview.style.backgroundColor = backgroundColor || '#FFFFFF';
                    preview.style.color = textColor || '#1F2937';
                } else {
                    // Auto theme - use system preference
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        preview.style.backgroundColor = backgroundColor || '#111827';
                        preview.style.color = textColor || '#F3F4F6';
                    } else {
                        preview.style.backgroundColor = backgroundColor || '#FFFFFF';
                        preview.style.color = textColor || '#1F2937';
                    }
                }

                // Apply accent color to button
                const button = preview.querySelector('button');
                if (button) {
                    button.style.backgroundColor = accentColor;
                    button.style.color = '#FFFFFF';
                }

                // Apply accent color to links
                const links = preview.querySelectorAll('a');
                links.forEach(link => {
                    link.style.color = accentColor;
                });

                // Apply heading color
                const headings = preview.querySelectorAll('h1, h2, h3');
                headings.forEach(heading => {
                    if (headingColor) {
                        heading.style.color = headingColor;
                    } else {
                        heading.style.color = (theme === 'dark' ? '#F9FAFB' : '#111827');
                    }
                });
            }

            // Listen for form changes
            if (form) {
                form.addEventListener('input', updatePreview);
                form.addEventListener('change', updatePreview);

                // Initial preview with delay to ensure form is fully loaded
                setTimeout(updatePreview, 100);
            }
        });
    </script>
    @endpush
</x-app-layout>
