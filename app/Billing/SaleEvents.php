<?php

namespace App\Billing;

use App\Jobs\DeliverDigitalOrder;
use App\Jobs\SendPrintOrderEmail;
use App\Jobs\SubmitOrderToProdigi;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * What happens when a print or download sale's payment changes in Stripe.
 * Stripe retries webhooks, so each side effect runs only on the first
 * delivery (when the order is first marked paid).
 */
class SaleEvents
{
    /** Sales are tagged app=portfolio; anything else isn't ours. */
    public static function isPortfolio(array $object): bool
    {
        return ($object['metadata']['app'] ?? null) === 'portfolio';
    }

    /** payment_intent.succeeded: mark paid, then deliver (download email, or Prodigi order + confirmation). */
    public static function paid(array $paymentIntent): ?Order
    {
        if (! self::isPortfolio($paymentIntent)) {
            return null;
        }

        $order = DB::transaction(function () use ($paymentIntent) {
            $order = self::find($paymentIntent, lock: true);
            if (! $order || $order->paid_at) {
                return null; // unknown order, or a retry of one already handled
            }

            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntent['id'],
                // With a destination charge, Stripe's transfer to the photographer.
                'stripe_transfer_id' => is_string($paymentIntent['transfer'] ?? null) ? $paymentIntent['transfer'] : $order->stripe_transfer_id,
            ]);

            return $order;
        });

        if (! $order) {
            return null;
        }

        if ($order->isDigital()) {
            DeliverDigitalOrder::dispatch($order->id);
        } elseif ($order->hasPrintItems()) {
            SubmitOrderToProdigi::dispatch($order);
            SendPrintOrderEmail::dispatch($order->id, SendPrintOrderEmail::CONFIRMED);
        }

        return $order;
    }

    /** payment_intent.payment_failed: an unpaid order is marked failed (a paid one is left alone). */
    public static function failed(array $paymentIntent): void
    {
        if (! self::isPortfolio($paymentIntent)) {
            return;
        }

        $order = self::find($paymentIntent);
        if ($order && ! $order->paid_at) {
            $order->update(['status' => 'failed']);
        }
    }

    /** charge.refunded in full: the order is refunded, which also stops a download order's links. */
    public static function refunded(array $charge): void
    {
        if (empty($charge['payment_intent']) || ! ($charge['refunded'] ?? false)) {
            return; // partial refunds keep the order
        }

        Order::where('stripe_payment_intent_id', $charge['payment_intent'])
            ->where('status', 'completed')
            ->update(['status' => 'refunded']);
    }

    private static function find(array $paymentIntent, bool $lock = false): ?Order
    {
        $query = fn () => $lock ? Order::query()->lockForUpdate() : Order::query();

        $order = $query()->where('stripe_payment_intent_id', $paymentIntent['id'])->first();
        if (! $order && ! empty($paymentIntent['metadata']['order_id'])) {
            $order = $query()->whereKey($paymentIntent['metadata']['order_id'])->first();
        }

        return $order;
    }
}
