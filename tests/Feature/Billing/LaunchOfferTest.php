<?php

namespace Tests\Feature\Billing;

use App\Billing\LaunchOffer;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LaunchOfferTest extends TestCase
{
    use RefreshDatabase;

    private const DURING = '2026-11-20 12:00:00';
    private const LAST_SECOND = '2027-02-10 23:59:59';
    private const AFTER = '2027-02-11 00:00:00';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function at(string $time): void
    {
        Carbon::setTestNow($time);
        CarbonImmutable::setTestNow($time);
    }

    public function test_coupon_is_attached_to_monthly_subscriptions_during_the_offer(): void
    {
        $offer = new LaunchOffer();
        foreach ([self::DURING, self::LAST_SECOND] as $time) {
            $this->at($time);
            $builder = $offer->apply(User::factory()->create()->newSubscription('default', 'price_month'), 'month');
            $this->assertSame('LAUNCH50', $builder->couponId, "at {$time}");
        }
    }

    public function test_coupon_is_not_attached_for_yearly_after_the_end_or_when_unset(): void
    {
        $offer = new LaunchOffer();
        $user = User::factory()->create();

        $this->at(self::DURING);
        $this->assertNull($offer->apply($user->newSubscription('default', 'price_year'), 'year')->couponId, 'yearly');

        $this->at(self::AFTER);
        $this->assertNull($offer->apply($user->newSubscription('default', 'price_month'), 'month')->couponId, 'after the end date');

        $this->at(self::DURING);
        config(['billing.launch_offer.coupon_id' => '']);
        $this->assertNull($offer->apply($user->newSubscription('default', 'price_month'), 'month')->couponId, 'coupon id unset');
    }

    public function test_offer_prices(): void
    {
        $offer = new LaunchOffer();
        $this->assertSame('1.99', $offer->discountedMonthlyPrice(new SubscriptionPlan(['price' => 3.99])));
        $this->assertSame('3.99', $offer->discountedMonthlyPrice(new SubscriptionPlan(['price' => 7.99])));
        $this->assertSame('10 Feb 2027', $offer->endsLabel());
    }

    public function test_pricing_page_shows_the_offer_until_it_ends(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);

        $this->at(self::DURING);
        $this->get('/pricing?interval=month')->assertOk()
            ->assertSee('£1.99/mo for your first 3 months, then £3.99', false)
            ->assertSee('£3.99/mo for your first 3 months, then £7.99', false)
            ->assertSee('Launch offer ends 10 Feb 2027')
            ->assertSee('line-through', false);

        // Yearly prices keep their normal saving, with no offer price.
        $this->get('/pricing?interval=year')->assertOk()
            ->assertDontSee('for your first 3 months')
            ->assertSee('39.90');

        $this->at(self::AFTER);
        $this->get('/pricing?interval=month')->assertOk()
            ->assertDontSee('Launch offer')
            ->assertDontSee('for your first 3 months')
            ->assertSee('3.99');
    }

    public function test_home_page_plan_cards_show_the_offer(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);

        $this->at(self::DURING);
        $this->get('/')->assertOk()->assertSee('£1.99/mo for your first 3 months, then £3.99', false);

        $this->at(self::AFTER);
        $this->get('/')->assertOk()->assertDontSee('for your first 3 months');
    }

    public function test_terms_show_the_current_plan_prices(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);

        $this->get('/terms')->assertOk()
            ->assertSee('Photographer Plan (£3.99/mo)', false)
            ->assertSee('Videographer Plan (£7.99/mo)', false)
            ->assertDontSee('$9.99');
    }
}
