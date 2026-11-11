<?php

namespace App\Downloads;

use App\Models\Collection;
use App\Models\User;
use App\Prints\PrintPricing;

/**
 * Prices for full-resolution downloads, set by each photographer: a price per
 * photo, bundles ("any 5 photos for £20"), and per collection a price for the
 * whole collection. Collections can override the per-photo price.
 *
 * Clients always pay the cheapest combination for the photos they choose.
 * From the total the platform keeps its fee (out of which it pays the card
 * fee) and the photographer receives the rest.
 */
class DigitalPricing
{
    public const MAX_BUNDLES = 5;

    public function __construct(private PrintPricing $prints) {}

    /**
     * The lowest price for anything: the platform fee must cover the card fee
     * (and never below £1).
     */
    public function minimumPrice(): float
    {
        $rate = $this->prints->platformRate();
        $cardMargin = $rate - config('prints.stripe_fee_percent') / 100;
        $minimum = $cardMargin > 0 ? config('prints.stripe_fee_fixed') / $cardMargin : 1.0;

        return max(1.0, ceil(round($minimum * 100, 6)) / 100);
    }

    /**
     * What a collection's photos sell for: the collection's own prices, else
     * the photographer's defaults.
     *
     * @return array{photo: ?float, bundles: list<array{count: int, price: float}>, collection: ?float}
     */
    public function offerFor(Collection $collection, ?User $owner = null): array
    {
        $owner ??= $collection->user;
        $photo = $collection->digital_photo_price ?? $owner?->digital_photo_price;

        return [
            'photo' => $photo !== null ? (float) $photo : null,
            'bundles' => self::normaliseBundles($owner?->digital_bundles ?? []),
            'collection' => $collection->digital_collection_price !== null ? (float) $collection->digital_collection_price : null,
        ];
    }

    public static function hasPrices(array $offer): bool
    {
        return $offer['photo'] !== null || $offer['bundles'] !== [] || $offer['collection'] !== null;
    }

    /**
     * The cheapest way to buy $count photos out of $available.
     *
     * @return array{total: float, all: bool, lines: list<array{label: string, quantity: int, amount: float}>}|null
     *                                                                                                              null when the photos can't be bought with these prices. "all" is
     *                                                                                                              true when buying the whole collection is cheapest (the client
     *                                                                                                              then gets every photo).
     */
    public function quote(array $offer, int $count, int $available): ?array
    {
        if ($count < 1 || $available < 1 || $count > $available) {
            return null;
        }

        return $this->fromPlan($offer, $this->plan($offer, $count), $count, $available);
    }

    /**
     * Quotes for buying 1, 2, … $available photos, for the buy page to show
     * the price as the client picks photos.
     *
     * @return array<int, array{total: float, all: bool, lines: list<array{label: string, quantity: int, amount: float}>}|null>
     */
    public function quoteTable(array $offer, int $available): array
    {
        $plan = $this->plan($offer, $available);
        $table = [];
        for ($count = 1; $count <= $available; $count++) {
            $table[$count] = $this->fromPlan($offer, $plan, $count, $available);
        }

        return $table;
    }

    /** Fewest pence to cover 0…$upTo photos with singles and bundles (a bundle may cover fewer photos than its size). */
    private function plan(array $offer, int $upTo): array
    {
        $options = [];
        if ($offer['photo'] !== null) {
            $options[] = ['size' => 1, 'pence' => self::pence($offer['photo']), 'label' => 'Single photo'];
        }
        foreach ($offer['bundles'] as $bundle) {
            $options[] = ['size' => $bundle['count'], 'pence' => self::pence($bundle['price']), 'label' => "Bundle of {$bundle['count']} photos"];
        }

        $cost = array_fill(0, $upTo + 1, PHP_INT_MAX);
        $choice = array_fill(0, $upTo + 1, null);
        $cost[0] = 0;
        if ($options) {
            for ($k = 1; $k <= $upTo; $k++) {
                foreach ($options as $i => $option) {
                    $before = $cost[max(0, $k - $option['size'])];
                    if ($before !== PHP_INT_MAX && $before + $option['pence'] < $cost[$k]) {
                        $cost[$k] = $before + $option['pence'];
                        $choice[$k] = $i;
                    }
                }
            }
        }

        return ['options' => $options, 'cost' => $cost, 'choice' => $choice];
    }

    private function fromPlan(array $offer, array $plan, int $count, int $available): ?array
    {
        $best = null;
        if ($plan['cost'][$count] !== PHP_INT_MAX) {
            $used = [];
            for ($k = $count; $k > 0; $k = max(0, $k - $plan['options'][$plan['choice'][$k]]['size'])) {
                $used[$plan['choice'][$k]] = ($used[$plan['choice'][$k]] ?? 0) + 1;
            }
            ksort($used);
            $lines = [];
            foreach ($used as $i => $quantity) {
                $option = $plan['options'][$i];
                $lines[] = ['label' => $option['label'], 'quantity' => $quantity, 'amount' => $quantity * $option['pence'] / 100];
            }
            $best = ['total' => $plan['cost'][$count] / 100, 'all' => false, 'lines' => $lines];
        }

        if ($offer['collection'] !== null && ($best === null || self::pence($offer['collection']) <= self::pence($best['total']))) {
            $best = [
                'total' => self::pence($offer['collection']) / 100,
                'all' => true,
                'lines' => [['label' => "Whole collection ({$available} photos)", 'quantity' => 1, 'amount' => self::pence($offer['collection']) / 100]],
            ];
        }

        return $best;
    }

    /** The photographer's share of a sale: total − platform fee. */
    public function payout(float $total): float
    {
        return round($total - $this->prints->platformFee($total), 2);
    }

    public function platformFee(float $total): float
    {
        return $this->prints->platformFee($total);
    }

    /**
     * Valid bundles only (2+ photos, a price), smallest first, one per size.
     *
     * @return list<array{count: int, price: float}>
     */
    public static function normaliseBundles(?array $bundles): array
    {
        return collect($bundles ?? [])
            ->filter(fn ($b) => is_array($b) && (int) ($b['count'] ?? 0) >= 2 && is_numeric($b['price'] ?? null))
            ->map(fn ($b) => ['count' => (int) $b['count'], 'price' => round((float) $b['price'], 2)])
            ->unique('count')->sortBy('count')->values()->all();
    }

    private static function pence(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
