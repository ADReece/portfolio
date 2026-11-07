    <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Connect Stripe Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8">
                @if(auth()->user()->stripe_connect_enabled)
                    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-6 mb-6">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="text-lg font-semibold text-green-800">Stripe Connected</h3>
                                <p class="text-green-700">Your Stripe account is connected and ready to receive payments!</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="font-semibold text-lg mb-2 dark:text-white">What's Next?</h4>
                        <ul class="list-disc pl-6 space-y-2 text-gray-700 dark:text-gray-300">
                            <li>Create products from your photos</li>
                            <li>Set prices for digital downloads and prints</li>
                            <li>Share your portfolio with potential buyers</li>
                            <li>Get paid directly to your Stripe account</li>
                        </ul>
                    </div>

                    <form action="{{ route('connect.stripe.disconnect') }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect your Stripe account? You will not be able to receive payments.');">
                        @csrf
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                            Disconnect Stripe Account
                        </button>
                    </form>
                @else
                    <div class="text-center mb-8">
                        <svg class="w-20 h-20 mx-auto mb-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <h3 class="text-2xl font-bold mb-2 dark:text-white">Connect Your Stripe Account</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">Start accepting payments from your clients by connecting your Stripe account</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
                        <h4 class="font-semibold text-lg mb-4 dark:text-white">Benefits of Connecting Stripe:</h4>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="dark:text-gray-300">Sell digital downloads and physical prints</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="dark:text-gray-300">Receive payments directly to your bank account</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="dark:text-gray-300">Secure payment processing</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="dark:text-gray-300">Automatic payout handling</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="dark:text-gray-300">{{ rtrim(rtrim(number_format(config('services.platform_fee_percent'), 2), '0'), '.') }}% platform fee (plus Stripe fees)</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> This feature is only available on Photographer and Videographer plans.
                            @if(!auth()->user()->hasFeature('selling'))
                                <a href="{{ route('pricing') }}" class="underline font-semibold">Upgrade your plan</a> to enable selling.
                            @endif
                        </p>
                    </div>

                    @if(auth()->user()->hasFeature('selling'))
                        <a href="https://connect.stripe.com/oauth/authorize?response_type=code&client_id={{ config('services.stripe.connect_client_id') }}&scope=read_write&redirect_uri={{ route('connect.stripe.callback') }}"
                           class="block w-full text-center bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700">
                            Connect with Stripe
                        </a>
                        <p class="text-sm text-gray-600 mt-4 text-center">
                            You'll be redirected to Stripe to complete the connection process
                        </p>
                    @else
                        <a href="{{ route('pricing') }}" class="block w-full text-center bg-gray-400 text-white px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
                            Upgrade Plan to Connect Stripe
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

