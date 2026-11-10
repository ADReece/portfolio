<?php

/*
 * Reporting subscribers to Ascend Digital's own CRM (the platform operator's
 * view of every app's customers and revenue). Off while the token is unset.
 */
return [
    'url' => rtrim(env('PLATFORM_CRM_URL', 'https://crm.ascend-digital.co.uk'), '/'),
    'token' => env('PLATFORM_CRM_TOKEN'),
    'app' => 'portfolio',
    // account.updated events wait this long so a burst of edits sends one event.
    'debounce_seconds' => (int) env('PLATFORM_CRM_DEBOUNCE_SECONDS', 120),
];
