@php($credits = collect(app(\App\Support\MarketingImages::class)->all())->sortKeys())
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo credits - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-950">
    @include('partials.marketing-nav')

    <main id="main" class="mx-auto max-w-4xl px-4 pt-28 pb-12 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-white p-8 shadow-lg dark:bg-gray-900">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white">Photo credits</h1>
            <p class="mt-4 text-gray-700 dark:text-gray-300">
                The photos on our homepage and sample portfolios are from <a href="https://unsplash.com" class="font-medium text-brand-700 underline underline-offset-4 dark:text-brand-300">Unsplash</a>,
                used under the <a href="https://unsplash.com/license" class="font-medium text-brand-700 underline underline-offset-4 dark:text-brand-300">Unsplash License</a>. Thank you to the photographers below.
            </p>
            @if($credits->isNotEmpty())
                <ul role="list" class="mt-6 divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($credits->groupBy('source_url') as $url => $group)
                        @php($c = $group->first())
                        <li class="py-3 text-sm text-gray-700 dark:text-gray-300">
                            <a href="{{ $url }}" class="font-medium text-gray-900 underline underline-offset-4 dark:text-white">{{ $c['alt'] }}</a>
                            by <a href="{{ $c['photographer_url'] }}" class="underline underline-offset-4">{{ $c['photographer'] }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </main>
</body>
</html>
