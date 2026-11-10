<?php

namespace App\Tracking;

use Illuminate\Http\Request;

/**
 * First-touch attribution: the UTM parameters and ad click ids from the
 * visitor's first landing, kept in their session and saved on the account
 * at sign-up.
 */
class Attribution
{
    public const SESSION_KEY = 'attribution.first_touch';

    public const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid'];

    public static function capture(Request $request): void
    {
        if (! $request->hasSession() || $request->session()->has(self::SESSION_KEY)) {
            return; // first landing wins
        }
        $values = collect(self::KEYS)
            ->mapWithKeys(fn ($k) => [$k => $request->query($k)])
            ->filter(fn ($v) => is_string($v) && $v !== '')
            ->map(fn ($v) => mb_substr($v, 0, 255))
            ->all();
        if ($values === []) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $values + [
            'landing_page' => mb_substr($request->path() === '/' ? '/' : '/'.$request->path(), 0, 255),
            'referrer_host' => ($ref = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST)) ? mb_substr($ref, 0, 255) : null,
            'captured_at' => now()->toIso8601ZuluString(),
        ]);
    }

    public static function current(): ?array
    {
        return session(self::SESSION_KEY);
    }
}
