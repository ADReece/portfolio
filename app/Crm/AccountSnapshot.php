<?php

namespace App\Crm;

use App\CustomDomains\DomainRegistry;
use App\Models\User;

/**
 * What the CRM is told about an account. Never includes passwords, tokens or
 * payment details; the Stripe customer id is only a reference.
 */
class AccountSnapshot
{
    public static function for(User $user): array
    {
        $user->loadMissing('subscriptionPlan');
        $plan = $user->subscriptionPlan;
        $slug = $plan?->slug ?? 'free';
        $subscription = $user->subscriptions()->where('type', 'default')->latest()->first();

        $status = self::status($slug, $subscription);
        $interval = null;
        if ($slug !== 'free' && $subscription) {
            $interval = $plan && $plan->annual_stripe_price_id && $subscription->stripe_price === $plan->annual_stripe_price_id ? 'year' : 'month';
        }

        $mrr = 0;
        if ($plan && $interval && in_array($status, ['active', 'trialing', 'past_due'], true) && ! $user->isFeatureOverrideActive()) {
            $mrr = $interval === 'year'
                ? (int) round(((float) ($plan->annual_price ?: $plan->price * 12)) * 100 / 12)
                : (int) round((float) $plan->price * 100);
        }

        $domain = $user->username ? app(DomainRegistry::class)->primaryFor($user) : null;

        return [
            'external_id' => (string) $user->id,
            'name' => $user->name ?: $user->username,
            'email' => $user->email,
            'contact_name' => $user->name,
            'url' => $domain ? 'https://'.$domain : ($user->username ? route('profile.view', $user->username) : null),
            'plan' => $slug,
            'interval' => $interval,
            'status' => $status,
            'mrr_pence' => $mrr,
            'stripe_customer_id' => $user->stripe_id,
            'signed_up_at' => $user->created_at?->toIso8601ZuluString(),
            'country' => null, // not collected by Portfolio
        ];
    }

    private static function status(string $slug, $subscription): string
    {
        if (! $subscription) {
            return 'free';
        }
        if ($subscription->ended() || $subscription->onGracePeriod()) {
            return $slug === 'free' && $subscription->ended() ? 'free' : 'cancelled';
        }

        return match ($subscription->stripe_status) {
            'trialing' => 'trialing',
            'past_due', 'unpaid', 'incomplete' => 'past_due',
            'canceled', 'incomplete_expired' => $slug === 'free' ? 'free' : 'cancelled',
            default => $slug === 'free' ? 'free' : 'active',
        };
    }
}
