<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ isset($user) ? ($user->name ?? $user->username).' | '.config('app.name') : config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700|inter:400,500,600,700|roboto:400,500,700|open-sans:400,600,700|lato:400,700|montserrat:400,500,600,700|playfair-display:400,700|merriweather:400,700" rel="stylesheet">

    <!-- App Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $favicon = asset('favicon.ico');
        if(isset($user) && ($user->logo_path || $user->logo_thumb_path)) {
            $logoUrl = $user->logoUrl();
            if($logoUrl) { $favicon = $logoUrl; }
        }
    @endphp
    <link rel="icon" type="image/png" href="{{ $favicon }}" />
    @include('partials.portfolio-styles', ['user' => $user ?? null])
</head>
<body class="font-sans antialiased">
    @php($canPortfolio = isset($user) && method_exists($user,'canUseCustomizations') && $user->canUseCustomizations())
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 {{ $canPortfolio ? '' : 'bg-gray-100 dark:bg-gray-900' }}">
        <div>
            <a href="/">
                @if(isset($user) && ($user->logo_path || $user->logo_thumb_path) && isset($logoUrl))
                    <img src="{{ $logoUrl }}" alt="Logo" class="w-20 h-20 object-contain" />
                @else
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                @endif
            </a>
        </div>
        <main class="portfolio-content w-full sm:max-w-md mt-6 px-6 py-4 {{ $canPortfolio ? '' : 'bg-white dark:bg-gray-800' }} shadow-md overflow-hidden sm:rounded-lg guest-card">
            {{ $slot }}
        </main>
    </div>

    @include('partials.portfolio-debug', ['user' => $user ?? null])
    @livewireScriptConfig
</body>
</html>
