<?php

namespace Tests\Feature\Prints;

use App\Jobs\SendPrintOrderEmail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Service\ProdigiOrderService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/** Clients hear when their print order is confirmed, and again with tracking when it ships. */
class PrintOrderEmailsTest extends PrintCatalogueTestCase
{
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $photographer = User::factory()->create(['name' => 'Ava Lens', 'email' => 'ava@example.com']);
        $this->order = Order::factory()->create([
            'user_id' => $photographer->id,
            'customer_email' => 'client@example.com',
            'customer_name' => 'Sam Client',
            'total' => 80,
            'photographer_amount' => 46,
            'status' => 'completed',
            'paid_at' => now(),
            'prodigi_order_id' => 'ord_123',
            'prodigi_status' => 'processing',
            'prodigi_submitted_at' => now(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => Product::factory()->create(['user_id' => $photographer->id, 'name' => '10x8 Fine Art Print', 'prodigi_sku' => 'GLOBAL-FAP-10x8'])->id,
            'quantity' => 2,
            'price' => 40,
            'prodigi_attributes' => ['color' => 'white'],
        ]);
    }

    private function sentMail(): array
    {
        $sent = [];
        Mail::getSymfonyTransport()->messages()->each(function ($m) use (&$sent) {
            $email = $m->getOriginalMessage();
            $sent[] = ['to' => $email->getTo()[0]->getAddress(), 'subject' => $email->getSubject(), 'html' => $email->getHtmlBody(), 'replyTo' => ($email->getReplyTo()[0] ?? null)?->getAddress()];
        });

        return $sent;
    }

    public function test_confirmation_goes_to_the_client_and_the_photographer_hears_about_the_sale(): void
    {
        config(['mail.default' => 'array']);

        (new SendPrintOrderEmail($this->order->id, SendPrintOrderEmail::CONFIRMED))->handle();

        [$client, $photographer] = $this->sentMail();
        $this->assertSame('client@example.com', $client['to']);
        $this->assertSame('ava@example.com', $client['replyTo']);
        $this->assertStringContainsString('confirmed', $client['subject']);
        $this->assertStringContainsString('2 × 10x8 Fine Art Print', $client['html']);
        $this->assertStringContainsString('80.00', $client['html']);
        $this->assertSame('ava@example.com', $photographer['to']);
        $this->assertStringContainsString('46.00', $photographer['html']);
    }

    public function test_shipping_is_detected_once_and_the_client_gets_tracking(): void
    {
        Bus::fake([SendPrintOrderEmail::class]);
        Http::fake([self::BASE.'/orders/ord_123' => Http::response(['outcome' => 'Ok', 'order' => [
            'id' => 'ord_123',
            'status' => ['stage' => 'Complete'],
            'shipments' => [[
                'carrier' => ['name' => 'Royal Mail', 'service' => 'Tracked 48'],
                'tracking' => ['number' => 'RM123456789GB', 'url' => 'https://www.royalmail.com/track-your-item#/tracking-results/RM123456789GB'],
                'status' => 'Shipped',
            ]],
        ]])]);

        app(ProdigiOrderService::class)->syncOrderStatus($this->order);
        app(ProdigiOrderService::class)->syncOrderStatus($this->order->fresh());

        $order = $this->order->fresh();
        $this->assertSame('shipped', $order->prodigi_status);
        $this->assertNotNull($order->prodigi_shipped_at);
        $this->assertSame('RM123456789GB', $order->prodigi_tracking_number);
        $this->assertSame('Royal Mail', $order->prodigi_carrier);
        Bus::assertDispatchedTimes(SendPrintOrderEmail::class, 1);
        Bus::assertDispatched(SendPrintOrderEmail::class, fn ($job) => $job->kind === SendPrintOrderEmail::SHIPPED);

        config(['mail.default' => 'array']);
        (new SendPrintOrderEmail($order->id, SendPrintOrderEmail::SHIPPED))->handle();
        [$mail] = $this->sentMail();
        $this->assertSame('client@example.com', $mail['to']);
        $this->assertStringContainsString('on their way', $mail['subject']);
        $this->assertStringContainsString('RM123456789GB', $mail['html']);
        $this->assertStringContainsString('Track your parcel', $mail['html']);
        $this->assertStringContainsString('Royal Mail', $mail['html']);
    }

    public function test_tracking_links_that_are_not_web_links_are_dropped(): void
    {
        Bus::fake([SendPrintOrderEmail::class]);
        Http::fake([self::BASE.'/orders/ord_123' => Http::response(['order' => [
            'status' => ['stage' => 'InProgress'],
            'shipments' => [['tracking' => ['number' => 'X1', 'url' => 'javascript:alert(1)']]],
        ]])]);

        app(ProdigiOrderService::class)->syncOrderStatus($this->order);

        $order = $this->order->fresh();
        $this->assertNull($order->prodigi_tracking_url);
        $this->assertNotNull($order->prodigi_shipped_at, 'a shipment with tracking has been posted');
    }

    public function test_no_emails_for_unpaid_orders(): void
    {
        config(['mail.default' => 'array']);
        $this->order->update(['paid_at' => null]);

        (new SendPrintOrderEmail($this->order->id, SendPrintOrderEmail::CONFIRMED))->handle();

        $this->assertSame([], $this->sentMail());
    }
}
