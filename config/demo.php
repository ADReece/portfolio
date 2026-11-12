<?php

/*
 * The public demo photographer (App\Demo\DemoAccount, php artisan
 * portfolio:demo-seed). A made-up studio: never a real person or customer.
 * Photos are free Unsplash stock (Unsplash License), each credited to its
 * photographer, fetched with UNSPLASH_ACCESS_KEY through Unsplash's API.
 */
return [
    'username' => 'demo',
    'name' => 'Marlow & Finch Photography',
    'email' => 'demo@portfolio.invalid',
    'bio' => '<p>Wedding, portrait and landscape photographers based in the Lake District.</p><p>This is a demo portfolio: try everything clients see. Nothing is charged and no emails are sent. Photos are from <a href="https://unsplash.com/?utm_source=portfolio&amp;utm_medium=referral">Unsplash</a>, credited on each photo.</p>',

    'unsplash_key' => env('UNSPLASH_ACCESS_KEY'),
    'utm' => 'utm_source=portfolio&utm_medium=referral',

    // Photos per category, found by Unsplash search the first time and kept
    // after that (re-running the seed reuses them).
    'categories' => [
        'weddings' => ['query' => 'wedding couple', 'count' => 12],
        'portraits' => ['query' => 'portrait photography', 'count' => 10],
        'landscapes' => ['query' => 'mountain landscape', 'count' => 10],
        'events' => ['query' => 'party celebration', 'count' => 8],
    ],

    // The password-protected client gallery; shown on the demo so visitors can try it.
    'client_gallery_password' => 'demo',

    // A short film for the demo's video gallery (CC0 or similar; MP4, H.264).
    // Optional: without it the demo has no films.
    'video_url' => env('DEMO_VIDEO_URL'),
    'video_title' => env('DEMO_VIDEO_TITLE', 'Wedding highlights'),
    'video_credit' => env('DEMO_VIDEO_CREDIT'),

    // A domain shown as an example in the "things to try" panel; never connected.
    'example_domain' => 'marlowandfinch.example',
];
