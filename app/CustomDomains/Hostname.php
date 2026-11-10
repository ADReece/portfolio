<?php

namespace App\CustomDomains;

/** Normalises and validates a hostname a photographer types in. */
class Hostname
{
    /**
     * "https://WWW.Stéphshoots.com/" -> ["stéphshoots" as punycode, include www?]
     * Returns null for anything that isn't a usable public hostname.
     *
     * @return array{hostname: string, had_www: bool}|null
     */
    public static function normalise(string $input): ?array
    {
        $host = trim(mb_strtolower($input));
        $host = preg_replace('#^[a-z]+://#', '', $host);
        $host = preg_replace('#[/?\#:].*$#', '', $host);
        $host = rtrim($host, '.');

        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT | IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
            if ($ascii === false) {
                return null;
            }
            $host = $ascii;
        }

        $hadWww = str_starts_with($host, 'www.');
        if ($hadWww) {
            $host = substr($host, 4);
        }

        $label = '(?!-)[a-z0-9-]{1,63}(?<!-)';
        if (strlen($host) > 253 || ! preg_match('/^('.$label.'\.)+[a-z][a-z0-9-]{1,62}$/', $host)) {
            return null;
        }
        if (self::isReserved($host)) {
            return null;
        }

        return ['hostname' => $host, 'had_www' => $hadWww];
    }

    /** The platform's own domains and special-use names can't be added. */
    public static function isReserved(string $host): bool
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $suffixes = array_merge(config('custom_domains.reserved_suffixes', []), array_filter([$appHost]), config('custom_domains.platform_hosts', []));

        foreach ($suffixes as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }
}
