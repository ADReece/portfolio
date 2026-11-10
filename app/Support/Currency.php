<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Single source of truth for the platform currency, so every page, checkout
 * and Stripe call agrees. Configured in Admin → Settings.
 */
class Currency
{
    public const SYMBOLS = [
        'GBP' => '£',
        'USD' => '$',
        'EUR' => '€',
        'AUD' => 'A$',
        'CAD' => 'C$',
    ];

    private static ?string $code = null;

    public static function code(): string
    {
        if (self::$code === null) {
            try {
                $value = Setting::where('key', 'default_currency')->value('value');
            } catch (\Throwable $e) {
                $value = null; // e.g. before migrations have run
            }
            self::$code = strtoupper($value ?: 'GBP');
        }

        return self::$code;
    }

    /** Lower-case ISO code, as Stripe expects. */
    public static function stripeCode(): string
    {
        return strtolower(self::code());
    }

    public static function symbol(): string
    {
        return self::SYMBOLS[self::code()] ?? self::code().' ';
    }

    public static function format(float|int|string|null $amount, int $decimals = 2): string
    {
        return self::symbol().number_format((float) $amount, $decimals);
    }

    /** Clear the memoised value (tests, settings updates). */
    public static function flush(): void
    {
        self::$code = null;
    }
}
