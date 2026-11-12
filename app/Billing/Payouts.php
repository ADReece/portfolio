<?php

namespace App\Billing;

use Stripe\StripeClient;

/**
 * Photographer payouts run on Portfolio's own Stripe account with Connect
 * (Express accounts, destination charges), separate from the shared Ascend
 * Digital account used for subscriptions. Until its keys are set
 * (PORTFOLIO_CONNECT_STRIPE_SECRET and _WEBHOOK_SECRET), selling prints and
 * downloads shows as "coming soon": no onboarding and no checkout.
 */
class Payouts
{
    public static function available(): bool
    {
        return filled(config('services.portfolio_connect.secret'))
            && filled(config('services.portfolio_connect.webhook_secret'));
    }

    /**
     * The Connect platform's API client (bound in the container, so tests can swap it).
     *
     * @return StripeClient
     */
    public static function stripe()
    {
        return app('stripe.connect');
    }

    /**
     * Updates a photographer from their Express account (from Stripe's API or
     * an account.updated event): they can sell once Stripe lets the account
     * take card payments and pay out to their bank.
     */
    public static function syncAccount(\App\Models\User $user, array $account): void
    {
        if (($account['id'] ?? null) !== $user->stripe_connect_id) {
            return;
        }

        $enabled = (bool) ($account['charges_enabled'] ?? false) && (bool) ($account['payouts_enabled'] ?? false);
        if ($user->stripe_connect_enabled !== $enabled) {
            $user->forceFill(['stripe_connect_enabled' => $enabled])->save();
        }
    }

    /** A photographer who can be paid: their Express account is ready (the demo only pretends). */
    public static function canPay(?\App\Models\User $user): bool
    {
        return $user !== null && ($user->is_demo
            || (self::available() && $user->stripe_connect_enabled && filled($user->stripe_connect_id)));
    }

    /**
     * What the platform keeps from a sale, in pence: the print cost (we pay
     * Prodigi) plus our fee. Stripe sends the rest to the photographer.
     */
    public static function applicationFee(float $total, float $photographerAmount): int
    {
        return max(0, (int) round(($total - max(0, $photographerAmount)) * 100));
    }
}
