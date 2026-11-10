<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Stripe can't send a CSRF token; the webhook controller checks
        // Stripe's signature instead (STRIPE_WEBHOOK_SECRET).
        'stripe/webhook',
    ];
}
