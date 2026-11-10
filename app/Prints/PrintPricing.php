<?php

namespace App\Prints;

use App\Models\ProdigiProduct;

/**
 * Print prices. The minimum retail price covers the latest Prodigi cost
 * (item + standard UK shipping), Stripe's card fee and the platform fee, so a
 * photographer can never sell at a loss:
 *
 *   price × (1 − platform% − stripe%) − stripe fixed fee ≥ cost
 */
class PrintPricing
{
    /** Null when the product hasn't been quoted yet. */
    public function minimumFor(ProdigiProduct $product): ?float
    {
        $cost = $product->totalCost();
        if ($cost === null) {
            return null;
        }

        $keep = 1 - (config('services.platform_fee_percent') + config('prints.stripe_fee_percent')) / 100;
        $pence = ($cost + config('prints.stripe_fee_fixed')) / max($keep, 0.01) * 100;

        return ceil(round($pence, 6)) / 100;
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
}
