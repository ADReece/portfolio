@php
    $photographer = $order->user;
    $photographerName = $photographer->name ?: $photographer->username;
@endphp
<x-collection-layout :user="$photographer" :title="'Your photos · '.($order->collection?->name ?? $photographerName)" :noindex="true">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <h1 class="text-2xl font-bold sm:text-3xl">Your photos{{ $order->collection ? ' from '.$order->collection->name : '' }}</h1>
        <p class="mt-2 opacity-80">
            {{ $photos->count() }} full-resolution {{ \Illuminate\Support\Str::plural('photo', $photos->count()) }} by {{ $photographerName }}.
            This page works until {{ $expires->format('j F Y') }}, so save your photos somewhere safe.
        </p>

        @if($zipUrl)
            <a href="{{ $zipUrl }}" class="portfolio-button mt-6 inline-flex items-center gap-2 rounded-control px-5 py-3 font-semibold text-white" style="background: var(--portfolio-accent, #4f46e5)">
                <x-ui.icon name="arrow-down-tray" class="size-5" /> Download all ({{ $photos->count() }} photos, ZIP)
            </a>
        @endif

        @if($photos->isEmpty())
            <p class="mt-8">These photos are no longer available. Please contact {{ $photographerName }}.</p>
        @else
            <ul role="list" class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($photos as $photo)
                    <li class="portfolio-neutral overflow-hidden rounded-card bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-gray-100">
                        <img src="{{ $photo->getAwsThumbnail() }}" alt="{{ $photo->caption ?: 'Photo '.$loop->iteration }}" loading="lazy" class="aspect-square w-full bg-black/5 object-cover">
                        <a href="{{ $photoUrl($photo) }}" class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-semibold hover:underline">
                            <x-ui.icon name="arrow-down-tray" class="size-4" /> Download<span class="sr-only"> photo {{ $loop->iteration }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-collection-layout>
