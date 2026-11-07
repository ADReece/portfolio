<div class="max-w-xl mx-auto px-4 py-14">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 sm:p-8 space-y-6">
        <div class="flex items-start gap-4">
            @if($tone === 'success')
                <span class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            @endif
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $heading }}</h1>
                <p class="text-gray-600 dark:text-gray-300">{{ $body }}</p>
            </div>
        </div>

        @if($item)
            <div class="flex gap-4 items-center border-t border-gray-200 dark:border-gray-700 pt-6">
                @if($photo)
                    <img src="{{ $photo->getAwsThumbnail() }}" alt="" class="h-20 w-20 rounded-md object-cover bg-gray-100 dark:bg-gray-900">
                @endif
                <dl class="text-sm flex-1 space-y-1">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600 dark:text-gray-300">{{ $item->product->name ?? 'Print' }} × {{ $item->quantity }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ $currencySymbol }}{{ number_format((float) $order->total, 2) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Order reference</dt><dd class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($order->id, 8, '')) }}</dd></div>
                </dl>
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            @if($tone !== 'success' && $photo)
                <a href="{{ route('print-purchase.show', $photo) }}" class="portfolio-button inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">Try again</a>
            @endif
            @if($returnUrl)
                <a href="{{ $returnUrl }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500">Back to gallery</a>
            @elseif($photographer?->username)
                <a href="{{ route('profile.view', $photographer->username) }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500">Visit {{ $photographer->name ?? $photographer->username }}'s portfolio</a>
            @endif
        </div>
    </div>
</div>
