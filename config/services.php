<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Support chat on Portfol.io's own pages (not photographers' portfolios).
    'chatmesh' => [
        'key' => env('CHATMESH_KEY'),
        'url' => env('CHATMESH_URL', 'https://chatmesh.ascend-digital.co.uk'),
    ],

    // Portfolio's own Stripe account with Connect (separate from the shared
    // Ascend Digital account used for subscriptions). Selling prints and
    // downloads stays off until the secret and webhook secret are set.
    'portfolio_connect' => [
        'key' => env('PORTFOLIO_CONNECT_STRIPE_KEY'),
        'secret' => env('PORTFOLIO_CONNECT_STRIPE_SECRET'),
        'webhook_secret' => env('PORTFOLIO_CONNECT_STRIPE_WEBHOOK_SECRET'),
        'country' => env('PORTFOLIO_CONNECT_COUNTRY', 'GB'),
    ],

    // Percentage the platform keeps from each client print sale. Shown to
    // photographers on the Connect Stripe page, so copy always matches.
    'platform_fee_percent' => (float) env('PLATFORM_FEE_PERCENT', 10),

    'prodigi' => [
        'api_key' => env('PRODIGI_API_KEY'),
        'merchant_id' => env('PRODIGI_MERCHANT_ID'),
        'environment' => env('PRODIGI_ENVIRONMENT', 'sandbox'),
        // Live: https://api.prodigi.com/v4.0 (every order costs real money).
        'base_url' => env('PRODIGI_BASE_URL', 'https://api.sandbox.prodigi.com/v4.0'),
    ],

];
