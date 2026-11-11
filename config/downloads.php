<?php

/*
 * Paid digital downloads (App\Downloads).
 */
return [
    // How long a buyer's download link works, from payment.
    'link_days' => (int) env('DOWNLOAD_LINK_DAYS', 30),
];
