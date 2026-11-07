<a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="flex items-center gap-2 rounded-control focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500">
    <x-application-logo class="h-8 w-auto text-brand-600 dark:text-brand-400" aria-hidden="true" />
    <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">{{ config('app.name') }}</span>
</a>
