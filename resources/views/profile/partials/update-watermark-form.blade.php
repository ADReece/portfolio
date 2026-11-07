<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Watermark Settings') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Customize the watermark that appears on your protected photos.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.watermark.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="watermark_text" value="{{ __('Watermark Text') }}" />
            <x-text-input id="watermark_text" name="watermark_text" type="text" class="mt-1 block w-full" :value="old('watermark_text', $user->watermark_text)" autocomplete="off" />
            <x-input-error :messages="$errors->get('watermark_text')" class="mt-2" />
            <p class="text-sm text-gray-500 mt-2">
                {{ __('Leave blank to use') }} <strong>{{ _('@' . $user->username) }}</strong> {{ __('as your watermark.') }}
            </p>
        </div>

        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-400 dark:text-blue-300 mt-0.5 mr-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Watermark Info</h4>
                    <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                        <li>• Watermarks appear on photos marked as "watermarked"</li>
                        <li>• Text is repeated diagonally across the entire image</li>
                        <li>• Watermarks are semi-transparent but highly visible</li>
                        <li>• Prevents unauthorized use of your work</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Watermark Settings') }}</x-primary-button>

            @if (session('status') === 'watermark-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>

        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
            <p class="text-xs text-amber-800 dark:text-amber-200">
                <strong>⚠️ Note:</strong> Watermark changes only apply to newly uploaded photos. Existing watermarked photos will keep their current watermark.
            </p>
        </div>
    </form>
</section>
