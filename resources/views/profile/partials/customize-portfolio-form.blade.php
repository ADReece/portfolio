<form method="post" action="{{ route('profile.customize.update') }}" id="customize-form" class="space-y-8">
    @csrf
    @method('patch')

    <!-- Theme Selection -->
    <div class="border-b pb-6 dark:border-gray-700">
        <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">
            🌓 Theme Settings
        </h3>

        <div>
            <x-input-label for="portfolio_theme" value="{{ __('Portfolio Theme') }}" />
            <select id="portfolio_theme" name="portfolio_theme" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                @php($themes = ['auto' => 'Auto (match visitor system)', 'light' => 'Light', 'dark' => 'Dark'])
                @foreach($themes as $value => $label)
                    <option value="{{ $value }}" {{ old('portfolio_theme', auth()->user()->portfolio_theme ?? 'auto') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Choose whether your portfolio always uses light or dark mode, or automatically matches your visitor\'s system preference.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('portfolio_theme')" />
        </div>
    </div>

    <!-- Typography -->
    <div class="border-b pb-6 dark:border-gray-700">
        <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">
            ✍️ Typography
        </h3>

        <div>
            <x-input-label for="portfolio_font" value="{{ __('Font Family') }}" />
            <select id="portfolio_font" name="portfolio_font" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                @php($fonts = [
                    'system' => 'System Default',
                    'inter' => 'Inter (Modern & Clean)',
                    'nunito' => 'Nunito (Friendly & Rounded)',
                    'roboto' => 'Roboto (Professional)',
                    'open-sans' => 'Open Sans (Classic)',
                    'lato' => 'Lato (Elegant)',
                    'montserrat' => 'Montserrat (Bold & Contemporary)',
                    'playfair' => 'Playfair Display (Elegant Serif)',
                    'merriweather' => 'Merriweather (Readable Serif)',
                ])
                @foreach($fonts as $value => $label)
                    <option value="{{ $value }}" {{ old('portfolio_font', auth()->user()->portfolio_font ?? 'system') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Choose the main font for your portfolio. This affects all text on your public portfolio pages.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('portfolio_font')" />
        </div>
    </div>

    <!-- Color Customization -->
    <div class="border-b pb-6 dark:border-gray-700">
        <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">
            🎨 Color Palette
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Accent Color -->
            <div>
                <x-input-label for="portfolio_accent_color" value="{{ __('Accent Color') }}" />
                <div class="flex items-center gap-3 mt-1">
                    <input type="color" id="portfolio_accent_color" name="portfolio_accent_color"
                           value="{{ old('portfolio_accent_color', auth()->user()->portfolio_accent_color ?? '#6366F1') }}"
                           class="h-12 w-20 rounded-md border border-gray-300 dark:border-gray-600 cursor-pointer">
                    <input type="text" id="portfolio_accent_color_hex"
                           value="{{ old('portfolio_accent_color', auth()->user()->portfolio_accent_color ?? '#6366F1') }}"
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 uppercase"
                           pattern="^#[A-Fa-f0-9]{6}$" maxlength="7" readonly>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Used for buttons, links, and interactive elements.') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('portfolio_accent_color')" />
            </div>

            <!-- Background Color -->
            <div>
                <x-input-label for="portfolio_background_color" value="{{ __('Background Color') }}" />
                <div class="flex items-center gap-3 mt-1">
                    <input type="color" id="portfolio_background_color" name="portfolio_background_color"
                           value="{{ old('portfolio_background_color', auth()->user()->portfolio_background_color ?? '') }}"
                           class="h-12 w-20 rounded-md border border-gray-300 dark:border-gray-600 cursor-pointer">
                    <input type="text" id="portfolio_background_color_hex"
                           value="{{ old('portfolio_background_color', auth()->user()->portfolio_background_color ?? '') }}"
                           placeholder="Use default"
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 uppercase"
                           pattern="^#[A-Fa-f0-9]{6}$" maxlength="7">
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Main background color. Leave empty to use theme default.') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('portfolio_background_color')" />
            </div>

            <!-- Text Color -->
            <div>
                <x-input-label for="portfolio_text_color" value="{{ __('Text Color') }}" />
                <div class="flex items-center gap-3 mt-1">
                    <input type="color" id="portfolio_text_color" name="portfolio_text_color"
                           value="{{ old('portfolio_text_color', auth()->user()->portfolio_text_color ?? '') }}"
                           class="h-12 w-20 rounded-md border border-gray-300 dark:border-gray-600 cursor-pointer">
                    <input type="text" id="portfolio_text_color_hex"
                           value="{{ old('portfolio_text_color', auth()->user()->portfolio_text_color ?? '') }}"
                           placeholder="Use default"
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 uppercase"
                           pattern="^#[A-Fa-f0-9]{6}$" maxlength="7">
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Body text color. Leave empty to use theme default.') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('portfolio_text_color')" />
            </div>

            <!-- Heading Color -->
            <div>
                <x-input-label for="portfolio_heading_color" value="{{ __('Heading Color') }}" />
                <div class="flex items-center gap-3 mt-1">
                    <input type="color" id="portfolio_heading_color" name="portfolio_heading_color"
                           value="{{ old('portfolio_heading_color', auth()->user()->portfolio_heading_color ?? '') }}"
                           class="h-12 w-20 rounded-md border border-gray-300 dark:border-gray-600 cursor-pointer">
                    <input type="text" id="portfolio_heading_color_hex"
                           value="{{ old('portfolio_heading_color', auth()->user()->portfolio_heading_color ?? '') }}"
                           placeholder="Use default"
                           class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 uppercase"
                           pattern="^#[A-Fa-f0-9]{6}$" maxlength="7">
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Color for headings (h1, h2, h3, etc.). Leave empty to use theme default.') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('portfolio_heading_color')" />
            </div>
        </div>

        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-xs text-blue-800 dark:text-blue-200">
                <strong>💡 Pro Tip:</strong> Colors will override your theme defaults. For best results, test your color combinations in both light and dark themes to ensure readability.
            </p>
        </div>
    </div>

    <!-- Layout & Display Settings -->
    <div class="border-b pb-6 dark:border-gray-700">
        <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-4">
            📐 Layout & Grid Settings
        </h3>

        <!-- Display Mode -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('Portfolio Display Mode') }}
            </label>

            <div class="space-y-4">
                <!-- Grid Option -->
                <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ (old('portfolio_display_mode', auth()->user()->portfolio_display_mode ?? 'grid')) === 'grid' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                    <input type="radio" name="portfolio_display_mode" value="grid"
                           {{ (old('portfolio_display_mode', auth()->user()->portfolio_display_mode ?? 'grid')) === 'grid' ? 'checked' : '' }}
                           class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-600">
                    <div class="ml-3">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                            Masonry Grid
                        </span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Display all your public photos in a beautiful masonry grid layout. Great for showcasing your full portfolio at a glance.
                        </span>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="grid grid-cols-3 gap-1 w-24 h-16 bg-gray-200 dark:bg-gray-600 rounded-sm p-1">
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm row-span-2"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Collections Option -->
                <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ (old('portfolio_display_mode', auth()->user()->portfolio_display_mode ?? 'grid')) === 'collections' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                    <input type="radio" name="portfolio_display_mode" value="collections"
                           {{ (old('portfolio_display_mode', auth()->user()->portfolio_display_mode ?? 'grid')) === 'collections' ? 'checked' : '' }}
                           class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-600">
                    <div class="ml-3">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                            Collections View
                        </span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Display your collections as individual galleries with cover photos. Perfect for organizing work by project or event.
                        </span>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="grid grid-cols-2 gap-1 w-24 h-16 bg-gray-200 dark:bg-gray-600 rounded-sm p-1">
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                                <div class="bg-gray-400 dark:bg-gray-400 rounded-sm"></div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('portfolio_display_mode')" />
        </div>

        <!-- Grid Settings -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Masonry Columns -->
            <div>
                <label for="masonry_columns" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Masonry Grid Columns') }}
                </label>
                <select name="masonry_columns" id="masonry_columns"
                        class="w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="2" {{ (old('masonry_columns', auth()->user()->masonry_columns ?? 4)) == 2 ? 'selected' : '' }}>2 Columns (Mobile-like)</option>
                    <option value="3" {{ (old('masonry_columns', auth()->user()->masonry_columns ?? 4)) == 3 ? 'selected' : '' }}>3 Columns</option>
                    <option value="4" {{ (old('masonry_columns', auth()->user()->masonry_columns ?? 4)) == 4 ? 'selected' : '' }}>4 Columns (Default)</option>
                    <option value="5" {{ (old('masonry_columns', auth()->user()->masonry_columns ?? 4)) == 5 ? 'selected' : '' }}>5 Columns</option>
                    <option value="6" {{ (old('masonry_columns', auth()->user()->masonry_columns ?? 4)) == 6 ? 'selected' : '' }}>6 Columns (Dense)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Number of columns in the masonry grid layout. Desktop only - mobile always uses 2.
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('masonry_columns')" />
            </div>

            <!-- Photos Per Page -->
            <div>
                <label for="photos_per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Photos Per Load') }}
                </label>
                <select name="photos_per_page" id="photos_per_page"
                        class="w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    <option value="10" {{ (old('photos_per_page', auth()->user()->photos_per_page ?? 20)) == 10 ? 'selected' : '' }}>10 Photos (Fast)</option>
                    <option value="20" {{ (old('photos_per_page', auth()->user()->photos_per_page ?? 20)) == 20 ? 'selected' : '' }}>20 Photos (Default)</option>
                    <option value="30" {{ (old('photos_per_page', auth()->user()->photos_per_page ?? 20)) == 30 ? 'selected' : '' }}>30 Photos</option>
                    <option value="40" {{ (old('photos_per_page', auth()->user()->photos_per_page ?? 20)) == 40 ? 'selected' : '' }}>40 Photos</option>
                    <option value="50" {{ (old('photos_per_page', auth()->user()->photos_per_page ?? 20)) == 50 ? 'selected' : '' }}>50 Photos (Slower)</option>
                    <option value="100" {{ (old('photos_per_page', auth()->user()->photos_per_page ?? 20)) == 100 ? 'selected' : '' }}>100 Photos (Debug)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Number of photos loaded at once during infinite scroll. Higher = slower initial load but fewer requests.
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('photos_per_page')" />
            </div>
        </div>

        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
            <p class="text-xs text-amber-800 dark:text-amber-200">
                <strong>⚡ Performance Tip:</strong> More columns = more visual density. More photos per load = fewer server requests but slower initial loading. Find your balance!
            </p>
        </div>
    </div>

    <!-- Reset Button -->
    <div class="flex items-center justify-between">
        <button type="button" id="reset-defaults" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline">
            Reset to Defaults
        </button>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
            @if (session('status') === 'portfolio-customized')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Saved!') }}</p>
            @endif
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync color inputs with hex inputs
    function syncColorInputs(colorId, hexId) {
        const colorInput = document.getElementById(colorId);
        const hexInput = document.getElementById(hexId);

        if (colorInput && hexInput) {
            colorInput.addEventListener('input', function() {
                hexInput.value = this.value.toUpperCase();
            });

            hexInput.addEventListener('input', function() {
                if (/^#[A-Fa-f0-9]{6}$/.test(this.value)) {
                    colorInput.value = this.value;
                }
            });
        }
    }

    syncColorInputs('portfolio_accent_color', 'portfolio_accent_color_hex');
    syncColorInputs('portfolio_background_color', 'portfolio_background_color_hex');
    syncColorInputs('portfolio_text_color', 'portfolio_text_color_hex');
    syncColorInputs('portfolio_heading_color', 'portfolio_heading_color_hex');

    // Preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const preset = JSON.parse(this.dataset.preset);

            document.getElementById('portfolio_theme').value = preset.theme;
            document.getElementById('portfolio_accent_color').value = preset.accent;
            document.getElementById('portfolio_accent_color_hex').value = preset.accent;
            document.getElementById('portfolio_background_color').value = preset.bg;
            document.getElementById('portfolio_background_color_hex').value = preset.bg;
            document.getElementById('portfolio_text_color').value = preset.text;
            document.getElementById('portfolio_text_color_hex').value = preset.text;
            document.getElementById('portfolio_heading_color').value = preset.heading;
            document.getElementById('portfolio_heading_color_hex').value = preset.heading;

            // Trigger preview update
            document.getElementById('customize-form').dispatchEvent(new Event('input'));
        });
    });

    // Reset button
    document.getElementById('reset-defaults')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to reset all customizations to default values?')) {
            document.getElementById('portfolio_theme').value = 'auto';
            document.getElementById('portfolio_font').value = 'system';
            document.getElementById('portfolio_accent_color').value = '#6366F1';
            document.getElementById('portfolio_accent_color_hex').value = '#6366F1';
            document.getElementById('portfolio_background_color').value = '';
            document.getElementById('portfolio_background_color_hex').value = '';
            document.getElementById('portfolio_text_color').value = '';
            document.getElementById('portfolio_text_color_hex').value = '';
            document.getElementById('portfolio_heading_color').value = '';
            document.getElementById('portfolio_heading_color_hex').value = '';

            // Trigger preview update
            document.getElementById('customize-form').dispatchEvent(new Event('input'));
        }
    });
});
</script>
@endpush

