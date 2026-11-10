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
     * A print sale was paid (payment_intent.succeeded). Stripe retries webhooks,
     * so this runs its side effects (Prodigi order, photographer transfer) only
     * the first time an order is marked paid.
     */
    public function handlePaymentIntentSucceeded($payload)
    {
        $paymentIntent = $payload['data']['object'];

        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($paymentIntent) {
            $order = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntent['id'])->lockForUpdate()->first();

            if (! $order && ! empty($paymentIntent['metadata']['order_id'])) {
                $order = \App\Models\Order::whereKey($paymentIntent['metadata']['order_id'])->lockForUpdate()->first();
            }

            if (! $order || $order->paid_at) {
                return null; // unknown order, or a retry of one already handled
            }

            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntent['id'],
            ]);

            return $order;
        });

        if (! $order) {
            return $this->successMethod();
        }

        if ($order->hasPrintItems()) {
            \App\Jobs\SubmitOrderToProdigi::dispatch($order);
        }

        // Pay the photographer their share via Stripe Connect. The charge is on
        // the platform account; this is a separate transfer to theirs.
        if ($order->user->stripe_connect_enabled && $order->user->stripe_connect_id) {
            try {
                $params = [
                    // Whole pence: 12.35 * 100 is 1234.999… in floating point.
                    'amount' => (int) round((float) $order->photographer_amount * 100),
                    'currency' => \App\Support\Currency::stripeCode(),
                    'destination' => $order->user->stripe_connect_id,
                    'transfer_group' => 'ORDER_'.$order->id,
                ];

                // Tie the transfer to the charge so it can be paid from funds
                // that haven't settled yet (otherwise it needs available balance).
                if (! empty($paymentIntent['latest_charge'])) {
                    $params['source_transaction'] = $paymentIntent['latest_charge'];
                }

                $transfer = app(\Stripe\StripeClient::class)->transfers->create($params, [
                    'idempotency_key' => 'order-transfer-'.$order->id,
                ]);

                $order->update(['stripe_transfer_id' => $transfer->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to transfer funds for order '.$order->id.': '.$e->getMessage());
            }
        }

        return $this->successMethod();
    }

    /**
     * A print sale payment failed (payment_intent.payment_failed). Cashier calls
     * handle + the event name in StudlyCase, hence the method name.
     */
    public function handlePaymentIntentPaymentFailed($payload)
    {
        $paymentIntent = $payload['data']['object'];

        $order = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if (! $order && ! empty($paymentIntent['metadata']['order_id'])) {
            $order = \App\Models\Order::find($paymentIntent['metadata']['order_id']);
        }

        if ($order && ! $order->paid_at) {
            $order->update(['status' => 'failed']);
        }

        return $this->successMethod();
    }
}
