<?php

namespace Tests\Feature\Admin;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlansAndSubscribersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function subscribe(string $status, $endsAt = null): User
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => $status,
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'ends_at' => $endsAt,
        ]);

        return $user;
    }

    public function test_removing_every_feature_clears_the_plan_feature_list(): void
    {
        $plan = SubscriptionPlan::factory()->create(['features' => ['Unlimited galleries', 'Print sales']]);

        $this->actingAs($this->admin())
            ->put(route('admin.plans.update', $plan), [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'sort_order' => $plan->sort_order,
                // no features[]: the form sends nothing when every feature is removed
            ])
            ->assertRedirect(route('admin.plans.index'));

        $this->assertSame([], $plan->fresh()->features);
    }

    public function test_dashboard_counts_only_paid_up_subscribers_and_shows_past_due_separately(): void
    {
        $this->subscribe('active');
        $this->subscribe('trialing');
        $this->subscribe('active', now()->addDays(5));  // cancelled, still in paid period
        $this->subscribe('active', now()->subDay());    // ended
        $this->subscribe('past_due');
        $this->subscribe('incomplete');

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertViewHas('activeSubscribers', 3)
            ->assertViewHas('pastDueSubscribers', 1)
            ->assertSee('1 more past due (payment failed)');
    }
}
