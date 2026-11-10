{{--
    Ascend Digital's own conversion tracking (GA4, Google Ads, Meta Pixel) and
    the cookie consent banner. Renders only on Portfolio's own pages (never on
    photographers' portfolios, galleries or custom domains) and only when an
    ID is configured. Nothing third-party loads until the visitor accepts:
    Consent Mode v2 starts with everything denied, and gtag.js and the Meta
    Pixel are added only after "Accept". The choice is kept for 6 months in
    the first-party "pf_consent" cookie; "Cookie settings" reopens the banner.
--}}
@if(\App\Tracking\Tracking::allowedHere())
    @php($trackingEvents = \App\Tracking\Tracking::pull())
    <div id="cookie-banner" role="region" aria-label="Cookie choices" hidden
         class="fixed inset-x-3 bottom-3 z-[70] mx-auto max-w-2xl rounded-xl bg-white p-5 text-sm text-gray-800 shadow-2xl ring-1 ring-gray-200 sm:inset-x-6 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Cookies</h2>
        <p class="mt-1">
            We'd like to use analytics and advertising cookies (Google Analytics, Google Ads and Meta) to understand how people find {{ config('app.name') }} and to measure our ads.
            They're off unless you accept. Photographers' portfolios never use them. <a href="{{ route('privacy') }}#cookies" class="font-medium underline underline-offset-4">More about cookies</a>
        </p>
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" data-consent="granted" class="min-w-32 flex-1 rounded-md bg-gray-900 px-4 py-2.5 font-semibold text-white hover:bg-gray-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 sm:flex-none dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">Accept</button>
            <button type="button" data-consent="denied" class="min-w-32 flex-1 rounded-md bg-gray-900 px-4 py-2.5 font-semibold text-white hover:bg-gray-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 sm:flex-none dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">Reject</button>
        </div>
    </div>
    <script>
    (function () {
        var cfg = @json(\App\Tracking\Tracking::clientConfig());
        var events = @json($trackingEvents);
        var NAME = 'pf_consent';
        var banner = document.getElementById('cookie-banner');

        function choice() {
            var m = document.cookie.match(/(?:^|;\s*)pf_consent=(granted|denied)/);
            return m ? m[1] : null;
        }
        function remember(value) {
            document.cookie = NAME + '=' + value + '; Max-Age=' + (cfg.consentDays * 86400) + '; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        }

        // Consent Mode v2: everything denied until the visitor accepts.
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied' });

        var loaded = false;
        function load() {
            if (loaded) return;
            loaded = true;
            gtag('consent', 'update', { ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted', analytics_storage: 'granted' });
            if (cfg.ga4 || cfg.ads) {
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga4 || cfg.ads);
                document.head.appendChild(s);
                gtag('js', new Date());
                if (cfg.ga4) gtag('config', cfg.ga4);
                if (cfg.ads) gtag('config', cfg.ads);
            }
            if (cfg.meta) {
                !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s) }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', cfg.meta);
                fbq('track', 'PageView');
            }
            events.forEach(send);
        }

        var META = { sign_up: 'CompleteRegistration', begin_checkout: 'InitiateCheckout', purchase: 'Purchase' };
        function send(ev) {
            // Each conversion is sent once per browser, even on reloads.
            try { if (localStorage.getItem('pf_ev_' + ev.id)) return; localStorage.setItem('pf_ev_' + ev.id, '1'); } catch (e) {}
            var p = ev.params || {};
            if (cfg.ga4 || cfg.ads) {
                gtag('event', ev.name, p);
                var label = ev.name === 'sign_up' ? cfg.adsSignupLabel : (ev.name === 'purchase' ? cfg.adsPurchaseLabel : null);
                if (cfg.ads && label) {
                    gtag('event', 'conversion', { send_to: cfg.ads + '/' + label, value: p.value, currency: p.currency, transaction_id: p.transaction_id || ev.id });
                }
            }
            if (cfg.meta && META[ev.name]) {
                var mp = {};
                if (p.value !== undefined) { mp.value = p.value; mp.currency = p.currency; }
                if (p.plan) mp.content_name = p.plan;
                fbq('track', META[ev.name], mp, { eventID: ev.id });
            }
        }

        function decide(value) {
            var before = choice();
            remember(value);
            banner.hidden = true;
            if (value === 'granted') {
                load();
            } else if (before === 'granted') {
                location.reload(); // stop tags already running on this page
            }
        }

        banner.addEventListener('click', function (e) {
            var b = e.target.closest('[data-consent]');
            if (b) decide(b.getAttribute('data-consent'));
        });
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-cookie-settings]')) {
                e.preventDefault();
                banner.hidden = false;
                banner.querySelector('[data-consent]').focus();
            }
        });

        if (choice() === 'granted') {
            load();
        } else if (choice() === null) {
            banner.hidden = false;
        }
    })();
    </script>
@endif
