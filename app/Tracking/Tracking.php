<?php

namespace App\Tracking;

use Illuminate\Support\Facades\Route;

/**
 * Consent-gated conversion tracking for Portfolio's own pages. Conversion
 * events are queued in the session and rendered once on the next page that
 * may carry tracking; the browser only sends them after the visitor accepts.
 */
class Tracking
{
    public const SESSION_KEY = 'tracking.events';

    public static function configured(): bool
    {
        return filled(config('tracking.ga4_measurement_id'))
            || filled(config('tracking.google_ads_id'))
            || filled(config('tracking.meta_pixel_id'));
    }

    /** Whether this page may carry Ascend Digital's tracking (and its banner). */
    public static function allowedHere(): bool
    {
        if (! self::configured() || app()->bound('custom-domain')) {
            return false;
        }
        $route = Route::currentRouteName();

        return $route !== null && collect(config('tracking.routes'))->contains(fn ($p) => \Illuminate\Support\Str::is($p, $route));
    }

    /**
     * Queue a conversion event. $id is stable for the conversion (e.g. the
     * Stripe subscription id), so it's sent once even if a page is reloaded.
     */
    public static function queue(string $name, string $id, array $params = []): void
    {
        if (! self::configured()) {
            return;
        }
        $events = session(self::SESSION_KEY, []);
        $events[$id] = ['name' => $name, 'id' => $id, 'params' => $params];
        session([self::SESSION_KEY => $events]);
    }

    /** The queued events, removed from the session so they render once. */
    public static function pull(): array
    {
        return array_values(session()->pull(self::SESSION_KEY, []));
    }

    /** Config for the browser: IDs only, never secrets. */
    public static function clientConfig(): array
    {
        return [
            'ga4' => config('tracking.ga4_measurement_id') ?: null,
            'ads' => config('tracking.google_ads_id') ?: null,
            'adsSignupLabel' => config('tracking.google_ads_signup_label') ?: null,
            'adsPurchaseLabel' => config('tracking.google_ads_purchase_label') ?: null,
            'meta' => config('tracking.meta_pixel_id') ?: null,
            'consentDays' => (int) config('tracking.consent_days'),
        ];
    }
}
