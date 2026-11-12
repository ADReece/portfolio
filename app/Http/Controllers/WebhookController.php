<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class WebhookController extends CashierController
{
    /**
     * Rejects payloads that aren't a Stripe event (400) instead of erroring.
     * With STRIPE_WEBHOOK_SECRET set, Cashier's signature check runs first and
     * a bad signature gets 403. Events for customers or payments that aren't
     * Portfolio's (the account is shared) are acknowledged with 200 and ignored.
     */
    public function handleWebhook(\Illuminate\Http\Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload) || ! is_string($payload['type'] ?? null) || ! is_array($payload['data']['object'] ?? null)) {
            return new \Symfony\Component\HttpFoundation\Response('Invalid payload', 400);
        }

        return parent::handleWebhook($request);
    }

    /**
     * Handle subscription updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionUpdated($payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        // Add custom logic here if needed

        return $response;
    }

    /**
     * Handle subscription deleted.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted($payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        // Downgrade user to free plan
        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            $freePlan = \App\Models\SubscriptionPlan::where('slug', 'free')->first();
            if ($freePlan) {
                $user->update(['subscription_plan_id' => $freePlan->id]);
            }
        }

        return $response;
    }

    /*
     * Print and download sales now run on Portfolio's own Connect platform
     * (ConnectWebhookController). These handle any sale started on this
     * shared account before the switch; they no longer move money.
     */
    public function handlePaymentIntentSucceeded($payload)
    {
        \App\Billing\SaleEvents::paid($payload['data']['object']);

        return $this->successMethod();
    }

    public function handlePaymentIntentPaymentFailed($payload)
    {
        \App\Billing\SaleEvents::failed($payload['data']['object']);

        return $this->successMethod();
    }

    public function handleChargeRefunded($payload)
    {
        \App\Billing\SaleEvents::refunded($payload['data']['object']);

        return $this->successMethod();
    }
}
