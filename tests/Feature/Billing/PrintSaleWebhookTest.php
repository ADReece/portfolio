<?php

namespace Tests\Feature\Billing;

use App\Http\Controllers\WebhookController;
use App\Jobs\SubmitOrderToProdigi;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Stripe\StripeClient;
use Tests\TestCase;

class PrintSaleWebhookTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{0: array, 1: array}> */
    private array $transfers = [];

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SubmitOrderToProdigi::class]);
        \App\Models\Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'GBP']);
        \App\Support\Currency::flush();

        $test = $this;
        $this->app->instance(StripeClient::class, new class($test) {
            public object $transfers;

            public function __construct($test)
            {
                $this->transfers = new class($test) {
                    public function __construct(private $test) {}

                    public function create(array $params, array $opts = [])
                    {
                        $this->test->recordTransfer($params, $opts);

                        return (object) ['id' => 'tr_'.count($this->test->transfersMade())];
                    }
                };
            }
        });
    }

    public function recordTransfer(array $params, array $opts): void
    {
        $this->transfers[] = [$params, $opts];
    }

    public function transfersMade(): array
    {
        return $this->transfers;
    }

    private function paidOrder(): Order
    {
        $photographer = User::factory()->create();
        $photographer->forceFill(['stripe_connect_id' => 'acct_123', 'stripe_connect_enabled' => true])->save();

        $order = Order::factory()->create([
            'user_id' => $photographer->id,
            'total' => 13.72,
            'platform_fee' => 1.37,
            'photographer_amount' => 12.35,
            'stripe_payment_intent_id' => 'pi_123',
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => \App\Models\Product::factory()->create(['user_id' => $photographer->id])->id,
        ]);

        return $order;
    }

    private function event(string $type): array
    {
        return ['type' => $type, 'data' => ['object' => [
            'id' => 'pi_123',
            'latest_charge' => 'ch_123',
            'metadata' => ['app' => 'portfolio'],
        ]]];
    }

    public function test_paid_print_order_is_completed_sent_to_prodigi_and_photographer_paid_once(): void
    {
        $order = $this->paidOrder();
        $controller = app(WebhookController::class);

        $controller->handlePaymentIntentSucceeded($this->event('payment_intent.succeeded'));
        // Stripe retries webhooks: a second delivery must not repeat anything.
        $controller->handlePaymentIntentSucceeded($this->event('payment_intent.succeeded'));

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('tr_1', $order->stripe_transfer_id);
        Bus::assertDispatchedTimes(SubmitOrderToProdigi::class, 1);

        $this->assertCount(1, $this->transfers);
        [$params, $opts] = $this->transfers[0];
        $this->assertSame(1235, $params['amount'], 'whole pence, not 1234.999…');
        $this->assertSame('gbp', $params['currency']);
        $this->assertSame('acct_123', $params['destination']);
        $this->assertSame('ch_123', $params['source_transaction']);
        $this->assertSame('order-transfer-'.$order->id, $opts['idempotency_key']);
    }

    public function test_failed_payment_event_reaches_its_handler_and_marks_the_order_failed(): void
    {
        $order = $this->paidOrder();

        $response = $this->postJson('/stripe/webhook', $this->event('payment_intent.payment_failed'));

        $response->assertOk();
        $this->assertSame('failed', $order->fresh()->status);
    }

    public function test_failed_event_does_not_undo_a_paid_order(): void
    {
        $order = $this->paidOrder();
        app(WebhookController::class)->handlePaymentIntentSucceeded($this->event('payment_intent.succeeded'));

        $this->postJson('/stripe/webhook', $this->event('payment_intent.payment_failed'))->assertOk();

        $this->assertSame('completed', $order->fresh()->status);
    }
}
