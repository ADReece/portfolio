{{-- Shown on public pages to the photographer who owns them, so they can get back to editing. --}}
@if(auth()->check() && isset($user) && $user && auth()->id() === $user->id)
    <div class="relative z-40 flex flex-wrap items-center justify-between gap-2 bg-gray-900 px-4 py-2 text-sm text-gray-100">
        <span class="inline-flex items-center gap-2"><x-ui.icon name="eye" class="size-4" /> This is how clients see your portfolio.</span>
        <span class="flex items-center gap-4">
            <a href="{{ route('profile.customize') }}" class="font-medium underline-offset-4 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">Customize</a>
            <a href="{{ route('dashboard') }}" class="font-medium underline-offset-4 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-white">Back to dashboard</a>
        </span>
    </div>
@endif
