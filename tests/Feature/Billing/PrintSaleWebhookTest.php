<?php

namespace Tests\Feature\Billing;

use App\Jobs\DeliverDigitalOrder;
use App\Jobs\SendPrintOrderEmail;
use App\Jobs\SubmitOrderToProdigi;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Support\FakeConnectStripe;
use Tests\TestCase;

/** Webhooks from Portfolio's Connect platform: /stripe/connect/webhook, signed with its own secret. */
class PrintSaleWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SubmitOrderToProdigi::class, SendPrintOrderEmail::class, DeliverDigitalOrder::class]);
        FakeConnectStripe::install($this->app);
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

    private function send(array $event, ?string $secret = 'whsec_test_connect'): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode(['id' => 'evt_1', 'object' => 'event'] + $event);
        $time = time();
        $signature = 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$payload, (string) $secret);

        return $this->call('POST', route('connect.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);
    }

    private function paymentEvent(string $type, array $metadata = ['app' => 'portfolio']): array
    {
        return ['type' => $type, 'data' => ['object' => [
            'id' => 'pi_123', 'object' => 'payment_intent', 'transfer' => 'tr_dest', 'metadata' => $metadata,
        ]]];
    }

    public function test_paid_print_order_is_completed_sent_to_prodigi_and_confirmed_once(): void
    {
        $order = $this->paidOrder();

        $this->send($this->paymentEvent('payment_intent.succeeded'))->assertOk();
        // Stripe retries webhooks: a second delivery must not repeat anything.
        $this->send($this->paymentEvent('payment_intent.succeeded'))->assertOk();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('tr_dest', $order->stripe_transfer_id, 'the destination charge\'s transfer');
        Bus::assertDispatchedTimes(SubmitOrderToProdigi::class, 1);
        Bus::assertDispatched(SendPrintOrderEmail::class, fn ($job) => $job->orderId === $order->id && $job->kind === SendPrintOrderEmail::CONFIRMED);
        Bus::assertDispatchedTimes(SendPrintOrderEmail::class, 1);
    }

    public function test_bad_signatures_and_other_apps_payments_are_ignored(): void
    {
        $order = $this->paidOrder();

        $this->send($this->paymentEvent('payment_intent.succeeded'), 'whsec_wrong')->assertForbidden();
        $this->call('POST', route('connect.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')->assertForbidden();
        $this->send($this->paymentEvent('payment_intent.succeeded', ['app' => 'other']))->assertOk();

        $this->assertNull($order->fresh()->paid_at);
        Bus::assertNotDispatched(SubmitOrderToProdigi::class);
    }

    public function test_failed_payment_marks_an_unpaid_order_failed_but_never_undoes_a_paid_one(): void
    {
        $order = $this->paidOrder();
        $this->send($this->paymentEvent('payment_intent.payment_failed'))->assertOk();
        $this->assertSame('failed', $order->fresh()->status);

        $order->update(['status' => 'pending']);
        $this->send($this->paymentEvent('payment_intent.succeeded'))->assertOk();
        $this->send($this->paymentEvent('payment_intent.payment_failed'))->assertOk();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_full_refund_marks_the_order_refunded(): void
    {
        $order = $this->paidOrder();
        $this->send($this->paymentEvent('payment_intent.succeeded'));

        $this->send(['type' => 'charge.refunded', 'data' => ['object' => ['id' => 'ch_1', 'object' => 'charge', 'payment_intent' => 'pi_123', 'refunded' => false]]]);
        $this->assertSame('completed', $order->fresh()->status, 'partial refunds keep the order');

        $this->send(['type' => 'charge.refunded', 'data' => ['object' => ['id' => 'ch_1', 'object' => 'charge', 'payment_intent' => 'pi_123', 'refunded' => true]]]);
        $this->assertSame('refunded', $order->fresh()->status);
    }

    public function test_paid_download_order_is_delivered(): void
    {
        $order = $this->paidOrder();
        $order->update(['kind' => Order::DIGITAL]);

        $this->send($this->paymentEvent('payment_intent.succeeded'))->assertOk();

        Bus::assertDispatched(DeliverDigitalOrder::class, fn ($job) => $job->orderId === $order->id);
        Bus::assertNotDispatched(SubmitOrderToProdigi::class);
    }

    public function test_account_updates_turn_selling_on_and_off(): void
    {
        $user = User::factory()->create(['stripe_connect_id' => 'acct_9']);
        $other = User::factory()->create(['stripe_connect_id' => 'acct_other', 'stripe_connect_enabled' => true]);
        $account = fn (bool $payouts) => ['type' => 'account.updated', 'account' => 'acct_9', 'data' => ['object' => [
            'id' => 'acct_9', 'object' => 'account', 'charges_enabled' => true, 'payouts_enabled' => $payouts,
        ]]];

        $this->send($account(true))->assertOk();
        $this->assertTrue($user->fresh()->stripe_connect_enabled);

        // e.g. Stripe needs more verification: selling pauses until it's done.
        $this->send($account(false))->assertOk();
        $this->assertFalse($user->fresh()->stripe_connect_enabled);
        $this->assertTrue($other->fresh()->stripe_connect_enabled);

        $this->send(['type' => 'account.application.deauthorized', 'account' => 'acct_other', 'data' => ['object' => ['id' => 'ca_1', 'object' => 'application']]])->assertOk();
        $this->assertNull($other->fresh()->stripe_connect_id);
    }
}
