<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeConnectStripe;
use Tests\TestCase;

/** Photographers set up payouts with a Stripe Express account on Portfolio's Connect platform. */
class PayoutsOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private FakeConnectStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = FakeConnectStripe::install($this->app);
    }

    private function photographer(array $attributes = []): User
    {
        return User::factory()->create(['is_admin' => true] + $attributes); // admins have every feature
    }

    public function test_setting_up_creates_an_express_account_and_sends_them_to_stripe(): void
    {
        $user = $this->photographer();

        $this->actingAs($user)->post(route('connect.stripe.onboard'))
            ->assertRedirect('https://connect.stripe.test/setup/acct_new');

        [$account] = $this->stripe->paramsFor('accounts.create');
        $this->assertSame('express', $account['type']);
        $this->assertSame('GB', $account['country']);
        $this->assertSame($user->email, $account['email']);
        $this->assertTrue($account['capabilities']['card_payments']['requested']);
        $this->assertTrue($account['capabilities']['transfers']['requested']);
        $this->assertSame(['app' => 'portfolio', 'user_id' => (string) $user->id], $account['metadata']);

        [$link] = $this->stripe->paramsFor('accountLinks.create');
        $this->assertSame('account_onboarding', $link['type']);
        $this->assertSame(route('connect.stripe.return'), $link['return_url']);
        $this->assertSame(route('connect.stripe.refresh'), $link['refresh_url']);

        $user->refresh();
        $this->assertSame('acct_new', $user->stripe_connect_id);
        $this->assertFalse($user->stripe_connect_enabled, 'not until Stripe says the account can be paid');
    }

    public function test_continuing_setup_reuses_the_account(): void
    {
        $user = $this->photographer(['stripe_connect_id' => 'acct_existing']);

        $this->actingAs($user)->post(route('connect.stripe.onboard'))->assertRedirect('https://connect.stripe.test/setup/acct_existing');
        $this->actingAs($user)->get(route('connect.stripe.refresh'))->assertRedirect('https://connect.stripe.test/setup/acct_existing');

        $this->assertSame([], $this->stripe->paramsFor('accounts.create'));
    }

    public function test_returning_turns_selling_on_only_once_stripe_can_pay_them(): void
    {
        $user = $this->photographer(['stripe_connect_id' => 'acct_1']);

        $this->stripe->account = ['charges_enabled' => true, 'payouts_enabled' => false];
        $this->actingAs($user)->get(route('connect.stripe.return'))->assertRedirect(route('connect.stripe'))->assertSessionHas('error');
        $this->assertFalse($user->fresh()->stripe_connect_enabled);
        $this->actingAs($user)->get(route('connect.stripe'))->assertSee('Continue setup');

        $this->stripe->account = ['charges_enabled' => true, 'payouts_enabled' => true];
        $this->actingAs($user)->get(route('connect.stripe.return'))->assertSessionHas('success');
        $this->assertTrue($user->fresh()->stripe_connect_enabled);
        $this->actingAs($user)->get(route('connect.stripe'))->assertSee('Payouts are set up')->assertSee('Payouts and bank details');
    }

    public function test_dashboard_opens_their_express_dashboard(): void
    {
        $user = $this->photographer(['stripe_connect_id' => 'acct_1', 'stripe_connect_enabled' => true]);

        $this->actingAs($user)->post(route('connect.stripe.dashboard'))->assertRedirect('https://connect.stripe.test/express/acct_1');
        $this->actingAs($this->photographer())->post(route('connect.stripe.dashboard'))->assertNotFound();
    }

    public function test_stripe_errors_are_shown_politely(): void
    {
        $this->stripe->fail = true;

        $this->actingAs($this->photographer())->post(route('connect.stripe.onboard'))
            ->assertRedirect(route('connect.stripe'))->assertSessionHas('error');
    }

    public function test_turning_off_payouts_unlinks_the_account(): void
    {
        $user = $this->photographer(['stripe_connect_id' => 'acct_1', 'stripe_connect_enabled' => true]);

        $this->actingAs($user)->post(route('connect.stripe.disconnect'))->assertRedirect(route('connect.stripe'));

        $user->refresh();
        $this->assertNull($user->stripe_connect_id);
        $this->assertFalse($user->stripe_connect_enabled);
    }

    public function test_plans_without_selling_and_the_demo_cannot_onboard(): void
    {
        $this->actingAs(User::factory()->create())->post(route('connect.stripe.onboard'))->assertSessionHas('error');
        $this->actingAs($this->photographer(['is_demo' => true]))->post(route('connect.stripe.onboard'))->assertForbidden();
        $this->assertSame([], $this->stripe->paramsFor('accounts.create'));
    }
}
