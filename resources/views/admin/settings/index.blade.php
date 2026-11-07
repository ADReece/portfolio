<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('System Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Currency Settings -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Currency Settings</h3>
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="max-w-sm">
                            <label for="default_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Currency
                            </label>
                            <select id="default_currency" name="settings[default_currency]" aria-describedby="default_currency_help" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                @foreach(\App\Support\Currency::SYMBOLS as $code => $symbol)
                                    <option value="{{ $code }}" @selected(\App\Support\Currency::code() === $code)>{{ $code }} ({{ $symbol }})</option>
                                @endforeach
                            </select>
                            <p id="default_currency_help" class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used for plan prices, print sales and payouts. The symbol is set automatically.</p>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Save Currency Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscription Plans Management -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Subscription Plans Management</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Stripe Product & Price IDs are now managed directly within each plan. Use the button below to manage all subscription plans and their Stripe integration details.</p>
                    <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Manage Subscription Plans
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
