<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy, sent as Report-Only until browsers show no
 * violations; then set CSP_ENFORCE=true to enforce it. The other security
 * headers (HSTS, nosniff, X-Frame-Options, Referrer-Policy,
 * Permissions-Policy) come from nginx so static files get them too.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $header = config('security.csp_enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        if (! $response->headers->has($header)) {
            $response->headers->set($header, self::policy());
        }

        return $response;
    }

    public static function policy(): string
    {
        $chatmesh = self::origin(config('services.chatmesh.url'));
        $chatmeshWs = $chatmesh ? preg_replace('#^http#', 'ws', $chatmesh) : null;
        $s3 = self::origin(config('filesystems.disks.s3.url'));
        // Ascend Digital's own tracking (only loaded after cookie consent).
        $google = filled(config('tracking.ga4_measurement_id')) || filled(config('tracking.google_ads_id'));
        $meta = filled(config('tracking.meta_pixel_id'));
        $gScripts = $google ? ['https://www.googletagmanager.com', 'https://www.google-analytics.com', 'https://googleads.g.doubleclick.net', 'https://www.googleadservices.com'] : [];
        $gConnect = $google ? ['https://www.google-analytics.com', 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com', 'https://googleads.g.doubleclick.net', 'https://www.google.com', 'https://www.googleadservices.com'] : [];
        $gFrames = $google ? ['https://www.googletagmanager.com', 'https://td.doubleclick.net', 'https://bid.g.doubleclick.net'] : [];
        $mScripts = $meta ? ['https://connect.facebook.net'] : [];
        $mConnect = $meta ? ['https://www.facebook.com', 'https://connect.facebook.net'] : [];

        $directives = [
            'default-src' => ["'self'"],
            // Livewire and Alpine need inline scripts and eval.
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://js.stripe.com', $chatmesh, ...$gScripts, ...$mScripts],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'],
            'font-src' => ["'self'", 'data:', 'https://fonts.bunny.net'],
            // Photos may come from S3 or another https origin; never http.
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'connect-src' => ["'self'", 'https://api.stripe.com', $chatmesh, $chatmeshWs, $s3, ...$gConnect, ...$mConnect],
            'frame-src' => ['https://js.stripe.com', 'https://hooks.stripe.com', ...$gFrames],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ];

        return collect($directives)
            ->map(fn ($sources, $name) => $name.' '.implode(' ', array_unique(array_filter($sources))))
            ->implode('; ');
    }

    private static function origin(?string $url): ?string
    {
        $parts = $url ? parse_url($url) : false;

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
