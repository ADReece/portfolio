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

    /** Print and download sales are tagged app=portfolio; anything else belongs to another app. */
    private function isPortfolioPayment(array $paymentIntent): bool
    {
        return ($paymentIntent['metadata']['app'] ?? null) === 'portfolio';
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

    /**
     * A print or download sale was paid (payment_intent.succeeded). Stripe
     * retries webhooks, so this runs its side effects (Prodigi order or download
     * email, photographer transfer) only the first time an order is marked paid.
     */
    public function handlePaymentIntentSucceeded($payload)
    {
        $paymentIntent = $payload['data']['object'];
        if (! $this->isPortfolioPayment($paymentIntent)) {
            return $this->successMethod();
        }

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

        if ($order->isDigital()) {
            \App\Jobs\DeliverDigitalOrder::dispatch($order->id);
        } elseif ($order->hasPrintItems()) {
            \App\Jobs\SubmitOrderToProdigi::dispatch($order);
        }

        // Pay the photographer their share via Stripe Connect. The charge is on
        // the platform account; this is a separate transfer to theirs.
        if ($order->user->stripe_connect_enabled && $order->user->stripe_connect_id && (float) $order->photographer_amount > 0) {
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
        if (! $this->isPortfolioPayment($paymentIntent)) {
            return $this->successMethod();
        }

        $order = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if (! $order && ! empty($paymentIntent['metadata']['order_id'])) {
            $order = \App\Models\Order::find($paymentIntent['metadata']['order_id']);
        }

        if ($order && ! $order->paid_at) {
            $order->update(['status' => 'failed']);
        }

        return $this->successMethod();
    }

    /**
     * A sale refunded in full in Stripe (charge.refunded): the order is marked
     * refunded, which also stops a download order's links working.
     */
    public function handleChargeRefunded($payload)
    {
        $charge = $payload['data']['object'];
        if (empty($charge['payment_intent']) || ! ($charge['refunded'] ?? false)) {
            return $this->successMethod(); // partial refunds keep the order
        }

        \App\Models\Order::where('stripe_payment_intent_id', $charge['payment_intent'])
            ->where('status', 'completed')
            ->update(['status' => 'refunded']);

        return $this->successMethod();
    }
}
