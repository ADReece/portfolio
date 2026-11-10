{{-- Signed-in user, theme switch and log out, at the foot of the sidebar. --}}
<div class="space-y-3 border-t border-gray-200 pt-4 dark:border-gray-800">
    <div x-data="themeSwitch" class="flex items-center justify-between gap-2 px-3">
        <span id="theme-label" class="text-xs font-medium text-gray-500 dark:text-gray-400">Theme</span>
        <div role="radiogroup" aria-labelledby="theme-label" class="flex rounded-control bg-gray-100 p-0.5 dark:bg-gray-800">
            @foreach(['system' => ['computer-desktop', 'System'], 'light' => ['sun', 'Light'], 'dark' => ['moon', 'Dark']] as $value => [$icon, $label])
                <button type="button" role="radio" x-on:click="set('{{ $value }}')" :aria-checked="theme === '{{ $value }}'"
                        :class="theme === '{{ $value }}' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="rounded-[calc(var(--radius-control)-2px)] p-1.5 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                        title="{{ $label }}">
                    <x-ui.icon :name="$icon" class="size-4" :label="$label" />
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-3 px-3">
        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-brand-900 dark:text-brand-200" aria-hidden="true">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-control p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100" title="Log out">
                <x-ui.icon name="arrow-right-start-on-rectangle" class="size-5" label="Log out" />
            </button>
        </form>
    </div>
</div>
