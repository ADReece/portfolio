<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Portfolio Display') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Choose how your public portfolio is displayed to visitors.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update-display') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Masonry Grid Settings -->
        <div class="border-b pb-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ __('Grid Settings') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Masonry Columns -->
                <div>
                    <label for="masonry_columns" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Masonry Grid Columns') }}
                    </label>
                    <select name="masonry_columns" id="masonry_columns"
                            class="w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                        <option value="2" {{ ($user->masonry_columns ?? 4) == 2 ? 'selected' : '' }}>2 Columns (Mobile-like)</option>
                        <option value="3" {{ ($user->masonry_columns ?? 4) == 3 ? 'selected' : '' }}>3 Columns</option>
                        <option value="4" {{ ($user->masonry_columns ?? 4) == 4 ? 'selected' : '' }}>4 Columns (Default)</option>
                        <option value="5" {{ ($user->masonry_columns ?? 4) == 5 ? 'selected' : '' }}>5 Columns</option>
                        <option value="6" {{ ($user->masonry_columns ?? 4) == 6 ? 'selected' : '' }}>6 Columns (Dense)</option>
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
                            class="w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                        <option value="10" {{ ($user->photos_per_page ?? 20) == 10 ? 'selected' : '' }}>10 Photos (Fast)</option>
                        <option value="20" {{ ($user->photos_per_page ?? 20) == 20 ? 'selected' : '' }}>20 Photos (Default)</option>
                        <option value="30" {{ ($user->photos_per_page ?? 20) == 30 ? 'selected' : '' }}>30 Photos</option>
                        <option value="40" {{ ($user->photos_per_page ?? 20) == 40 ? 'selected' : '' }}>40 Photos</option>
                        <option value="50" {{ ($user->photos_per_page ?? 20) == 50 ? 'selected' : '' }}>50 Photos (Slower)</option>
                        <option value="100" {{ ($user->photos_per_page ?? 20) == 100 ? 'selected' : '' }}>100 Photos (Debug)</option>
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

        <!-- Display Mode -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('Display Mode') }}
            </label>

            <div class="space-y-4">
                <!-- Grid Option -->
                <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ ($user->portfolio_display_mode ?? 'grid') === 'grid' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                    <input type="radio" name="portfolio_display_mode" value="grid"
                           {{ ($user->portfolio_display_mode ?? 'grid') === 'grid' ? 'checked' : '' }}
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
                <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ ($user->portfolio_display_mode ?? 'grid') === 'collections' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                    <input type="radio" name="portfolio_display_mode" value="collections"
                           {{ ($user->portfolio_display_mode ?? 'grid') === 'collections' ? 'checked' : '' }}
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


        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            @if (session('status') === 'display-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
