<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeConnectStripe;
use Tests\TestCase;

/** Without Portfolio's Connect keys, photographers can't be paid, so selling is "coming soon". */
class SellingComingSoonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.portfolio_connect.secret' => null, 'services.portfolio_connect.webhook_secret' => null]);
    }

    public function test_payouts_page_says_coming_soon_with_no_setup_button(): void
    {
        $this->actingAs(User::factory()->create())->get(route('connect.stripe'))
            ->assertOk()
            ->assertSee('Selling prints is coming soon')
            ->assertDontSee('Set up payouts</button>', false)
            ->assertDontSee(route('connect.stripe.onboard'));
    }

    public function test_onboarding_and_webhook_are_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('connect.stripe.onboard'))->assertNotFound();
        $this->actingAs($user)->get(route('connect.stripe.return'))->assertRedirect(route('connect.stripe'));
        $this->postJson(route('connect.webhook'), ['type' => 'account.updated'])->assertNotFound();
        $this->assertNull($user->fresh()->stripe_connect_id);
    }

    public function test_a_secret_without_its_webhook_secret_keeps_selling_off(): void
    {
        config(['services.portfolio_connect.secret' => 'sk_test_x']);

        $this->assertFalse(\App\Billing\Payouts::available());
    }

    public function test_homepage_labels_prints_coming_soon_until_the_keys_are_set(): void
    {
        $this->assertMatchesRegularExpression('#Sell prints\s*<span[^>]*>Coming soon#', $this->get('/')->getContent());

        FakeConnectStripe::install($this->app);
        $html = $this->get('/')->getContent();
        $this->assertDoesNotMatchRegularExpression('#Sell prints\s*<span[^>]*>Coming soon#', $html);
        $this->assertStringContainsString('Sell prints and downloads', $html);
        $this->assertStringNotContainsString('Print sales are coming soon', $html);
    }

    public function test_onboarding_shows_when_configured(): void
    {
        FakeConnectStripe::install($this->app);

        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('connect.stripe'))
            ->assertOk()->assertDontSee('Selling prints is coming soon')->assertSee('Set up payouts');
    }
}
