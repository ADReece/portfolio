<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Customize Portfolio CTA Banner -->
            @if($user->hasFeature('upload_logo') || $user->isFeatureOverrideActive())
                <!-- Subscriber: Customize Portfolio CTA -->
                <div class="p-6 bg-linear-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 shadow-lg sm:rounded-lg border-2 border-indigo-200 dark:border-indigo-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                🎨 Customize Your Portfolio
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Personalize fonts, colors, themes, logo branding, and watermarks for your public portfolio.
                            </p>
                        </div>
                        <a href="{{ route('profile.customize') }}" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors duration-200 whitespace-nowrap">
                            Customize →
                        </a>
                    </div>
                </div>
            @else
                <!-- Free plan: Upgrade CTA -->
                <div class="p-6 bg-linear-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 shadow-lg sm:rounded-lg border-2 border-amber-200 dark:border-amber-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                                ✨ Unlock Portfolio Customization
                                <span class="px-2 py-0.5 text-xs rounded-sm bg-amber-100 text-amber-700 dark:bg-amber-800 dark:text-amber-200">Free Plan</span>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Upgrade to customize fonts, colors, themes, add your logo branding, watermark images, and more.
                            </p>
                        </div>
                        <a href="{{ route('pricing') }}" class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg transition-colors duration-200 whitespace-nowrap">
                            View Plans →
                        </a>
                    </div>
                </div>
            @endif

            <!-- Profile Settings Section -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 px-4">
                    📸 Profile Settings
                </h3>
                <div class="space-y-6">
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Settings Section -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 px-4">
                    🔐 Account & Security
                </h3>
                <div class="space-y-6">
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
