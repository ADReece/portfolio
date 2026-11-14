<?php

return [
    // Photo links stay the same for this many hours (so browsers cache the
    // photos), and work for up to twice as long.
    'url_window_hours' => (int) env('MEDIA_URL_WINDOW_HOURS', 6),
];
