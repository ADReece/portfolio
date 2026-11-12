{{-- "Things to try" on the public demo portfolio (users.is_demo). --}}
@php($links = \App\Demo\DemoAccount::tryLinks($user))
<aside aria-labelledby="demo-panel-heading"
       class="portfolio-neutral mx-auto mb-8 max-w-4xl rounded-card border border-amber-200 bg-amber-50 p-5 text-sm text-stone-800 shadow-sm dark:border-amber-800 dark:bg-stone-900 dark:text-stone-100">
    <h2 id="demo-panel-heading" class="text-base font-semibold">This is a live demo portfolio</h2>
    <p class="mt-1 text-stone-700 dark:text-stone-300">Everything here is what your clients would see. Nothing is charged and no emails are sent.</p>
    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
        @if($links['weddings'])
            <li><a href="{{ $links['weddings'] }}" class="font-medium underline underline-offset-4">Buy photos</a> from the weddings: per photo, in bundles, or the whole collection.</li>
        @endif
        @if($links['client'])
            <li><a href="{{ $links['client'] }}" class="font-medium underline underline-offset-4">Open a private client gallery</a> with the password <strong class="font-mono">{{ config('demo.client_gallery_password') }}</strong>, heart your favourites and send them.</li>
        @endif
        <li><strong class="font-medium">Order a print:</strong> hover a photo (or tap it on a phone) and choose the cart.</li>
        @if($links['portraits'])
            <li><a href="{{ $links['portraits'] }}" class="font-medium underline underline-offset-4">Download a portrait as a postcard</a> with your own caption.</li>
        @endif
        @if($links['landscapes'])
            <li><a href="{{ $links['landscapes'] }}" class="font-medium underline underline-offset-4">See watermarked photos</a> in the landscapes.</li>
        @endif
        @if(\App\Models\Video::ready()->where('user_id', $user->id)->exists() && $links['weddings'])
            <li><a href="{{ $links['weddings'] }}#films" class="font-medium underline underline-offset-4">Watch a film</a> in the weddings gallery.</li>
        @endif
        <li><strong class="font-medium">Your own domain:</strong> a portfolio like this can live at <span class="font-mono">{{ config('demo.example_domain') }}</span> (an example, not connected).</li>
    </ul>
    <p class="mt-3 text-xs text-stone-600 dark:text-stone-400">
        Photos are from Unsplash, credited on each photo. <a href="{{ $links['credits'] }}" class="underline underline-offset-4">Photo credits</a>.
        <a href="{{ route('register') }}" class="ml-2 font-semibold underline underline-offset-4">Make your own</a>
    </p>
</aside>
