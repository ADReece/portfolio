<?php

return [
    // Enforce the Content Security Policy instead of only reporting it.
    // Leave false until the browser console shows no CSP violations.
    'csp_enforce' => (bool) env('CSP_ENFORCE', false),
];
