<?php

namespace App\Http\Middleware;

use App\CustomDomains\DomainRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves photographers' portfolios on their own domains.
 *
 * On a custom domain (e.g. stephshoots.com for @stephshoots):
 *  - "/", "/collections", "/collections/{id}" and "/print-products" show that
 *    portfolio (internally /@stephshoots/...), and URLs generated for it drop
 *    the /@stephshoots prefix and use the custom host;
 *  - the gallery's own endpoints (photos, orders, archives, Livewire, media)
 *    work as normal;
 *  - anything else (sign in, dashboard, pricing) goes to the main site;
 *  - www and apex redirect to whichever is primary.
 * Unknown hosts get a 404, never the marketing site. On the platform host,
 * a portfolio with a live custom domain redirects there (one canonical home).
 */
class ResolveCustomDomain
{
    /** Paths that are the portfolio itself, served under /@username. */
    private const PORTFOLIO_PATH = '#^/(collections(/[^/]+(/unlock)?)?|print-products)?/?$#';

    /** The gallery's own endpoints, which work on any host. */
    private const PASSTHROUGH_PATH = '#^/(photos/|orders/|livewire/|media/|build/|images/|collections/[^/]+/(request-archive|archive/|download-archive/)|robots\.txt$|sitemap\.xml$|favicon\.ico$|up$)#';

    public function __construct(private DomainRegistry $registry) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $this->resetUrls();

        if (DomainRegistry::isPlatformHost($host)) {
            return $this->redirectToCustomDomain($request) ?? $next($request);
        }

        $entry = $this->registry->find($host);
        if (! $entry) {
            abort(404);
        }

        if ($host !== $entry['primary']) {
            return redirect()->away('https://'.$entry['primary'].$request->getRequestUri(), 301);
        }

        $path = $request->getPathInfo();
        if (str_starts_with($path, '/internal')) {
            abort(404); // server-to-server endpoints only exist on the platform host
        }
        if (! preg_match(self::PORTFOLIO_PATH, $path) && ! preg_match(self::PASSTHROUGH_PATH, $path)) {
            // Accounts, checkout for plans, the marketing site: the main domain.
            return redirect()->away(rtrim(config('app.url'), '/').$request->getRequestUri());
        }

        $this->useHost($request, $entry['username']);
        app()->instance('custom-domain', $entry);

        if (preg_match(self::PORTFOLIO_PATH, $path)) {
            $request = $this->rewrite($request, '/@'.$entry['username'].rtrim($path, '/'));
        }

        return $next($request);
    }

    /** /@user/... on the platform host 301s to the user's live custom domain. */
    private function redirectToCustomDomain(Request $request): ?Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }
        if (! preg_match('#^/@([^/]+)(/.*)?$#', $request->getPathInfo(), $m)) {
            return null;
        }
        $rest = $m[2] ?? '/';
        if (! preg_match(self::PORTFOLIO_PATH, $rest)) {
            return null;
        }
        $primary = $this->registry->primaryFor(rawurldecode($m[1]));
        if (! $primary) {
            return null;
        }
        $query = $request->getQueryString();

        return redirect()->away('https://'.$primary.$rest.($query ? '?'.$query : ''), 301);
    }

    /** URLs for the platform host (the generator is shared within a process). */
    private function resetUrls(): void
    {
        URL::forceRootUrl(null);
        URL::formatPathUsing(fn (string $path) => $path);
        app()->forgetInstance('custom-domain');
    }

    /** Generate URLs for this host, without the /@username prefix. */
    private function useHost(Request $request, string $username): void
    {
        URL::forceRootUrl($request->getScheme().'://'.$request->getHttpHost());
        $prefix = '/@'.strtolower($username);
        URL::formatPathUsing(function (string $path) use ($prefix) {
            $lower = strtolower($path);
            if ($lower === $prefix) {
                return '/';
            }

            return str_starts_with($lower, $prefix.'/') ? substr($path, strlen($prefix)) : $path;
        });
        // Cookies on a custom domain must be host-only.
        config(['session.domain' => null]);
    }

    private function rewrite(Request $request, string $path): Request
    {
        $query = $request->getQueryString();
        $server = $request->server->all();
        $server['REQUEST_URI'] = $path.($query ? '?'.$query : '');

        return $request->duplicate(null, null, null, null, null, $server);
    }
}
