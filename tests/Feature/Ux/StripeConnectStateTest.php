<?php

namespace Tests\Feature\Ux;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeConnectStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_link_includes_a_state_that_the_callback_checks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('connect.stripe'))
            ->assertOk()
            ->assertViewHas('connectUrl', fn ($url) => str_contains($url, 'state='));

        // A callback with the wrong state is refused before talking to Stripe.
        $this->actingAs($user)->get(route('connect.stripe.callback', ['code' => 'ac_123', 'state' => 'forged']))
            ->assertRedirect(route('connect.stripe'));

        $this->assertFalse((bool) $user->fresh()->stripe_connect_enabled);
    }
}
