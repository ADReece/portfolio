<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        @include('layouts.partials.theme-script')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
    </head>
    <body class="h-full bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-control focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-brand-700 focus:shadow-lg">
            Skip to content
        </a>

        @if(session()->has('impersonate_admin_id'))
            <div class="relative z-40 flex flex-wrap items-center justify-center gap-3 bg-amber-400 px-4 py-2 text-sm font-medium text-amber-950">
                <x-ui.icon name="eye" class="size-4" />
                You're viewing the app as {{ Auth::user()->name }}.
                <form action="{{ route('admin.users.stop-impersonating') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-control bg-amber-950 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-amber-950 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-400">
                        Stop impersonating
                    </button>
                </form>
            </div>
        @endif

        @auth
            <div x-data="{ drawer: false }" x-on:keydown.escape.window="drawer = false" class="min-h-screen lg:pl-64">
                {{-- Desktop sidebar --}}
                <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col gap-6 overflow-y-auto border-r border-gray-200 bg-white px-3 py-5 lg:flex dark:border-gray-800 dark:bg-gray-900"
                       @if(session()->has('impersonate_admin_id')) style="top: 2.5rem" @endif>
                    <div class="px-3">@include('layouts.partials.brand')</div>
                    @include('layouts.partials.sidebar-nav')
                    @include('layouts.partials.user-menu')
                </aside>

                {{-- Mobile top bar --}}
                <div class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur lg:hidden dark:border-gray-800 dark:bg-gray-900/90">
                    @include('layouts.partials.brand')
                    <button type="button" x-on:click="drawer = true" :aria-expanded="drawer" aria-controls="mobile-drawer"
                            class="rounded-control p-2 text-gray-600 hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-300 dark:hover:bg-gray-800">
                        <x-ui.icon name="bars-3" class="size-6" label="Open menu" />
                    </button>
                </div>

                {{-- Mobile drawer --}}
                <div x-show="drawer" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu" id="mobile-drawer">
                    <div x-show="drawer" x-transition.opacity class="fixed inset-0 bg-gray-950/50" x-on:click="drawer = false" aria-hidden="true"></div>
                    <div x-show="drawer" x-trap.noscroll="drawer"
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                         class="fixed inset-y-0 left-0 flex w-full max-w-xs flex-col gap-6 overflow-y-auto bg-white px-3 py-5 shadow-xl dark:bg-gray-900">
                        <div class="flex items-center justify-between px-3">
                            @include('layouts.partials.brand')
                            <button type="button" x-on:click="drawer = false" class="rounded-control p-2 text-gray-500 hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:hover:bg-gray-800">
                                <x-ui.icon name="x-mark" class="size-6" label="Close menu" />
                            </button>
                        </div>
                        @include('layouts.partials.sidebar-nav')
                        @include('layouts.partials.user-menu')
                    </div>
                </div>

                <main id="main" tabindex="-1" class="focus:outline-hidden">
                    @isset($header)
                        <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-gray-900 dark:[&_h2]:text-gray-100">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <x-flash />

                    {{ $slot }}
                </main>
            </div>
        @else
            {{-- Guests (e.g. on the pricing page) get a simple header instead of the app navigation. --}}
            <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    @include('layouts.partials.brand')
                    <nav aria-label="Account" class="flex items-center gap-2">
                        <x-ui.button variant="ghost" :href="route('login')">Log in</x-ui.button>
                        <x-ui.button :href="route('register')">Create account</x-ui.button>
                    </nav>
                </div>
            </header>
            <main id="main" tabindex="-1" class="focus:outline-hidden">
                @isset($header)
                    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8 [&_h2]:text-2xl [&_h2]:font-bold">{{ $header }}</div>
                @endisset
                <x-flash />
                {{ $slot }}
            </main>
        @endauth

        <x-modals />

        @livewireScriptConfig
        @stack('scripts')
        {{-- Site support chat on the pricing page only, not the photographer's workspace. --}}
        @includeWhen(request()->routeIs('pricing'), 'partials.chatmesh')
    </body>
</html>
