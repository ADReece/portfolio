<?php

namespace Tests\Feature\Prints;

use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use App\Models\Product;
use App\Models\Set;
use App\Models\User;
use App\Service\ProdigiOrderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class PhotographerCatalogueTest extends PrintCatalogueTestCase
{
    public function test_only_active_products_are_listed_and_deactivating_keeps_history(): void
    {
        $active = $this->catalogueProduct();
        $hidden = $this->catalogueProduct(['sku' => 'GLOBAL-OLD-1', 'name' => 'Old print', 'is_active' => false]);
        $photographer = User::factory()->create();
        $photographer->prodigiProducts()->attach($hidden->id, ['is_enabled' => true, 'retail_price' => 40]);

        $this->actingAs($photographer)->get(route('profile.prodigi-products.edit'))
            ->assertOk()->assertSee('Framed print 16×20')->assertDontSee('Old print');

        // Saving never touches settings for the inactive product.
        $this->actingAs($photographer)->patch(route('profile.prodigi-products.update'), ['enabled_product_ids' => [$active->id], 'retail_prices' => [$active->id => '25']]);
        $this->assertSame('40.00', $photographer->prodigiProducts()->find($hidden->id)->pivot->retail_price);

        // Deactivate via the admin: hidden, but pivot rows and past orders stay.
        $order = Order::factory()->create();
        $item = OrderItem::create(['order_id' => $order->id, 'product_id' => Product::factory()->create(['prodigi_sku' => $active->sku])->id, 'quantity' => 1, 'price' => 25]);
        $this->actingAs($this->admin())->post(route('admin.print-catalogue.toggle', $active));
        $this->assertFalse($active->fresh()->is_active);
        $this->assertTrue($photographer->prodigiProducts()->where('prodigi_products.id', $active->id)->exists());
        $this->assertModelExists($item);
        $this->assertFalse($photographer->enabledProdigiProducts()->exists());
    }

    public function test_photographers_cannot_price_below_the_minimum_and_empty_means_default(): void
    {
        $product = $this->catalogueProduct(['default_retail_price' => 27.5]);
        $photographer = User::factory()->create();

        $this->actingAs($photographer)->patch(route('profile.prodigi-products.update'), ['enabled_product_ids' => [$product->id], 'retail_prices' => [$product->id => '16.00']])
            ->assertSessionHasErrors("retail_prices.{$product->id}");

        $this->actingAs($photographer)->patch(route('profile.prodigi-products.update'), ['enabled_product_ids' => [$product->id], 'retail_prices' => [$product->id => '']])
            ->assertSessionHasNoErrors();
        $this->assertNull($photographer->prodigiProducts()->find($product->id)->pivot->retail_price);
        $this->actingAs($photographer)->get(route('profile.prodigi-products.edit'))->assertSee('Empty uses £27.50');
    }

    public function test_cost_rising_above_a_photographers_price_is_flagged_and_not_sold(): void
    {
        $product = $this->catalogueProduct();
        $photographer = User::factory()->create(['is_admin' => true, 'username' => 'ava']); // admins have every feature
        $photographer->prodigiProducts()->attach($product->id, ['is_enabled' => true, 'retail_price' => 20]);

        $product->forceFill(['cost_item' => 15.00])->save(); // minimum is now 19.50 / 0.9 = 21.67

        $this->actingAs($photographer)->get(route('profile.prodigi-products.edit'))
            ->assertSee('Some prices no longer cover the cost')
            ->assertSee('Raise it to at least £21.67');
        $this->getJson(route('profile.print-products', 'ava'))->assertJsonPath('products', []);
    }

    public function test_order_sent_to_prodigi_uses_the_v4_format_with_the_chosen_options(): void
    {
        config(['filesystems.disks.s3' => array_merge(config('filesystems.disks.s3'), [
            'driver' => 's3', 'key' => 'test', 'secret' => 'test', 'region' => 'eu-west-2', 'bucket' => 'test-bucket',
        ])]);
        $owner = User::factory()->create();
        $set = Set::create(['collection_id' => Collection::factory()->create(['user_id' => $owner->id])->id, 'name' => 'Main']);
        $photo = Photo::create(['user_id' => $owner->id, 'set_id' => $set->id, 'url' => 'photos/original.jpg', 'caption' => 'A', 'private' => false, 'size' => 10]);
        $product = Product::factory()->create(['user_id' => $owner->id, 'photo_id' => $photo->id, 'prodigi_sku' => 'GLOBAL-CFPM-16X20', 'type' => 'print_poster']);
        $order = Order::factory()->create([
            'customer_name' => 'Sam Client', 'customer_email' => 'sam@example.com', 'customer_phone' => '07700900000',
            'shipping_address_line1' => '1 High St', 'shipping_city' => 'Leeds', 'shipping_county' => 'West Yorkshire',
            'shipping_postcode' => 'LS1 1AA', 'shipping_country_code' => 'gb',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 30, 'prodigi_attributes' => ['color' => 'white']]);

        Http::fake([self::BASE.'/orders' => Http::response(['outcome' => 'Created', 'order' => [
            'id' => 'ord_123', 'status' => ['stage' => 'InProgress'], 'charges' => [['totalCost' => ['amount' => '29.00', 'currency' => 'GBP']]],
        ]])]);

        app(ProdigiOrderService::class)->submitOrderToProdigi($order);

        Http::assertSent(function (Request $r) use ($order) {
            $item = $r['items'][0] ?? [];

            return str_ends_with($r->url(), '/orders')
                && $r->hasHeader('X-API-Key', 'test-prodigi-key')
                && $r['idempotencyKey'] === 'portfolio-order-'.$order->id
                && $r['shippingMethod'] === 'Standard'
                && $r['recipient']['address'] === ['line1' => '1 High St', 'postalOrZipCode' => 'LS1 1AA', 'countryCode' => 'GB', 'townOrCity' => 'Leeds', 'stateOrCounty' => 'West Yorkshire']
                && $item['sku'] === 'GLOBAL-CFPM-16X20' && $item['copies'] === 2 && $item['sizing'] === 'fillPrintArea'
                && $item['attributes'] === ['color' => 'white']
                && $item['assets'][0]['printArea'] === 'default'
                && str_contains($item['assets'][0]['url'], 'photos/original.jpg') && str_contains($item['assets'][0]['url'], 'X-Amz-Signature');
        });
        $order->refresh();
        $this->assertSame('ord_123', $order->prodigi_order_id);
        $this->assertSame('processing', $order->prodigi_status);
        $this->assertEquals(29.00, (float) $order->prodigi_charge);
    }
}
