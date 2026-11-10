<?php

return [
    /*
    | Launch offer: 50% off the first 3 months for anyone who subscribes
    | monthly before the end date. The Stripe coupon is created by
    | `php artisan stripe:sync-plans --apply`. After the end date the offer
    | disappears from the site and checkout on its own; no deploy needed.
    | Set LAUNCH_COUPON_ID to an empty string to switch the offer off early.
    */
    'launch_offer' => [
        'coupon_id' => env('LAUNCH_COUPON_ID', 'LAUNCH50'),
        'ends_at' => env('LAUNCH_OFFER_ENDS_AT', '2027-02-10T23:59:59+00:00'),
        'percent_off' => 50,
        'months' => 3,
        'name' => 'Launch offer: 50% off 3 months',
    ],
];
