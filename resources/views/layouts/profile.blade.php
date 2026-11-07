<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.share-meta', [
            'title' => isset($user) ? ($user->name ?: $user->username).' · Photography' : config('app.name'),
            'description' => isset($user) ? ($user->bio ?: 'Photography portfolio of '.($user->name ?: $user->username)) : null,
            'image' => isset($user) ? $user->logoUrl() : null,
        ])

        <!-- Fonts -->
        @include('partials.portfolio-fonts', ['user' => $user ?? null])

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
        @include('layouts.partials.owner-bar')
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
            <main class="portfolio-content md:ml-80 bg-gray-100 dark:bg-gray-900 overflow-x-hidden" >
                <x-flash />
                {{ $slot }}
            </main>
        </div>

        <!-- Global Modals -->
        <x-modals />

        @livewireScriptConfig
        @include('partials.portfolio-debug', ['user' => $user ?? null])
    </body>
</html>
