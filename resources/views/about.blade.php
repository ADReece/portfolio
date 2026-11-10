<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900">
    @include('partials.marketing-nav')

    <div class="pt-32 pb-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-4">About {{ config('app.name') }}</h1>
                <p class="text-xl text-gray-600 dark:text-gray-300">Empowering photographers and videographers worldwide</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8">
                <h2 class="text-3xl font-bold mb-4 dark:text-white">Our Mission</h2>
                <p class="text-lg text-gray-700 dark:text-gray-300 mb-4">
                    At {{ config('app.name') }}, we believe that every photographer and videographer deserves a professional platform to showcase their work and grow their business. Our mission is to provide beautiful, easy-to-use tools that help creative professionals present their work in the best possible light and connect with clients seamlessly.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8">
                <h2 class="text-3xl font-bold mb-4 dark:text-white">Why We Built This</h2>
                <p class="text-lg text-gray-700 dark:text-gray-300 mb-4">
                    We understand the challenges photographers and videographers face: managing client galleries, protecting your work, and getting paid fairly. Traditional portfolio platforms are either too expensive, too complicated, or don't offer the features you need.
                </p>
                <p class="text-lg text-gray-700">
                    That's why we created {{ config('app.name') }} - a platform that combines stunning portfolio templates, private client galleries, watermarking, and integrated payment processing, all at an affordable price.
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8">
                <h2 class="text-3xl font-bold mb-6 dark:text-white">What Makes Us Different</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">🎨 Built for Creatives</h3>
                        <p class="text-gray-700 dark:text-gray-300">Designed specifically for photographers and videographers, not a generic website builder.</p>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">💰 Fair Pricing</h3>
                        <p class="text-gray-700 dark:text-gray-300">Start free, upgrade when you need more. No hidden fees or surprise charges.</p>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">🔒 Privacy First</h3>
                        <p class="text-gray-700 dark:text-gray-300">Password-protected galleries keep your client's photos secure and private.</p>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold mb-2 dark:text-white">💳 Get Paid Easily</h3>
                        <p class="text-gray-700 dark:text-gray-300">Integrated payment processing means you get paid faster for your work.</p>
                    </div>
                </div>
            </div>

            <div class="bg-linear-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-8 text-white text-center">
                <h2 class="text-3xl font-bold mb-4">Join Thousands of Creative Professionals</h2>
                <p class="text-lg mb-6">Start showcasing your work today with {{ config('app.name') }}</p>
                <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-gray-100 inline-block">
                    Get Started Free
                </a>
            </div>
        </div>
    </div>

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
                        <li><a href="mailto:{{ config('app.support_email') }}" class="hover:text-white">Contact Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-sm text-center">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
    @livewireScriptConfig
    @include('partials.chatmesh')
</body>
</html>
