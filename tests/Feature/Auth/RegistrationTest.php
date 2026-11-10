<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test-user',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_username_must_be_url_safe(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test user/1',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_plan_chosen_on_pricing_page_leads_to_checkout_after_sign_up(): void
    {
        $plan = \App\Models\SubscriptionPlan::factory()->create(['active' => true]);

        $this->get('/register?plan='.$plan->id.'&interval=year')
            ->assertOk()
            ->assertSee("You're signing up for the", false);

        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'test-user',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('checkout', ['plan' => $plan->id, 'interval' => 'year']));
    }
}
