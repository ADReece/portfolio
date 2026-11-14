<x-profile-layout :user="$user">
    <x-slot name="name">
        {{ $user->name }}
    </x-slot>
    <x-slot name="header">
        <div class="lg:mt-32 mx-auto text-center leading-tight">
            @if($user->logo_path)
                <div class="flex justify-center mb-6">
                    <img src="{{ $user->logoUrl() }}" alt="{{ ($user->name ?: $user->username) }} logo" class="h-20 object-contain drop-shadow-sm" />
                </div>
            @endif
            <h1 class="text-3xl font-bold mb-2">
                {{ $user->name ?? $user->username }}
            </h1>
            <h2 class="text-lg font-semibold mb-4 opacity-80">
                 {{ '@'.$user->username }}
            </h2>
            @if($user->bio)
            <div class="mt-2 text-sm leading-relaxed text-center break-words opacity-90 mx-auto [&_a]:underline [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 [&_p+p]:mt-2">
                {!! $user->bioHtml() !!}
            </div>
            @endif
        </div>
    </x-slot>
    <div class="w-full mx-auto sm:px-2 lg:px-4 py-6">
        @if($user->is_demo)
            <div class="px-4">@include('demo.panel')</div>
        @endif
        <x-gallery.downloads class="px-4" scroll-to="" />
        <livewire:masonry-grid :userId="$user->id" />
    </div>
</x-profile-layout>
