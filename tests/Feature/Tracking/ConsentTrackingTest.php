<?php

namespace Tests\Feature\Tracking;

use App\Crm\CrmReporter;
use App\Models\CustomDomain;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Tracking\Tracking;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsentTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER = [
        'name' => 'Steph Shoots', 'username' => 'stephshoots', 'email' => 'steph@example.com',
        'password' => 'Sup3r-secret-pass!', 'password_confirmation' => 'Sup3r-secret-pass!',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        config(['app.url' => 'https://portfolio.ascend-digital.co.uk']);
    }

    private function withIds(): void
    {
        config([
            'tracking.ga4_measurement_id' => 'G-TEST123',
            'tracking.google_ads_id' => 'AW-999',
            'tracking.google_ads_signup_label' => 'signupLbl',
            'tracking.google_ads_purchase_label' => 'buyLbl',
            'tracking.meta_pixel_id' => '1234567890',
        ]);
    }

    public function test_nothing_renders_without_ids(): void
    {
        $html = $this->get('https://portfolio.ascend-digital.co.uk/')->assertOk()->getContent();

        $this->assertStringNotContainsString('cookie-banner', $html);
        $this->assertStringNotContainsString('googletagmanager', $html);
        $this->assertStringNotContainsString('fbevents', $html);
        $this->assertStringNotContainsString('Cookie settings', $html);
    }

    public function test_with_ids_nothing_loads_before_consent(): void
    {
        $this->withIds();
        $html = $this->get('https://portfolio.ascend-digital.co.uk/')->assertOk()->getContent();

        // The banner, with Accept and Reject as equal buttons, and consent denied by default.
        $this->assertStringContainsString('id="cookie-banner"', $html);
        $this->assertMatchesRegularExpression('#data-consent="granted" class="([^"]+)">Accept</button>\s*<button type="button" data-consent="denied" class="\1">Reject</button>#', $html);
        $this->assertStringContainsString("gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied' })", $html);
        // No third-party script tag in the page itself: they're only added by JS after "Accept".
        $this->assertDoesNotMatchRegularExpression('#<script[^>]+src="https://(www\.googletagmanager\.com|connect\.facebook\.net)#', $html);
        $this->assertStringContainsString('"ga4":"G-TEST123"', $html);
        $this->assertStringContainsString('Cookie settings', $html);
        $this->assertStringContainsString('https://www.googletagmanager.com', $this->get('/')->headers->get('Content-Security-Policy') ?? $this->get('/')->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_never_on_photographers_pages_or_custom_domains(): void
    {
        $this->withIds();
        $steph = User::factory()->create(['username' => 'stephshoots', 'subscription_plan_id' => SubscriptionPlan::where('slug', 'photographer')->value('id')]);

        $this->get('https://portfolio.ascend-digital.co.uk/@stephshoots')->assertOk()->assertDontSee('cookie-banner')->assertDontSee('G-TEST123');

        CustomDomain::factory()->verified()->create(['user_id' => $steph->id, 'hostname' => 'stephshoots.com']);
        $html = $this->get('https://stephshoots.com/')->assertOk()->getContent();
        $this->assertStringNotContainsString('cookie-banner', $html);
        $this->assertStringNotContainsString('G-TEST123', $html);
        $this->assertStringNotContainsString('gtag(', $html);
    }

    public function test_sign_up_event_renders_once_and_utms_are_saved(): void
    {
        $this->withIds();
        $this->get('https://portfolio.ascend-digital.co.uk/?utm_source=google&utm_campaign=launch&gclid=abc123');
        $this->get('https://portfolio.ascend-digital.co.uk/pricing?utm_source=later'); // first touch wins

        $this->post('https://portfolio.ascend-digital.co.uk/register', self::REGISTER)->assertRedirect();
        $user = User::where('email', 'steph@example.com')->sole();

        $this->assertSame('google', $user->attribution['utm_source']);
        $this->assertSame('launch', $user->attribution['utm_campaign']);
        $this->assertSame('abc123', $user->attribution['gclid']);
        $this->assertSame('/', $user->attribution['landing_page']);

        $first = $this->actingAs($user)->get('https://portfolio.ascend-digital.co.uk/dashboard')->assertOk()->getContent();
        $this->assertSame(1, substr_count($first, '"name":"sign_up"'));
        $this->assertStringContainsString('"id":"signup-'.$user->id.'"', $first);

        $second = $this->actingAs($user)->get('https://portfolio.ascend-digital.co.uk/dashboard')->getContent();
        $this->assertStringNotContainsString('"name":"sign_up"', $second);
    }

    public function test_begin_checkout_and_purchase_events(): void
    {
        $this->withIds();
        $user = User::factory()->create();
        $plan = SubscriptionPlan::where('slug', 'photographer')->first();

        $html = $this->actingAs($user)->get(route('checkout', ['plan' => $plan->id, 'interval' => 'month']))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '"name":"begin_checkout"'));
        $this->assertStringContainsString('"plan":"photographer","interval":"month"', $html);

        // purchase: queued with the Stripe id after checkout, shown once on the next page.
        Tracking::queue('purchase', 'sub_123', ['transaction_id' => 'sub_123', 'currency' => 'GBP', 'value' => 1.99, 'coupon' => 'LAUNCH50']);
        Tracking::queue('purchase', 'sub_123', ['transaction_id' => 'sub_123', 'currency' => 'GBP', 'value' => 1.99, 'coupon' => 'LAUNCH50']);
        $page = $this->actingAs($user)->get('https://portfolio.ascend-digital.co.uk/dashboard')->getContent();
        $this->assertSame(1, substr_count($page, '"transaction_id":"sub_123"'));
        $this->assertStringContainsString('"coupon":"LAUNCH50"', $page);
    }

    public function test_crm_account_created_carries_attribution(): void
    {
        config(['crm.url' => 'https://crm.test', 'crm.token' => 'crm-secret']);
        Http::fake(['https://crm.test/*' => Http::response([], 202)]);
        $this->get('https://portfolio.ascend-digital.co.uk/?utm_source=meta&fbclid=fb1');

        $this->post('https://portfolio.ascend-digital.co.uk/register', self::REGISTER);

        Http::assertSent(fn ($r) => $r['type'] === 'account.created'
            && $r['attribution']['utm_source'] === 'meta'
            && $r['attribution']['fbclid'] === 'fb1'
            && $r['event_id'] === CrmReporter::createdEventId(User::where('email', 'steph@example.com')->sole()));
    }

    public function test_events_are_not_queued_when_tracking_is_off(): void
    {
        Tracking::queue('sign_up', 'x');
        $this->assertSame([], Tracking::pull());
    }
}
