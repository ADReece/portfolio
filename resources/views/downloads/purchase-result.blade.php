@php
    $photographer = $order->user;
    $collection = $order->collection;
    $galleryUrl = $collection ? route('profile.collection', ['username' => $photographer->username, 'collection_id' => $collection->id]) : route('profile.view', $photographer->username);
@endphp
<x-collection-layout :user="$photographer" :title="$cancelled ? 'Payment cancelled' : 'Thank you'" :noindex="true">
    <div class="mx-auto max-w-xl px-4 py-16 text-center">
        @if($cancelled)
            <h1 class="text-2xl font-bold">Payment cancelled</h1>
            <p class="mt-3 opacity-80">You haven't been charged. Your photos are still waiting if you change your mind.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @if($collection)
                    <a href="{{ route('download-purchase.show', $collection) }}" class="portfolio-button rounded-control px-5 py-3 font-semibold text-white" style="background: var(--portfolio-accent, #4f46e5)">Choose photos again</a>
                @endif
                <a href="{{ $galleryUrl }}" class="rounded-control px-5 py-3 font-semibold underline underline-offset-4">Back to gallery</a>
            </div>
        @else
            <h1 class="text-2xl font-bold">Thank you for your order</h1>
            @if($downloadUrl)
                <p class="mt-3 opacity-80">Your photos are ready. We've also emailed the link to {{ $order->customer_email }}.</p>
                <a href="{{ $downloadUrl }}" class="portfolio-button mt-8 inline-block rounded-control px-5 py-3 font-semibold text-white" style="background: var(--portfolio-accent, #4f46e5)">Download your photos</a>
            @elseif($paid)
                <p class="mt-3 opacity-80">Your payment went through. We've emailed your download link to {{ $order->customer_email }}.</p>
            @else
                <p class="mt-3 opacity-80" role="status">We're confirming your payment. This page will update in a moment, and we'll email your download link to {{ $order->customer_email }}.</p>
                <div x-data x-init="setTimeout(() => location.reload(), 4000)"></div>
            @endif
            <p class="mt-8"><a href="{{ $galleryUrl }}" class="underline underline-offset-4">Back to gallery</a></p>
        @endif
    </div>
</x-collection-layout>
