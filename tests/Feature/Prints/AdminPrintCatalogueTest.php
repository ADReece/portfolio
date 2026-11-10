<?php

namespace Tests\Feature\Prints;

use App\Models\ProdigiImport;
use App\Models\ProdigiProduct;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class AdminPrintCatalogueTest extends PrintCatalogueTestCase
{
    public function test_admin_only(): void
    {
        $product = $this->catalogueProduct();
        $photographer = User::factory()->create();

        $this->get(route('admin.print-catalogue.index'))->assertRedirect(route('login'));
        foreach ([
            ['get', route('admin.print-catalogue.index')],
            ['get', route('admin.print-catalogue.edit', $product)],
            ['post', route('admin.print-catalogue.lookup')],
            ['post', route('admin.print-catalogue.toggle', $product)],
            ['post', route('admin.print-catalogue.refresh-all')],
        ] as [$method, $url]) {
            $this->actingAs($photographer)->{$method}($url)->assertForbidden();
        }
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_add_by_sku_looks_it_up_then_saves_it_inactive_with_its_cost(): void
    {
        $this->fakeProdigi();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.print-catalogue.lookup'), ['sku' => 'global-cfpm-16x20'])
            ->assertOk()
            ->assertSee('Classic frame, 16x20&quot;, EMA paper', false)
            ->assertSee('16 × 20 in')
            ->assertSee('black, white, natural');

        $this->actingAs($admin)->post(route('admin.print-catalogue.store'), ['sku' => 'GLOBAL-CFPM-16X20', 'name' => 'Classic frame 16×20', 'category' => 'Framed'])
            ->assertRedirect();

        $product = ProdigiProduct::sole();
        $this->assertSame('Classic frame 16×20', $product->name);
        $this->assertFalse($product->is_active, 'new products start hidden');
        $this->assertSame(['color' => ['black', 'white', 'natural']], $product->prodigiAttributes());
        $this->assertSame('10.00', $product->cost_item);
        $this->assertSame('4.50', $product->cost_shipping);
        $this->assertNotNull($product->refreshed_at);

        // The quote is for one copy to the UK, in GBP, standard delivery.
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/quotes')
            && $r['destinationCountryCode'] === 'GB' && $r['currencyCode'] === 'GBP' && $r['shippingMethod'] === 'Standard'
            && $r['items'][0]['sku'] === 'GLOBAL-CFPM-16X20' && $r['items'][0]['copies'] === 1 && $r['items'][0]['attributes'] === ['color' => 'black']);
        $this->assertSafeProdigiCalls();
    }

    public function test_unknown_sku_and_duplicates_are_refused(): void
    {
        $this->fakeProdigi();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.print-catalogue.lookup'), ['sku' => 'NOPE-123'])
            ->assertSessionHasErrors(['sku' => "Prodigi doesn't have a product with SKU NOPE-123."]);

        $product = $this->catalogueProduct();
        $this->actingAs($admin)->post(route('admin.print-catalogue.lookup'), ['sku' => $product->sku])
            ->assertRedirect(route('admin.print-catalogue.edit', $product));
    }

    public function test_refresh_updates_cost_and_records_failures(): void
    {
        $product = $this->catalogueProduct();
        Http::fake([
            self::BASE.'/products/*' => Http::sequence()
                ->push($this->productPayload())
                ->push(['outcome' => 'InternalError'], 500),
            self::BASE.'/quotes' => Http::response($this->quotePayload('12.00', '5.00')),
        ]);

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.refresh', $product))->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('12.00', $product->cost_item);
        $this->assertSame('5.00', $product->cost_shipping);
        $this->assertNull($product->refresh_error);

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.refresh', $product))->assertSessionHas('error');
        $this->assertStringContainsString('failed', $product->fresh()->refresh_error);
        $this->assertSame('12.00', $product->fresh()->cost_item, 'last good cost is kept');
    }

    public function test_refresh_all_and_the_scheduled_command(): void
    {
        $this->catalogueProduct();
        $this->fakeProdigi(item: '11.00');

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.refresh-all'))->assertSessionHas('success');
        $this->assertSame('11.00', ProdigiProduct::sole()->cost_item);

        $this->artisan('prodigi:sync-catalog')->expectsOutputToContain('Refreshed 1 of 1')->assertSuccessful();
        $this->assertSafeProdigiCalls();
    }

    public function test_default_price_cannot_be_below_the_minimum(): void
    {
        $product = $this->catalogueProduct(); // cost 14.50 -> minimum 14.50 / 0.9 = 16.12
        $admin = $this->admin();
        $form = ['name' => 'Framed', 'category' => 'Framed', 'sort_order' => 1, 'is_active' => '1',
            'allowed_attributes' => ['color' => ['black', 'white']]];

        $this->assertSame(16.12, $product->minimumRetailPrice());
        $this->actingAs($admin)->put(route('admin.print-catalogue.update', $product), $form + ['default_retail_price' => '16.11'])
            ->assertSessionHasErrors('default_retail_price');
        $this->actingAs($admin)->put(route('admin.print-catalogue.update', $product), $form + ['default_retail_price' => '24.00'])
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('24.00', $product->default_retail_price);
        $this->assertSame(['color' => ['black', 'white']], $product->offeredAttributes());
    }

    public function test_options_must_be_ones_prodigi_offers_and_at_least_one_each(): void
    {
        $product = $this->catalogueProduct();
        $form = ['name' => 'Framed', 'category' => 'Framed', 'sort_order' => 1, 'is_active' => '1'];

        $this->actingAs($this->admin())->put(route('admin.print-catalogue.update', $product), $form + ['allowed_attributes' => ['color' => ['purple']]])
            ->assertSessionHasErrors('allowed_attributes.color');
    }

    public function test_list_shows_cost_margin_counts_and_below_cost_photographers(): void
    {
        $product = $this->catalogueProduct();
        $ok = User::factory()->create();
        $cheap = User::factory()->create();
        $ok->prodigiProducts()->attach($product->id, ['is_enabled' => true, 'retail_price' => 30]);
        $cheap->prodigiProducts()->attach($product->id, ['is_enabled' => true, 'retail_price' => 15]);

        $this->actingAs($this->admin())->get(route('admin.print-catalogue.index', ['q' => 'cfpm']))
            ->assertOk()
            ->assertSee('GLOBAL-CFPM-16X20')
            ->assertSee('£14.50')
            ->assertSee('£16.12')
            ->assertSee('1 below cost');
    }

    public function test_bulk_add_pasted_skus(): void
    {
        $this->catalogueProduct(['sku' => 'GLOBAL-OLD-1', 'name' => 'Existing']);
        Http::fake([
            self::BASE.'/products/GLOBAL-CAN-16X20' => Http::response($this->productPayload('GLOBAL-CAN-16X20', [])),
            self::BASE.'/products/GLOBAL-FAP-10X8' => Http::response($this->productPayload('GLOBAL-FAP-10X8', [])),
            self::BASE.'/products/*' => Http::response(['outcome' => 'NotFound'], 404),
            self::BASE.'/quotes' => Http::response($this->quotePayload()),
        ]);

        $this->actingAs($this->admin())->post(route('admin.print-catalogue.store-many'), [
            'skus' => "global-can-16x20\nGLOBAL-FAP-10X8, GLOBAL-OLD-1\nNOPE-1",
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'Checking 3 SKUs') && str_contains($m, '1 already in the catalogue'));

        // The queue is synchronous in tests, so the import has finished.
        $import = ProdigiImport::sole();
        $this->assertSame([4, 2, 1, 1, 0], [$import->total, $import->added, $import->skipped, $import->not_found, $import->failed]);
        $this->assertSame(['NOPE-1'], $import->not_found_skus);
        $this->assertNotNull($import->finished_at);

        $this->actingAs($this->admin())->get(route('admin.print-catalogue.index'))
            ->assertSee('Pasted SKUs')->assertSee('2 added')->assertSee('NOPE-1');

        $canvas = ProdigiProduct::where('sku', 'GLOBAL-CAN-16X20')->sole();
        $this->assertFalse($canvas->is_active);
        $this->assertSame('Canvas', $canvas->category);
        $this->assertSame('Fine art prints', ProdigiProduct::where('sku', 'GLOBAL-FAP-10X8')->value('category'));
        $this->assertSame('10.00', $canvas->cost_item);
        $this->assertSafeProdigiCalls();
    }
}
