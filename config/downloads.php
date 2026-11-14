<?php

/*
 * Downloads: paid digital downloads (App\Downloads\DigitalSales), and
 * downloading several photos at once (App\Downloads\BulkDownload): small
 * downloads stream a ZIP straight away; bigger ones are built by the queue
 * and offered when ready.
 */
return [
    // How long a buyer's download link works, from payment.
    'link_days' => (int) env('DOWNLOAD_LINK_DAYS', 30),

    // Up to this many photos and bytes, the ZIP streams straight away.
    'instant_max_photos' => (int) env('DOWNLOADS_INSTANT_MAX_PHOTOS', 25),
    'instant_max_bytes' => (int) env('DOWNLOADS_INSTANT_MAX_MB', 500) * 1024 * 1024,

    // The most photos one download can hold.
    'max_photos' => (int) env('DOWNLOADS_MAX_PHOTOS', 3000),
    'max_bytes' => (int) env('DOWNLOADS_MAX_GB', 20) * 1024 * 1024 * 1024,

    // How long an instant link works, and how long a built ZIP is kept.
    'instant_link_minutes' => 15,
    'archive_hours' => 48,

    // Background ZIPs one visitor can have building at once.
    'max_building_per_ip' => 3,
];
