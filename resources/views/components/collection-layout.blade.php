<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.share-meta', ['title' => $title, 'description' => $description, 'image' => $image, 'noindex' => $noindex])

    <!-- Fonts -->
    @include('partials.portfolio-fonts', ['user' => $user ?? null])

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @php($favicon = asset('favicon.ico'))
    @if(isset($user) && ($user->logo_path || $user->logo_thumb_path))
        @php($favicon = $user->logoUrl() ?? $favicon)
    @endif
    <link rel="icon" type="image/png" href="{{ $favicon }}" />
    @include('partials.portfolio-styles', ['user' => $user ?? null])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
<div class="min-h-screen">
    <!-- Page Heading -->
    @if (isset($header))
        <header class="bg-white dark:bg-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <!-- Page Content -->
    <main class="portfolio-content">
        {{ $slot }}
    </main>
</div>
<x-modals />
@livewireScriptConfig
@include('partials.portfolio-debug', ['user' => $user ?? null])
</body>
</html>

