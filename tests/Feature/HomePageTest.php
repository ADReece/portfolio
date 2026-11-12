<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Carbon\CarbonImmutable;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        config(['billing.launch_offer.coupon_id' => 'LAUNCH50', 'billing.launch_offer.ends_at' => '2027-02-10T23:59:59+00:00']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();
        parent::tearDown();
    }

    private function at(string $time): void
    {
        CarbonImmutable::setTestNow($time);
        \Illuminate\Support\Carbon::setTestNow($time);
    }

    /** @return list<array> the page's JSON-LD blocks, decoded (fails on invalid JSON) */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m[1], 'page has JSON-LD');

        return array_map(fn ($json) => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $m[1]);
    }

    private function block(array $blocks, string $type): array
    {
        $found = collect($blocks)->firstWhere('@type', $type);
        $this->assertNotNull($found, "JSON-LD has a {$type}");

        return $found;
    }

    public function test_offer_is_in_the_server_rendered_html_and_meta_during_the_offer(): void
    {
        $this->at('2026-11-10 12:00:00');
        $html = $this->get('/')->assertOk()->getContent();

        $line = 'Launch offer: 50% off your first 3 months, sign up by 10 Feb 2027';
        $this->assertStringContainsString($line, $html);
        $this->assertStringContainsString('Launch offer: half price for your first 3 months', $html);
        $this->assertStringContainsString('Start for £1.99/month', $html);
        // Full price struck through, offer price next to it.
        $this->assertMatchesRegularExpression('#line-through[^>]*>.*?£3\.99</span>\s*<span[^>]*>£1\.99#s', $html);
        $this->assertMatchesRegularExpression('#line-through[^>]*>.*?£7\.99</span>\s*<span[^>]*>£3\.99#s', $html);

        foreach (['name="description"', 'property="og:description"', 'name="twitter:description"'] as $meta) {
            $this->assertMatchesRegularExpression('#<meta '.preg_quote($meta, '#').' content="[^"]*'.preg_quote(e($line), '#').'#', $html, $meta);
        }
    }

    public function test_json_ld_has_full_price_and_launch_offers_and_the_faq(): void
    {
        $this->at('2026-11-10 12:00:00');
        $blocks = $this->jsonLd($this->get('/')->getContent());

        $offers = collect($this->block($blocks, 'SoftwareApplication')['offers']);
        foreach (['photographer' => ['3.99', '1.99'], 'videographer' => ['7.99', '3.99']] as $slug => [$full, $launch]) {
            $name = SubscriptionPlan::where('slug', $slug)->value('name');
            $regular = $offers->firstWhere('name', "{$name} plan (monthly)");
            $this->assertSame($full, $regular['price']);
            $this->assertSame('GBP', $regular['priceCurrency']);
            $this->assertArrayNotHasKey('priceValidUntil', $regular);

            $offer = $offers->firstWhere('name', "{$name} plan launch offer");
            $this->assertSame($launch, $offer['price']);
            $this->assertSame('GBP', $offer['priceCurrency']);
            $this->assertSame('2027-02-10', $offer['priceValidUntil']);
            $this->assertSame('50% off the first 3 months', $offer['description']);
        }

        $questions = collect($this->block($blocks, 'FAQPage')['mainEntity'])->pluck('name');
        $this->assertContains('Is there a launch discount?', $questions);
    }

    public function test_faq_json_ld_matches_the_visible_faq(): void
    {
        $this->at('2026-11-10 12:00:00');
        $html = $this->get('/')->getContent();

        foreach ($this->block($this->jsonLd($html), 'FAQPage')['mainEntity'] as $q) {
            $this->assertStringContainsString('<h3>'.e($q['name']).'</h3>', $html);
            $this->assertStringContainsString(e($q['acceptedAnswer']['text']), $html);
        }
    }

    public function test_everything_about_the_offer_disappears_after_it_ends(): void
    {
        $this->at('2027-02-11 00:00:01');
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('Launch offer', $html);
        $this->assertStringNotContainsString('launch discount', $html);
        $this->assertStringNotContainsString('£1.99', $html);
        $this->assertStringContainsString('Start free', $html);

        $blocks = $this->jsonLd($html);
        $offers = collect($this->block($blocks, 'SoftwareApplication')['offers']);
        $this->assertTrue($offers->every(fn ($o) => ! isset($o['priceValidUntil'])));
        $this->assertSame(['3.99', '7.99'], $offers->pluck('price')->all());
        $this->assertNotContains('Is there a launch discount?', collect($this->block($blocks, 'FAQPage')['mainEntity'])->pluck('name'));
        $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]*Paid plans from £3\.99/month#', $html);
    }

    public function test_offer_still_shows_on_its_last_day(): void
    {
        $this->at('2027-02-10 23:59:00');

        $this->get('/')->assertSee('Launch offer: 50% off your first 3 months, sign up by 10 Feb 2027');
    }

    public function test_no_old_prices_anywhere_public(): void
    {
        $this->at('2026-11-10 12:00:00');

        foreach (['/', '/pricing', '/terms', '/examples/weddings'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            foreach (['9.99', '14.99', '99.99', '139.99'] as $old) {
                $this->assertDoesNotMatchRegularExpression('#(?<![\d.])'.preg_quote($old, '#').'(?!\d)#', $html, "{$path} mentions {$old}");
            }
        }
    }

    public function test_coming_soon_features_are_labelled(): void
    {
        $html = $this->get('/')->getContent();

        // Video galleries have shipped: no longer labelled.
        $this->assertDoesNotMatchRegularExpression('#Video galleries\s*<span[^>]*>Coming soon#', $html);
        $this->assertMatchesRegularExpression('#Sell prints\s*<span[^>]*>Coming soon#', $html);
        // Custom domains have shipped: no longer labelled.
        $this->assertStringContainsString('use your own domain, like yourname.com', $html);
        $this->assertDoesNotMatchRegularExpression('#Coming soon</span>\s*Use your own domain#', $html);
    }

    public function test_primary_and_secondary_calls_to_action(): void
    {
        $this->at('2026-11-10 12:00:00');
        $photographer = SubscriptionPlan::where('slug', 'photographer')->first();

        $this->get('/')
            ->assertSee(e(route('register', ['plan' => $photographer->id, 'interval' => 'month'])), false)
            ->assertSee('See a sample portfolio')
            ->assertSee(route('examples.show', 'weddings'), false)
            ->assertSee('no card needed');
    }

    public function test_sample_portfolios_and_photo_credits(): void
    {
        foreach (array_keys(config('marketing.showcases')) as $slug) {
            $this->get(route('examples.show', $slug))->assertOk()->assertSee('This is a sample portfolio');
        }
        $this->get('/examples/not-a-sample')->assertNotFound();
        $this->get(route('photo-credits'))->assertOk()->assertSee('Pexels License');
    }
}
