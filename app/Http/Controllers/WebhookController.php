<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class WebhookController extends CashierController
{
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

    /**
     * Handle payment succeeded.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentIntentSucceeded($payload)
    {
        // Handle successful payment for orders
        $paymentIntent = $payload['data']['object'];

        // Find order by payment intent ID and mark as completed
        $order = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if (!$order && !empty($paymentIntent['metadata']['order_id'])) {
            $order = \App\Models\Order::find($paymentIntent['metadata']['order_id']);
        }

        if ($order) {
            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntent['id'],
            ]);

            if ($order->hasPrintItems()) {
                \App\Jobs\SubmitOrderToProdigi::dispatch($order);
            }

            // Transfer funds to photographer via Stripe Connect
            if ($order->user->stripe_connect_enabled) {
                try {
                    $stripe = new \Stripe\StripeClient(config('cashier.secret'));

                    $transfer = $stripe->transfers->create([
                        'amount' => $order->photographer_amount * 100, // Convert to cents
                        'currency' => \App\Support\Currency::stripeCode(),
                        'destination' => $order->user->stripe_connect_id,
                        'transfer_group' => 'ORDER_' . $order->id,
                    ]);

                    $order->update(['stripe_transfer_id' => $transfer->id]);
                } catch (\Exception $e) {
                    \Log::error('Failed to transfer funds: ' . $e->getMessage());
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle payment failed.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentIntentFailed($payload)
    {
        $paymentIntent = $payload['data']['object'];

        $order = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($order) {
            $order->update(['status' => 'failed']);
        }

        return $this->successMethod();
    }
}

