<?php

namespace App\Console\Commands;

use App\Billing\LaunchOffer;
use App\Billing\StripeCatalog;
use App\Models\SubscriptionPlan;
use App\Support\Currency;
use Illuminate\Console\Command;

/**
 * Creates the Stripe Product and monthly/annual Prices for each paid plan
 * and stores their IDs on the plan. Safe to re-run: products are found by
 * metadata.plan_slug and prices by amount, currency and interval. When a
 * plan's price changes, a new Price is created and the old one archived
 * (existing subscriptions keep the price they're on). Prices are never edited.
 */
class SyncStripePlans extends Command
{
    protected $signature = 'stripe:sync-plans
                            {--apply : Create the Stripe objects and save the IDs (default is a dry run)}
                            {--dry-run : Only show what would change (the default)}';

    protected $description = 'Create or find Stripe products and prices for the paid plans, and the launch offer coupon';

    private const INTERVALS = ['month' => ['price', 'stripe_price_id'], 'year' => ['annual_price', 'annual_stripe_price_id']];

    public function handle(StripeCatalog $stripe, LaunchOffer $offer): int
    {
        $apply = $this->option('apply') && ! $this->option('dry-run');
        $currency = Currency::stripeCode();

        $this->components->info(($apply ? 'Syncing' : 'Dry run: no changes will be made').' ('.strtoupper($currency).')');

        $plans = SubscriptionPlan::where('active', true)->orderBy('sort_order')->get()
            ->filter(fn (SubscriptionPlan $plan) => ! $plan->isFree() && (float) $plan->price > 0);

        $this->launchCoupon($stripe, $offer, $apply);

        if ($plans->isEmpty()) {
            $this->components->warn('No active paid plans.');

            return self::SUCCESS;
        }

        foreach ($plans as $plan) {
            $this->line("<options=bold>{$plan->name}</> ({$plan->slug})");
            $changes = [];

            $product = $this->product($stripe, $plan, $apply);
            if ($product['id'] !== $plan->stripe_product_id) {
                $changes['stripe_product_id'] = $product['id'];
            }

            $prices = $product['id'] === '(new)' ? [] : $stripe->activePrices($product['id']);

            foreach (self::INTERVALS as $interval => [$amountField, $idField]) {
                $amount = (float) $plan->{$amountField};
                if ($amount <= 0) {
                    continue;
                }

                $priceId = $this->price($stripe, $plan, $product['id'], $prices, $interval, (int) round($amount * 100), $currency, $apply);
                if ($priceId !== $plan->{$idField}) {
                    $changes[$idField] = $priceId;
                }
            }

            if ($changes && $apply) {
                $plan->forceFill($changes)->save();
                $this->line('  saved '.implode(', ', array_keys($changes)));
            } elseif ($changes) {
                $this->line('  would save '.implode(', ', array_keys($changes)));
            } else {
                $this->line('  up to date');
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->components->info('Run again with --apply to make these changes.');
        }

        return self::SUCCESS;
    }

    /** Creates the launch offer coupon if it doesn't exist yet. Never edits an existing one. */
    private function launchCoupon(StripeCatalog $stripe, LaunchOffer $offer, bool $apply): void
    {
        $id = $offer->couponId();
        if (! $id) {
            return;
        }
        if (! $offer->isActive()) {
            $this->line("Launch offer ended: coupon {$id} not needed");

            return;
        }
        if ($stripe->findCoupon($id)) {
            $this->line("Launch offer coupon {$id} exists");

            return;
        }
        $params = $offer->stripeCouponParams();
        $this->line(($apply ? 'Creating' : 'Would create')." launch offer coupon {$id}: {$params['percent_off']}% off {$params['duration_in_months']} months, redeemable until ".gmdate('Y-m-d H:i', $params['redeem_by']).' UTC');
        if ($apply) {
            $stripe->createCoupon($params);
        }
    }

    private function product(StripeCatalog $stripe, SubscriptionPlan $plan, bool $apply): array
    {
        $existing = $stripe->findProductsByPlanSlug($plan->slug);
        $product = collect($existing)->firstWhere('id', $plan->stripe_product_id) ?? ($existing[0] ?? null);

        if (count($existing) > 1) {
            $this->components->warn("  {$plan->slug} has ".count($existing)." active Stripe products; using {$product['id']}");
        }

        if (! $product) {
            $this->line('  '.($apply ? 'creating' : 'would create').' product "'.$plan->name.'"');
            if (! $apply) {
                return ['id' => '(new)'];
            }

            return $stripe->createProduct([
                'name' => $plan->name,
                'metadata' => ['plan_slug' => $plan->slug],
            ]);
        }

        if (($product['name'] ?? null) !== $plan->name) {
            $this->line("  ".($apply ? 'renaming' : 'would rename')." product {$product['id']} to \"{$plan->name}\"");
            if ($apply) {
                $product = $stripe->updateProduct($product['id'], ['name' => $plan->name]);
            }
        } else {
            $this->line("  product {$product['id']}");
        }

        return $product;
    }

    private function price(StripeCatalog $stripe, SubscriptionPlan $plan, string $productId, array $prices, string $interval, int $unitAmount, string $currency, bool $apply): string
    {
        $forInterval = array_values(array_filter($prices, fn ($p) => ($p['recurring']['interval'] ?? null) === $interval
            && (int) ($p['recurring']['interval_count'] ?? 1) === 1));

        $match = collect($forInterval)->first(fn ($p) => (int) $p['unit_amount'] === $unitAmount && strtolower($p['currency']) === $currency);
        $label = $interval === 'year' ? 'annual' : 'monthly';
        $money = strtoupper($currency).' '.number_format($unitAmount / 100, 2);

        if ($match) {
            $this->line("  {$label} price {$match['id']} ({$money})");
            $id = $match['id'];
        } else {
            $this->line('  '.($apply ? 'creating' : 'would create')." {$label} price {$money}");
            $id = $apply
                ? $stripe->createPrice([
                    'product' => $productId,
                    'currency' => $currency,
                    'unit_amount' => $unitAmount,
                    'recurring' => ['interval' => $interval],
                    'metadata' => ['plan_slug' => $plan->slug],
                ])['id']
                : '(new)';
        }

        // Archive this plan's other prices for the interval (old amounts).
        foreach ($forInterval as $old) {
            if ($old['id'] !== $id && ($old['metadata']['plan_slug'] ?? null) === $plan->slug) {
                $this->line('  '.($apply ? 'archiving' : 'would archive')." old {$label} price {$old['id']} (".strtoupper($old['currency']).' '.number_format($old['unit_amount'] / 100, 2).')');
                if ($apply) {
                    $stripe->archivePrice($old['id']);
                }
            }
        }

        return $id;
    }
}
