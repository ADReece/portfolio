<?php

/*
 * Ascend Digital's own conversion tracking (GA4, Google Ads, Meta Pixel).
 * Each loads only when its ID is set AND the visitor accepts cookies, and only
 * on Portfolio's own pages (marketing site, sign-up, billing, dashboard):
 * never on photographers' portfolios, galleries or custom domains.
 */
return [
    'ga4_measurement_id' => env('GA4_MEASUREMENT_ID'),
    'google_ads_id' => env('GOOGLE_ADS_ID'),                         // AW-123456789
    'google_ads_signup_label' => env('GOOGLE_ADS_SIGNUP_LABEL'),
    'google_ads_purchase_label' => env('GOOGLE_ADS_PURCHASE_LABEL'),
    'meta_pixel_id' => env('META_PIXEL_ID'),

    // How long the visitor's cookie choice is remembered.
    'consent_days' => 182,

    // Routes (name patterns) that are Portfolio's own pages, where tracking may load.
    'routes' => [
        'home', 'about', 'pricing', 'terms', 'privacy', 'sla', 'photo-credits', 'examples.*',
        'login', 'register', 'password.*', 'verification.*',
        'checkout', 'billing.*', 'subscription.*', 'connect.*', 'dashboard', 'collections.*', 'sets.*',
        'profile.edit', 'profile.customize', 'profile.prodigi-products.*', 'custom-domains.*',
        'templates.*', 'fonts.*', 'admin.*',
    ],
];
