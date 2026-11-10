{{-- Top navigation for public marketing pages (home, about, legal). --}}
<header x-data="{ open: false }" x-on:keydown.escape.window="open = false"
        class="fixed inset-x-0 top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        @include('layouts.partials.brand')

        <nav aria-label="Main" class="hidden items-center gap-1 md:flex">
            <x-ui.button variant="ghost" :href="route('pricing')">Pricing</x-ui.button>
            <x-ui.button variant="ghost" :href="route('about')">About</x-ui.button>
            @auth
                <x-ui.button :href="route('dashboard')">Go to dashboard</x-ui.button>
            @else
                <x-ui.button variant="ghost" :href="route('login')">Log in</x-ui.button>
                <x-ui.button :href="route('register')">Get started free</x-ui.button>
            @endauth
        </nav>

        <button type="button" class="rounded-control p-2 text-gray-600 hover:bg-gray-100 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 md:hidden dark:text-gray-300 dark:hover:bg-gray-800"
                x-on:click="open = !open" :aria-expanded="open" aria-controls="marketing-menu">
            <x-ui.icon name="bars-3" class="size-6" x-show="!open" label="Open menu" />
            <x-ui.icon name="x-mark" class="size-6" x-show="open" x-cloak label="Close menu" />
        </button>
    </div>

    <nav id="marketing-menu" aria-label="Main" x-show="open" x-cloak x-collapse class="border-t border-gray-200 md:hidden dark:border-gray-800">
        <div class="space-y-1 px-4 py-3">
            <a href="{{ route('pricing') }}" class="block rounded-control px-3 py-2 font-medium text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Pricing</a>
            <a href="{{ route('about') }}" class="block rounded-control px-3 py-2 font-medium text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">About</a>
            @auth
                <x-ui.button :href="route('dashboard')" class="mt-2 w-full">Go to dashboard</x-ui.button>
            @else
                <a href="{{ route('login') }}" class="block rounded-control px-3 py-2 font-medium text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">Log in</a>
                <x-ui.button :href="route('register')" class="mt-2 w-full">Get started free</x-ui.button>
            @endauth
        </div>
    </nav>
</header>
