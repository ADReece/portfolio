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

    'stripe' => [
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
    ],

    // Percentage the platform keeps from each client print sale. Shown to
    // photographers on the Connect Stripe page, so copy always matches.
    'platform_fee_percent' => (float) env('PLATFORM_FEE_PERCENT', 10),

    'prodigi' => [
        'api_key' => env('PRODIGI_API_KEY'),
        'merchant_id' => env('PRODIGI_MERCHANT_ID'),
        'environment' => env('PRODIGI_ENVIRONMENT', 'sandbox'),
        'base_url' => env('PRODIGI_BASE_URL', 'https://sandbox.prodigi.com/v4.0'),
    ],

];
