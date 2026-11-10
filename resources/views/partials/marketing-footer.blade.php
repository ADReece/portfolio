{{-- Footer for the public marketing pages (home, sample portfolios). --}}
<footer class="bg-stone-950 py-14 text-sm text-stone-400">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 md:grid-cols-4 lg:px-8">
        <div>
            <p class="font-display text-xl text-white">{{ config('app.name') }}</p>
            <p class="mt-3">Portfolio websites and client galleries for photographers and videographers.</p>
        </div>
        <nav aria-label="Product">
            <h2 class="font-semibold text-white">Product</h2>
            <ul role="list" class="mt-3 space-y-2">
                <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                <li><a href="{{ route('examples.show', array_key_first(config('marketing.showcases'))) }}" class="hover:text-white">Sample portfolios</a></li>
                <li><a href="{{ route('about') }}" class="hover:text-white">About</a></li>
            </ul>
        </nav>
        <nav aria-label="Legal">
            <h2 class="font-semibold text-white">Legal</h2>
            <ul role="list" class="mt-3 space-y-2">
                <li><a href="{{ route('terms') }}" class="hover:text-white">Terms of Service</a></li>
                <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                <li><a href="{{ route('sla') }}" class="hover:text-white">SLA</a></li>
                <li><a href="{{ route('photo-credits') }}" class="hover:text-white">Photo credits</a></li>
                @if(\App\Tracking\Tracking::configured())
                    <li><a href="{{ route('privacy') }}#cookies" data-cookie-settings class="hover:text-white">Cookie settings</a></li>
                @endif
            </ul>
        </nav>
        <div>
            <h2 class="font-semibold text-white">Support</h2>
            <ul role="list" class="mt-3 space-y-2">
                <li><a href="mailto:{{ config('app.support_email') }}" class="hover:text-white">Contact support</a></li>
            </ul>
        </div>
    </div>
    <p class="mx-auto mt-10 max-w-7xl border-t border-stone-800 px-4 pt-8 sm:px-6 lg:px-8">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
</footer>
