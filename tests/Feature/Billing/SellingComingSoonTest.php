<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Without Stripe Connect, photographers can't be paid, so selling prints is "coming soon". */
class SellingComingSoonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.connect_client_id' => null]);
    }

    public function test_payouts_page_says_coming_soon_with_no_connect_link(): void
    {
        $this->actingAs(User::factory()->create())->get(route('connect.stripe'))
            ->assertOk()
            ->assertSee('Selling prints is coming soon')
            ->assertDontSee('connect.stripe.com', false)
            ->assertDontSee('Connect with Stripe');
    }

    public function test_connect_callback_does_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('connect.stripe.callback', ['code' => 'ac_123', 'state' => 'x']))
            ->assertRedirect(route('connect.stripe'));
        $this->assertFalse((bool) $user->fresh()->stripe_connect_enabled);
    }

    public function test_connect_onboarding_shows_when_configured(): void
    {
        config(['services.stripe.connect_client_id' => 'ca_test']);

        $this->actingAs(User::factory()->create())->get(route('connect.stripe'))
            ->assertOk()->assertDontSee('Selling prints is coming soon');
    }
}
