<?php

namespace Tests\Feature\Prints;

use App\Models\ProdigiProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Prodigi is always faked: no real calls, and never an order. */
abstract class PrintCatalogueTestCase extends TestCase
{
    use RefreshDatabase;

    protected const BASE = 'https://api.prodigi.test/v4.0';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.prodigi.api_key' => 'test-prodigi-key',
            'services.prodigi.base_url' => self::BASE,
            'services.prodigi.environment' => 'live',
            'services.platform_fee_percent' => 10,
            'prints.stripe_fee_percent' => 2.5,
            'prints.stripe_fee_fixed' => 0.20,
            'prints.fallback_retail_price' => 29.99,
        ]);
        Http::preventStrayRequests();
    }

    protected function productPayload(string $sku = 'GLOBAL-CFPM-16X20', array $attributes = ['color' => ['black', 'white', 'natural']]): array
    {
        return [
            'outcome' => 'Ok',
            'product' => [
                'sku' => $sku,
                'description' => 'Classic frame, 16x20", EMA paper',
                'productDimensions' => ['width' => 16, 'height' => 20, 'units' => 'in'],
                'attributes' => $attributes,
                'printAreas' => ['default' => ['required' => true]],
                'variants' => [['attributes' => ['color' => 'black'], 'shipsTo' => ['GB']]],
            ],
        ];
    }

    protected function quotePayload(string $item = '10.00', string $shipping = '4.50'): array
    {
        return [
            'outcome' => 'Created',
            'quotes' => [[
                'shipmentMethod' => 'Standard',
                'costSummary' => [
                    'items' => ['amount' => $item, 'currency' => 'GBP'],
                    'shipping' => ['amount' => $shipping, 'currency' => 'GBP'],
                ],
            ]],
        ];
    }

    protected function fakeProdigi(string $sku = 'GLOBAL-CFPM-16X20', string $item = '10.00', string $shipping = '4.50'): void
    {
        Http::fake([
            self::BASE.'/products/'.$sku => Http::response($this->productPayload($sku)),
            self::BASE.'/products/*' => Http::response(['outcome' => 'NotFound'], 404),
            self::BASE.'/quotes' => Http::response($this->quotePayload($item, $shipping)),
        ]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function catalogueProduct(array $attrs = []): ProdigiProduct
    {
        $product = ProdigiProduct::create($attrs + [
            'sku' => 'GLOBAL-CFPM-16X20',
            'name' => 'Framed print 16×20',
            'category' => 'Framed',
            'is_active' => true,
            'raw_payload' => $this->productPayload()['product'],
        ]);
        $product->forceFill(['cost_item' => 10.00, 'cost_shipping' => 4.50, 'cost_currency' => 'GBP', 'cost_quoted_at' => now()])->save();

        return $product;
    }

    /** Every Prodigi request used the v4 API key header and none placed an order. */
    protected function assertSafeProdigiCalls(): void
    {
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/orders'));
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-API-Key', 'test-prodigi-key') && ! $r->hasHeader('Authorization'));
    }
}
