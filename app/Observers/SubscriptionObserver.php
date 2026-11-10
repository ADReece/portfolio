<?php

namespace App\Observers;

use App\Crm\CrmReporter;
use Laravel\Cashier\Subscription;

/**
 * Cashier subscription changes (checkout, Stripe webhooks, cancel/resume) as
 * CRM events for the subscriber.
 */
class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        if ($user = $subscription->owner) {
            app(CrmReporter::class)->planChanged($user);
        }
    }

    public function updated(Subscription $subscription): void
    {
        $user = $subscription->owner;
        if (! $user) {
            return;
        }
        $reporter = app(CrmReporter::class);

        if ($subscription->wasChanged('ends_at') && $subscription->ends_at && ! $subscription->getOriginal('ends_at')) {
            $reporter->cancelled($user);
        } elseif ($subscription->wasChanged(['stripe_status', 'stripe_price', 'ends_at', 'quantity'])) {
            $reporter->planChanged($user);
        }
    }
}
