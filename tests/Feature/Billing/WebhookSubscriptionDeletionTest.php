<?php

namespace Tests\Feature\Billing;

use App\Http\Controllers\WebhookController;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSubscriptionDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_deleted_webhook_downgrades_user_to_free_plan_and_cancels_subscription(): void
    {
        $freePlan = SubscriptionPlan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'annual_price' => 0,
            'annual_discount_percent' => 0,
            'photo_limit' => 100,
            'collection_limit' => 5,
            'private_collections' => false,
            'watermarking' => false,
            'selling' => false,
            'video_upload' => false,
            'custom_templates' => false,
            'upload_logo' => false,
            'features' => ['Basic'],
            'active' => true,
            'sort_order' => 1,
            'recommended' => false,
        ]);

        $paidPlan = SubscriptionPlan::create([
            'name' => 'Photographer',
            'slug' => 'photographer',
            'stripe_price_id' => 'price_monthly_123',
            'price' => 9.99,
            'annual_price' => 99.99,
            'annual_discount_percent' => 17,
            'photo_limit' => null,
            'collection_limit' => null,
            'private_collections' => true,
            'watermarking' => true,
            'selling' => true,
            'video_upload' => false,
            'custom_templates' => true,
            'upload_logo' => true,
            'features' => ['Pro'],
            'active' => true,
            'sort_order' => 2,
            'recommended' => true,
        ]);

        $user = User::factory()->create([
            'subscription_plan_id' => $paidPlan->id,
        ]);
        $user->forceFill(['stripe_id' => 'cus_test_123'])->save();

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly_123',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $controller = app(WebhookController::class);
        $response = $controller->handleCustomerSubscriptionDeleted([
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'id' => 'sub_test_123',
                ],
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($freePlan->id, $user->fresh()->subscription_plan_id);
        $this->assertNotNull($subscription->fresh()->ends_at);
    }
}
