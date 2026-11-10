<?php

namespace Tests\Feature\Billing;

use App\Http\Controllers\WebhookController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookPaymentIntentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_intent_succeeded_marks_order_completed_and_sets_paid_at(): void
    {
        $user = User::factory()->create([
            'stripe_connect_enabled' => false,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'Buyer',
            'total' => 25.00,
            'platform_fee' => 2.50,
            'photographer_amount' => 22.50,
            'stripe_payment_intent_id' => 'pi_test_success_1',
            'status' => 'pending',
        ]);

        $controller = app(WebhookController::class);
        $response = $controller->handlePaymentIntentSucceeded([
            'data' => [
                'object' => [
                    'id' => 'pi_test_success_1',
                ],
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_payment_intent_failed_marks_order_as_failed(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'Buyer',
            'total' => 50.00,
            'platform_fee' => 5.00,
            'photographer_amount' => 45.00,
            'stripe_payment_intent_id' => 'pi_test_failed_1',
            'status' => 'pending',
        ]);

        $controller = app(WebhookController::class);
        $response = $controller->handlePaymentIntentPaymentFailed([
            'data' => [
                'object' => [
                    'id' => 'pi_test_failed_1',
                ],
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('failed', $order->fresh()->status);
    }

    public function test_payment_intent_webhooks_ignore_unknown_payment_intents(): void
    {
        $controller = app(WebhookController::class);

        $successResponse = $controller->handlePaymentIntentSucceeded([
            'data' => [
                'object' => [
                    'id' => 'pi_does_not_exist',
                ],
            ],
        ]);

        $failedResponse = $controller->handlePaymentIntentPaymentFailed([
            'data' => [
                'object' => [
                    'id' => 'pi_does_not_exist',
                ],
            ],
        ]);

        $this->assertSame(200, $successResponse->getStatusCode());
        $this->assertSame(200, $failedResponse->getStatusCode());
        $this->assertSame(0, Order::query()->count());
    }
}
