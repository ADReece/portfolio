<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($user) ? ($user->name ?? $user->username) : config('app.name') }} | {{ config('app.name') }} </title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=nunito:400,600,700|inter:400,500,600,700|roboto:400,500,700|open-sans:400,600,700|lato:400,700|montserrat:400,500,600,700|playfair-display:400,700|merriweather:400,700" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles

        @php($favicon = asset('favicon.ico'))
        @if(isset($user) && ($user->logo_path || $user->logo_thumb_path))
            @php($favicon = $user->logoUrl() ?? $favicon)
        @endif
        <link rel="icon" type="image/png" href="{{ $favicon }}" />
        @include('partials.portfolio-styles', ['user' => $user ?? null])
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 overflow-x-hidden">
        @if(Auth::check())
            @include('layouts.navigation')
        @endif
        <div class="bg-gray-100 dark:bg-gray-900">
            <!-- Page Heading -->
            <header class="portfolio-header md:fixed md:top-0 md:left-0 md:w-80 md:min-h-screen bg-white dark:bg-gray-800 shadow-lg z-10">
                @if (isset($header))
                <div class="max-w-7xl md:mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
                @endif
            </header>

            <!-- Page Content -->
            <main class="portfolio-content md:ml-80 bg-gray-100 dark:bg-gray-900 overflow-x-hidden" @if(Auth::check()) style="min-height: calc(100vh - 4rem);" @endif>
                <x-flash />
                {{ $slot }}
            </main>
        </div>

        <!-- Global Modals -->
        <x-modals />

        @livewireScripts
        @include('partials.portfolio-debug', ['user' => $user ?? null])
    </body>
</html>
