<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-xs fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-2xl font-bold text-gray-900 dark:text-gray-300">
                        {{ config('app.name') }}
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('pricing') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Pricing</a>
                    <a href="{{ route('about') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">About</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Login</a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Get Started</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="pt-24 pb-16 bg-linear-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 dark:text-white mb-6">
                    Showcase Your Art.<br/>
                    <span class="text-transparent bg-clip-text bg-linear-to-r from-indigo-600 to-purple-600">
                        Build Your Brand.
                    </span>
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
                    Create stunning photography and videography portfolios. Share private galleries with clients. Sell your work. All in one beautiful platform.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-indigo-700 transform transition hover:scale-105">
                        Start Free Trial
                    </a>
                    <a href="{{ route('pricing') }}" class="bg-white text-indigo-600 px-8 py-4 rounded-lg text-lg font-semibold border-2 border-indigo-600 hover:bg-indigo-50 transform transition hover:scale-105">
                        View Pricing
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-16 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Everything You Need to Succeed</h2>
                <p class="text-xl text-gray-600 dark:text-gray-300">Professional tools for photographers and videographers</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 mt-12">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Beautiful Portfolios</h3>
                    <p class="text-gray-600 dark:text-gray-300">Choose from stunning templates designed specifically for photographers and videographers.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Private Galleries</h3>
                    <p class="text-gray-600 dark:text-gray-300">Share password-protected galleries with clients and get instant feedback on your work.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Sell Your Work</h3>
                    <p class="text-gray-600 dark:text-gray-300">Sell digital downloads and physical prints directly to your clients with built-in payment processing.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Custom Watermarks</h3>
                    <p class="text-gray-600 dark:text-gray-300">Protect your work with customizable watermarks on preview images.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Video Support</h3>
                    <p class="text-gray-600 dark:text-gray-300">Upload and showcase video content alongside your photos with our Videographer plan.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 dark:text-white">Fast & Reliable</h3>
                    <p class="text-gray-600 dark:text-gray-300">Lightning-fast loading times and 99.9% uptime to ensure your portfolio is always accessible.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Preview -->
    <div class="py-16 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Simple, Transparent Pricing</h2>
                <p class="text-xl text-gray-600 dark:text-gray-300">Choose the plan that's right for you</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                @foreach($plans as $plan)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 {{ $plan->recommended ? 'ring-2 ring-indigo-600 transform scale-105' : '' }}">
                    @if($plan->recommended)
                    <div class="bg-indigo-600 text-white text-sm font-semibold px-3 py-1 rounded-full inline-block mb-4">
                        RECOMMENDED
                    </div>
                    @endif
                    <h3 class="text-2xl font-bold mb-2 dark:text-white">{{ $plan->name }}</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold dark:text-white">{{ $currencySymbol }}{{ $plan->priceFor('month') }}</span>
                        @if($plan->price > 0)
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                        @endif
                    </div>
                    <ul class="space-y-3 mb-8 min-h-[250px]">
                        @foreach($plan->features as $feature)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 dark:text-green-400 mr-2 mt-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm dark:text-gray-300">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @auth
                        <a href="{{ route('pricing') }}" class="block w-full text-center {{ $plan->recommended ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }} px-6 py-3 rounded-lg font-semibold transition">
                            View Details
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full text-center {{ $plan->recommended ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }} px-6 py-3 rounded-lg font-semibold transition">
                            Get Started
                        </a>
                    @endauth
                </div>
                @endforeach
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('pricing') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold">
                    View full pricing details and annual plans →
                </a>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-16 bg-linear-to-r from-indigo-600 to-purple-600 text-white">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl font-bold mb-4">Ready to Showcase Your Work?</h2>
            <p class="text-xl mb-8 opacity-90">Join thousands of photographers and videographers who trust {{ config('app.name') }}</p>
            <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transform transition hover:scale-105 inline-block">
                Start Your Free Trial
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-white font-semibold mb-4">{{ config('app.name') }}</h4>
                    <p class="text-sm">Professional portfolio platform for photographers and videographers.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                        <li><a href="{{ route('about') }}" class="hover:text-white">About</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('terms') }}" class="hover:text-white">Terms of Service</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="{{ route('sla') }}" class="hover:text-white">SLA</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="mailto:{{ 'support@' . config('app.name') . '.com' }}" class="hover:text-white">{{ 'Contact Support' }}</a></li>
                        <li><a href="#" class="hover:text-white">Help Center</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-sm text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
    @livewireScriptConfig
</body>
</html>

