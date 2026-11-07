<?php

namespace App\Support;

/**
 * Shipping destinations offered at print checkout (ISO 3166-1 alpha-2).
 */
class Countries
{
    public const LIST = [
        'GB' => 'United Kingdom', 'IE' => 'Ireland', 'US' => 'United States', 'CA' => 'Canada',
        'AU' => 'Australia', 'NZ' => 'New Zealand', 'AT' => 'Austria', 'BE' => 'Belgium',
        'BG' => 'Bulgaria', 'HR' => 'Croatia', 'CY' => 'Cyprus', 'CZ' => 'Czechia',
        'DK' => 'Denmark', 'EE' => 'Estonia', 'FI' => 'Finland', 'FR' => 'France',
        'DE' => 'Germany', 'GR' => 'Greece', 'HU' => 'Hungary', 'IS' => 'Iceland',
        'IT' => 'Italy', 'LV' => 'Latvia', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
        'MT' => 'Malta', 'NL' => 'Netherlands', 'NO' => 'Norway', 'PL' => 'Poland',
        'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia', 'SI' => 'Slovenia',
        'ES' => 'Spain', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'JP' => 'Japan',
        'SG' => 'Singapore', 'HK' => 'Hong Kong', 'AE' => 'United Arab Emirates', 'ZA' => 'South Africa',
        'MX' => 'Mexico', 'BR' => 'Brazil', 'IN' => 'India', 'IL' => 'Israel', 'KR' => 'South Korea',
    ];

    /** Sensible default destination for the platform currency. */
    public static function defaultCode(): string
    {
        return match (Currency::code()) {
            'USD' => 'US', 'CAD' => 'CA', 'AUD' => 'AU', 'EUR' => 'IE',
            default => 'GB',
        };
    }
}
