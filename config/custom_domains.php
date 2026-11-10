<?php

/*
 * Photographers' own domains (e.g. stephshoots.com for @stephshoots).
 * The server's sync job reads GET /internal/custom-domains every minute and
 * adds a Traefik route and certificate for each verified hostname.
 */
return [
    // Bearer token for GET /internal/custom-domains. Unset = endpoint is a 404.
    'sync_token' => env('CUSTOM_DOMAINS_SYNC_TOKEN'),

    // Where customers point their DNS.
    'target_ip' => env('CUSTOM_DOMAINS_TARGET_IP', '2.28.38.210'),
    // Optional IPv6 address. Without one, a domain with any AAAA record fails
    // verification (visitors and Let's Encrypt would go to the wrong server).
    'target_ipv6' => env('CUSTOM_DOMAINS_TARGET_IPV6'),
    // CNAME targets accepted for subdomains such as photos.example.com.
    'cname_targets' => array_filter(array_map('trim', explode(',', env(
        'CUSTOM_DOMAINS_CNAME_TARGETS',
        'portfolio.ascend-digital.co.uk,custom.portfolio.ascend-digital.co.uk'
    )))),

    // TXT record name prefix that proves ownership: _portfolio-verify.<domain>.
    'txt_prefix' => '_portfolio-verify',

    // Plans that can add custom domains, and how many (a domain's www counts
    // with it). "slug:count,slug:count". Admins and users with a feature
    // override get the largest allowance.
    'plans' => collect(explode(',', env('CUSTOM_DOMAIN_PLANS', 'photographer:1,videographer:1')))
        ->mapWithKeys(function ($pair) {
            [$slug, $count] = array_pad(explode(':', trim($pair)), 2, 1);

            return $slug === '' ? [] : [$slug => (int) $count];
        })->all(),

    // Hostnames that are the platform itself, never a customer's domain.
    // The app's own host (APP_URL), IP addresses, single-label container names
    // and localhost are always treated as the platform.
    'platform_hosts' => array_filter(array_map('trim', explode(',', env('CUSTOM_DOMAINS_PLATFORM_HOSTS', '')))),

    // Domains customers can never add (the platform's own).
    'reserved_suffixes' => ['ascend-digital.co.uk', 'duckdns.org', 'localhost', 'test', 'example', 'invalid', 'local'],

    // A pending domain is re-checked for this many days, then marked failed.
    'pending_days' => 7,
    // A verified domain whose DNS stops pointing here is taken offline after
    // failing checks for this many hours.
    'grace_hours' => 48,
];
