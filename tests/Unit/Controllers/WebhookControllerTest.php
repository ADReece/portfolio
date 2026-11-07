<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\WebhookController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Calls the payment-intent handlers directly against real orders. (These
 * previously used Mockery alias mocks of App\Models\Order, which replace the
 * class for the whole PHP process and broke every later test that used it.)
 */
class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_intent_failed_marks_order_failed(): void
    {
        $order = Order::factory()->create(['stripe_payment_intent_id' => 'pi_failed_123']);

        $response = (new WebhookController())->handlePaymentIntentFailed([
            'data' => ['object' => ['id' => 'pi_failed_123']],
        ]);

        $this->assertSame('failed', $order->fresh()->status);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_payment_intent_succeeded_marks_order_completed_when_found(): void
    {
        $photographer = User::factory()->create(['stripe_connect_enabled' => false]);
        $order = Order::factory()->create([
            'user_id' => $photographer->id,
            'stripe_payment_intent_id' => 'pi_success_123',
        ]);

        $response = (new WebhookController())->handlePaymentIntentSucceeded([
            'data' => ['object' => ['id' => 'pi_success_123']],
        ]);

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
