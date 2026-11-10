<?php

namespace Tests\Feature\Billing;

use App\Billing\StripeCatalog;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncStripePlansTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeCatalog $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = new FakeStripeCatalog();
        $this->app->instance(StripeCatalog::class, $this->stripe);
        \App\Models\Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'GBP']);
        \App\Support\Currency::flush();

        SubscriptionPlan::factory()->create(['name' => 'Free', 'slug' => 'free', 'price' => 0, 'annual_price' => 0, 'sort_order' => 1]);
        SubscriptionPlan::factory()->create(['name' => 'Photographer', 'slug' => 'photographer', 'price' => 9.99, 'annual_price' => 99.99, 'sort_order' => 2]);
        SubscriptionPlan::factory()->create(['name' => 'Videographer', 'slug' => 'videographer', 'price' => 14.99, 'annual_price' => 139.99, 'sort_order' => 3]);
    }

    public function test_dry_run_is_the_default_and_changes_nothing(): void
    {
        $this->artisan('stripe:sync-plans')
            ->expectsOutputToContain('Dry run')
            ->expectsOutputToContain('would create product "Photographer"')
            ->expectsOutputToContain('would create monthly price GBP 9.99')
            ->expectsOutputToContain('would create annual price GBP 99.99')
            ->assertSuccessful();

        $this->assertSame([], $this->stripe->products);
        $this->assertSame([], $this->stripe->prices);
        $this->assertNull(SubscriptionPlan::where('slug', 'photographer')->value('stripe_price_id'));
    }

    public function test_apply_creates_products_and_gbp_prices_and_stores_ids(): void
    {
        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();

        $this->assertCount(2, $this->stripe->products, 'Free plan gets no product');
        $this->assertCount(4, $this->stripe->prices);

        $plan = SubscriptionPlan::where('slug', 'photographer')->first();
        $monthly = $this->stripe->prices[$plan->stripe_price_id];
        $annual = $this->stripe->prices[$plan->annual_stripe_price_id];

        $this->assertSame($plan->stripe_product_id, $monthly['product']);
        $this->assertSame(['plan_slug' => 'photographer'], $this->stripe->products[$plan->stripe_product_id]['metadata']);
        $this->assertSame([999, 'gbp', 'month'], [$monthly['unit_amount'], $monthly['currency'], $monthly['recurring']['interval']]);
        $this->assertSame([9999, 'gbp', 'year'], [$annual['unit_amount'], $annual['currency'], $annual['recurring']['interval']]);
        $this->assertNull(SubscriptionPlan::where('slug', 'free')->value('stripe_product_id'));
    }

    public function test_rerunning_creates_no_duplicates(): void
    {
        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();
        $ids = SubscriptionPlan::orderBy('slug')->get(['stripe_product_id', 'stripe_price_id', 'annual_stripe_price_id'])->toArray();

        $this->artisan('stripe:sync-plans --apply')->expectsOutputToContain('up to date')->assertSuccessful();

        $this->assertCount(2, $this->stripe->products);
        $this->assertCount(4, $this->stripe->prices);
        $this->assertSame($ids, SubscriptionPlan::orderBy('slug')->get(['stripe_product_id', 'stripe_price_id', 'annual_stripe_price_id'])->toArray());
    }

    public function test_finds_existing_product_by_slug_when_plan_has_no_ids(): void
    {
        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();
        SubscriptionPlan::query()->update(['stripe_product_id' => null, 'stripe_price_id' => null, 'annual_stripe_price_id' => null]);

        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();

        $this->assertCount(2, $this->stripe->products);
        $this->assertCount(4, $this->stripe->prices);
        $this->assertNotNull(SubscriptionPlan::where('slug', 'photographer')->value('stripe_price_id'));
    }

    public function test_price_change_creates_new_price_and_archives_old_one_without_editing_it(): void
    {
        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();
        $plan = SubscriptionPlan::where('slug', 'photographer')->first();
        $oldMonthly = $plan->stripe_price_id;
        $oldAnnual = $plan->annual_stripe_price_id;

        $plan->update(['price' => 12.00]);
        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();

        $plan->refresh();
        $this->assertNotSame($oldMonthly, $plan->stripe_price_id);
        $this->assertSame(1200, $this->stripe->prices[$plan->stripe_price_id]['unit_amount']);
        $this->assertFalse($this->stripe->prices[$oldMonthly]['active']);
        $this->assertSame(999, $this->stripe->prices[$oldMonthly]['unit_amount'], 'old price is never edited');
        $this->assertSame($oldAnnual, $plan->annual_stripe_price_id, 'unchanged annual price is kept');
        $this->assertTrue($this->stripe->prices[$oldAnnual]['active']);
        $this->assertSame(0, $this->stripe->priceEdits, 'only archiving touches existing prices');
    }

    public function test_apply_creates_the_launch_coupon_once(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-11-10 12:00:00');

        $this->artisan('stripe:sync-plans --apply')->expectsOutputToContain('Creating launch offer coupon LAUNCH50')->assertSuccessful();
        $this->artisan('stripe:sync-plans --apply')->expectsOutputToContain('Launch offer coupon LAUNCH50 exists')->assertSuccessful();

        $this->assertSame(['LAUNCH50'], array_keys($this->stripe->coupons));
        $coupon = $this->stripe->coupons['LAUNCH50'];
        $this->assertSame(50, $coupon['percent_off']);
        $this->assertSame('repeating', $coupon['duration']);
        $this->assertSame(3, $coupon['duration_in_months']);
        $this->assertSame(strtotime('2027-02-11T00:00:00Z'), $coupon['redeem_by']);
        $this->assertSame('Launch offer: 50% off 3 months', $coupon['name']);
    }

    public function test_dry_run_does_not_create_the_coupon(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-11-10 12:00:00');

        $this->artisan('stripe:sync-plans')->expectsOutputToContain('Would create launch offer coupon LAUNCH50')->assertSuccessful();

        $this->assertSame([], $this->stripe->coupons);
    }

    public function test_no_coupon_after_the_offer_ends_or_when_unset(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2027-02-11 00:00:01');
        $this->artisan('stripe:sync-plans --apply')->expectsOutputToContain('Launch offer ended')->assertSuccessful();

        \Illuminate\Support\Carbon::setTestNow('2026-11-10 12:00:00');
        config(['billing.launch_offer.coupon_id' => '']);
        $this->artisan('stripe:sync-plans --apply')->assertSuccessful();

        $this->assertSame([], $this->stripe->coupons);
    }
}

class FakeStripeCatalog implements StripeCatalog
{
    public array $products = [];
    public array $prices = [];
    public int $priceEdits = 0;
    private int $next = 1;

    public function findProductsByPlanSlug(string $slug): array
    {
        return array_values(array_filter($this->products, fn ($p) => $p['active'] && ($p['metadata']['plan_slug'] ?? null) === $slug));
    }

    public function createProduct(array $params): array
    {
        $id = 'prod_'.$this->next++;

        return $this->products[$id] = array_merge(['metadata' => []], $params, ['id' => $id, 'active' => true]);
    }

    public function updateProduct(string $id, array $params): array
    {
        return $this->products[$id] = array_merge($this->products[$id], $params);
    }

    public function activePrices(string $productId): array
    {
        return array_values(array_filter($this->prices, fn ($p) => $p['active'] && $p['product'] === $productId));
    }

    public function createPrice(array $params): array
    {
        $id = 'price_'.$this->next++;
        $params['recurring'] += ['interval_count' => 1];

        return $this->prices[$id] = ['id' => $id, 'active' => true] + $params;
    }

    public function archivePrice(string $id): void
    {
        $this->prices[$id]['active'] = false;
    }

    public array $coupons = [];

    public function findCoupon(string $id): ?array
    {
        return $this->coupons[$id] ?? null;
    }

    public function createCoupon(array $params): array
    {
        return $this->coupons[$params['id']] = $params;
    }
}
