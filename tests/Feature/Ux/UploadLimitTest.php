<?php

namespace Tests\Feature\Ux;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_over_limit_returns_readable_json_with_upgrade_link(): void
    {
        $plan = SubscriptionPlan::factory()->create(['photo_limit' => 0]);
        $user = User::factory()->create(['subscription_plan_id' => $plan->id]);

        $this->actingAs($user)
            ->post(route('upload-files'), [])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => "You've used all 0 photos on your plan. Upgrade to upload more.",
                'upgrade_url' => route('pricing'),
            ]);
    }

    public function test_upload_without_plan_explains_what_to_do(): void
    {
        $this->actingAs(User::factory()->create(['subscription_plan_id' => null]))
            ->post(route('upload-files'), [])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Choose a plan to start uploading photos.');
    }
}
