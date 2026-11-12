<x-profile-layout :user="$user">
    <x-slot name="header">
        <div class="mx-auto text-center lg:mt-32">
            <h1 class="text-3xl font-bold">Photo credits</h1>
            <p class="mt-2 opacity-80">The photos in {{ $user->name ?: '@'.$user->username }} are free stock photos, used under their licences.</p>
        </div>
    </x-slot>
    <div class="mx-auto max-w-5xl px-4 py-8">
        <ul role="list" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($photos as $photo)
                <li class="text-sm">
                    <img src="{{ $photo->getAwsThumbnail() }}" alt="" loading="lazy" class="aspect-square w-full rounded-md bg-black/5 object-cover">
                    <p class="mt-1">
                        Photo by <a href="{{ $photo->credit['profile_url'] ?? '#' }}" class="underline underline-offset-4" rel="noopener">{{ $photo->credit['name'] ?? 'Unknown' }}</a>
                        on <a href="{{ $photo->credit['source_url'] ?? '#' }}" class="underline underline-offset-4" rel="noopener">Unsplash</a>
                    </p>
                </li>
            @endforeach
        </ul>
    </div>
</x-profile-layout>
