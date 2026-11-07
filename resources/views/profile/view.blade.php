<x-profile-layout :user="$user">
    <x-slot name="name">
        {{ $user->name }}
    </x-slot>
    <x-slot name="header">
        <div class="lg:mt-32 font-semibold mx-auto text-center text-gray-800 dark:text-gray-200 leading-tight">
            @if($user->logo_path)
                <div class="flex justify-center mb-6">
                    <img src="{{ $user->logoUrl() }}" alt="Logo" class="h-20 object-contain drop-shadow-sm" />
                </div>
            @endif
            <h1 class="text-3xl font-bold mb-2">
                {{ $user->name ?? $user->username }}
            </h1>
            <h2 class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                 {{ _('@'.$user->username) }}
            </h2>
            @if($user->bio)
            <div class="mt-2 text-gray-600 dark:text-gray-400 text-center max-w-2xl prose prose-sm dark:prose-invert mx-auto">
                {!! $user->bio !!}
            </div>
            @endif
        </div>
    </x-slot>
    <div class="w-full mx-auto sm:px-2 lg:px-4 py-6">
        <livewire:masonry-grid :userId="$user->id" />
    </div>
</x-profile-layout>
