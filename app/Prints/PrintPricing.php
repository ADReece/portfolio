<?php

namespace App\Prints;

use App\Models\ProdigiProduct;

/**
 * Print prices and who gets what. The client pays the list price (print and
 * delivery included). From it:
 *   - Prodigi is paid its cost (print + delivery),
 *   - the platform keeps its fee (PLATFORM_FEE_PERCENT of the price), out of
 *     which it pays Stripe's card fee,
 *   - the photographer receives the rest: price − cost − platform fee.
 *
 * The minimum price keeps the photographer's share at or above zero and the
 * platform fee at or above the card fee, so nobody sells at a loss.
 */
class PrintPricing
{
    public function platformRate(): float
    {
        return config('services.platform_fee_percent') / 100;
    }

    public function platformFee(float $price): float
    {
        return round($price * $this->platformRate(), 2);
    }

    /** Null when the product hasn't been quoted yet. */
    public function minimumFor(ProdigiProduct $product): ?float
    {
        $cost = $product->totalCost();
        if ($cost === null) {
            return null;
        }

        $rate = $this->platformRate();
        // Photographer's share ≥ 0: price × (1 − platform%) ≥ cost.
        $minimum = $cost / max(1 - $rate, 0.01);
        // Platform fee ≥ card fee: price × (platform% − card%) ≥ card fixed fee.
        $cardMargin = $rate - config('prints.stripe_fee_percent') / 100;
        if ($cardMargin > 0) {
            $minimum = max($minimum, config('prints.stripe_fee_fixed') / $cardMargin);
        }

        return ceil(round($minimum * 100, 6)) / 100;
    }

    /** The price clients pay when the photographer hasn't set one. */
    public function defaultFor(ProdigiProduct $product): float
    {
        $price = $product->default_retail_price !== null
            ? (float) $product->default_retail_price
            : (float) config('prints.fallback_retail_price');

        return max($price, $this->minimumFor($product) ?? 0);
    }

    /** What a client pays: the photographer's price, else the default. */
    public function priceFor(ProdigiProduct $product, $photographerPrice): float
    {
        return $photographerPrice !== null ? (float) $photographerPrice : $this->defaultFor($product);
    }

    /** False when the price no longer covers the cost (it has gone up). */
    public function isSellableAt(ProdigiProduct $product, float $price): bool
    {
        $minimum = $this->minimumFor($product);

        return $minimum === null || $price + 0.00001 >= $minimum;
    }

    /** The photographer's share of one sale at $price, with the catalogue's UK cost. */
    public function payoutFor(ProdigiProduct $product, float $price): ?float
    {
        $cost = $product->totalCost();

        return $cost === null ? null : $this->payout($price, $cost);
    }

    /** price − cost − platform fee, for an order's actual total and cost. */
    public function payout(float $total, float $cost): float
    {
        return round($total - $cost - $this->platformFee($total), 2);
    }
}
