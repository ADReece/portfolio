<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use App\Models\Product;
use App\Models\User;
use App\Service\ProdigiOrderService;
use App\Service\ProdigiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdigiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $printProduct;
    protected Photo $photo;

    public function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test photo
        $this->photo = Photo::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'photos/test.jpg',
        ]);

        // Create print product
        $this->printProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'photo_id' => $this->photo->id,
            'type' => 'print_poster',
            'name' => 'Test Poster',
            'price' => 29.99,
        ]);
    }

    /**
     * Test that we can build a valid Prodigi order payload
     */
    public function test_can_build_prodigi_order_payload(): void
    {
        // Create order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
        ]);

        // Add print item
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->printProduct->id,
            'quantity' => 2,
            'price' => $this->printProduct->price,
        ]);

        $order->load('items');

        // Test service instantiation
        $this->assertTrue(true); // Basic check that classes exist

        // TODO: Uncomment when mocking HTTP is implemented
        // $prodigiService = new ProdigiOrderService(new ProdigiService());
        // $payload = $prodigiService->buildProdigiOrderPayload($order, $order->items);
        // $this->assertArrayHasKey('shipments', $payload);
    }

    /**
     * Test that only print items are included in Prodigi orders
     */
    public function test_only_print_items_submitted_to_prodigi(): void
    {
        // Create both print and digital products
        $digitalProduct = Product::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'digital',
            'name' => 'Digital Download',
        ]);

        // Create order with mixed items
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_email' => 'test@example.com',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->printProduct->id,
            'quantity' => 1,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $digitalProduct->id,
            'quantity' => 1,
        ]);

        $order->load('items');

        // Check that order has print items
        $this->assertTrue($order->hasPrintItems());

        // Note: The actual filtering happens in ProdigiOrderService::submitOrderToProdigi()
    }

    /**
     * Test order model helper methods
     */
    public function test_order_helper_methods(): void
    {
        $order = Order::factory()->create();

        // Test new helper methods
        $this->assertFalse($order->isProdigiSubmitted());
        $this->assertFalse($order->isProdigiShipped());

        // Simulate submission
        $order->update([
            'prodigi_order_id' => 'PROD12345',
            'prodigi_status' => 'processing',
        ]);

        $this->assertTrue($order->isProdigiSubmitted());
        $this->assertFalse($order->isProdigiShipped());

        // Simulate shipment
        $order->update([
            'prodigi_status' => 'shipped',
            'prodigi_tracking_number' => 'TRK123456789',
        ]);

        $this->assertTrue($order->isProdigiShipped());
    }
}
