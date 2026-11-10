<?php

namespace App\Billing;

/**
 * Photographer payouts need Stripe Connect. The shared Ascend Digital Stripe
 * account doesn't have Connect yet, so while STRIPE_CONNECT_CLIENT_ID is
 * unset, selling prints shows as "coming soon": no Connect onboarding and no
 * print checkout (clients would pay but photographers couldn't be paid).
 */
class Payouts
{
    public static function available(): bool
    {
        return filled(config('services.stripe.connect_client_id'));
    }
}
