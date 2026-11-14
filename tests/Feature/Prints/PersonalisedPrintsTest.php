<?php

namespace Tests\Feature\Prints;

use App\Billing\SaleEvents;
use App\Jobs\RenderPersonalisedPrints;
use App\Jobs\SendPrintOrderEmail;
use App\Jobs\SubmitOrderToProdigi;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use App\Models\ProdigiProduct;
use App\Models\Set;
use App\Models\Template;
use App\Models\User;
use App\Service\ProdigiOrderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeConnectStripe;

/** Clients personalise a print with the photographer's template as part of ordering it. */
class PersonalisedPrintsTest extends PrintCatalogueTestCase
{
    private User $owner;

    private Photo $photo;

    private ProdigiProduct $product;

    private Template $template;

    private FakeConnectStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Needs the imagick extension.');
        }
        \App\Models\Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'GBP']);
        \App\Support\Currency::flush();

        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->owner->forceFill(['stripe_connect_id' => 'acct_1', 'stripe_connect_enabled' => true])->save();
        $collection = Collection::factory()->published()->create(['user_id' => $this->owner->id]);
        $set = Set::create(['collection_id' => $collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);
        $this->photo = Photo::create(['user_id' => $this->owner->id, 'set_id' => $set->id, 'url' => 'photos/'.$this->owner->id.'/a.jpg', 'private' => false, 'size' => 10, 'width' => 1200, 'height' => 800]);
        $this->jpeg($this->photo->url, 1200, 800);
        $this->jpeg('watermarked/'.$this->owner->id.'/a.jpg', 600, 400);

        $this->product = $this->catalogueProduct();
        $this->owner->prodigiProducts()->attach($this->product->id, ['is_enabled' => true, 'retail_price' => 40]);

        $this->template = Template::create([
            'user_id' => $this->owner->id, 'name' => 'Save the date', 'active' => true,
            'fields' => [['key' => 'names', 'label' => 'Your names', 'x' => 50, 'y' => 80, 'font_size' => 60, 'color' => '#ffffff', 'align' => 'center']],
        ]);
        $set->templates()->attach($this->template->id);

        $this->stripe = FakeConnectStripe::install($this->app);
    }

    private function jpeg(string $key, int $w, int $h): void
    {
        $im = new \Imagick();
        $im->newPseudoImage($w, $h, 'gradient:#204060-#e0c080');
        $im->setImageFormat('jpeg');
        Storage::disk('s3')->put($key, $im->getImageBlob());
    }

    private function order(array $extra = []): \Illuminate\Testing\TestResponse
    {
        Http::fake([self::BASE.'/quotes' => Http::response($this->quotePayload('20.00', '6.00'))]);

        return $this->post(route('print-purchase.store', $this->photo), $extra + [
            'prodigi_product_id' => $this->product->id,
            'options' => ['color' => 'white'],
            'quantity' => 1,
            'customer_email' => 'client@example.com',
            'customer_phone' => '07700900000',
            'shipping_address_line1' => '1 High St',
            'shipping_city' => 'Leeds',
            'shipping_county' => 'West Yorkshire',
            'shipping_postcode' => 'LS1 1AA',
            'shipping_country_code' => 'GB',
        ]);
    }

    public function test_the_order_page_offers_the_sets_active_templates(): void
    {
        Template::create(['user_id' => $this->owner->id, 'name' => 'Switched off', 'active' => false, 'fields' => []]);

        $this->get(route('print-purchase.show', $this->photo))
            ->assertOk()
            ->assertSee('Personalise')
            ->assertSee('No personalisation')
            ->assertSee('Save the date')
            ->assertSee('Your names')
            ->assertDontSee('Switched off');
    }

    public function test_the_preview_is_a_small_watermarked_jpeg(): void
    {
        $response = $this->post(route('print-purchase.preview', $this->photo), [
            'template_id' => $this->template->id, 'template_fields' => ['names' => 'Sam & Alex'],
        ])->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $im = new \Imagick();
        $im->readImageBlob($response->getContent());
        $this->assertSame(600, $im->getImageWidth(), 'made from the watermarked copy, not the original');

        $this->post(route('print-purchase.preview', $this->photo), ['template_id' => 'not-a-template'])->assertNotFound();
    }

    public function test_ordering_saves_the_design_and_text_with_the_print(): void
    {
        $this->order(['template_id' => $this->template->id, 'template_fields' => ['names' => '  Sam & Alex  ', 'extra' => 'ignored']])
            ->assertSessionHasNoErrors()->assertRedirect('https://checkout.stripe.test/cs_test');

        $item = OrderItem::sole();
        $this->assertSame($this->template->id, $item->template_id);
        $this->assertSame(['names' => 'Sam & Alex'], $item->template_data);
        [$session] = $this->stripe->paramsFor('checkout.sessions.create');
        $this->assertStringContainsString('personalised (Save the date)', $session['line_items'][0]['price_data']['product_data']['name']);
    }

    public function test_a_design_needs_its_text_and_must_be_the_photographers(): void
    {
        $this->order(['template_id' => $this->template->id, 'template_fields' => ['names' => '']])->assertSessionHasErrors('template_fields');
        $other = Template::create(['user_id' => User::factory()->create()->id, 'name' => 'Not theirs', 'active' => true, 'fields' => []]);
        $this->order(['template_id' => $other->id])->assertSessionHasErrors('template_id');
        $this->assertSame(0, Order::count());

        // No design is fine too.
        $this->order()->assertSessionHasNoErrors();
        $this->assertNull(OrderItem::sole()->template_id);
    }

    public function test_after_payment_the_print_is_drawn_full_size_then_sent_to_prodigi(): void
    {
        $this->order(['template_id' => $this->template->id, 'template_fields' => ['names' => 'Sam & Alex']]);
        $order = Order::sole();

        Bus::fake([RenderPersonalisedPrints::class, SubmitOrderToProdigi::class, SendPrintOrderEmail::class]);
        SaleEvents::paid(['id' => 'pi_1', 'metadata' => ['app' => 'portfolio', 'order_id' => $order->id]]);
        Bus::assertChained([RenderPersonalisedPrints::class, SubmitOrderToProdigi::class]);

        // The chain's first job: the full-size personalised image.
        (new RenderPersonalisedPrints($order->id))->handle();
        $item = OrderItem::sole();
        $this->assertSame('prints/orders/'.$order->id.'/'.$item->id.'.jpg', $item->print_asset_path);
        $im = new \Imagick();
        $im->readImageBlob(Storage::disk('s3')->get($item->print_asset_path));
        $this->assertSame([1200, 800], [$im->getImageWidth(), $im->getImageHeight()], 'the original\'s full size');
    }

    public function test_prodigi_prints_the_personalised_image(): void
    {
        config(['filesystems.disks.s3' => array_merge(config('filesystems.disks.s3'), [
            'driver' => 's3', 'key' => 'test', 'secret' => 'test', 'region' => 'eu-west-2', 'bucket' => 'test-bucket',
        ])]);
        Storage::forgetDisk('s3');
        $order = Order::factory()->create([
            'user_id' => $this->owner->id, 'customer_phone' => '07700900000', 'shipping_address_line1' => '1 High St',
            'shipping_city' => 'Leeds', 'shipping_county' => 'West Yorkshire', 'shipping_postcode' => 'LS1 1AA', 'shipping_country_code' => 'GB',
        ]);
        $product = \App\Models\Product::factory()->create(['user_id' => $this->owner->id, 'photo_id' => $this->photo->id, 'prodigi_sku' => 'GLOBAL-CFPM-16X20', 'type' => 'print_poster']);
        $item = OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 40, 'template_id' => $this->template->id, 'template_data' => ['names' => 'x']]);

        // Not drawn yet: it isn't sent.
        $this->expectExceptionMessage('has not been made yet');
        try {
            app(ProdigiOrderService::class)->submitOrderToProdigi($order);
        } finally {
            $item->update(['print_asset_path' => 'prints/orders/'.$order->id.'/'.$item->id.'.jpg']);
            Http::fake([self::BASE.'/orders' => Http::response(['outcome' => 'Created', 'order' => ['id' => 'ord_1', 'status' => ['stage' => 'InProgress']]])]);
            app(ProdigiOrderService::class)->submitOrderToProdigi($order->fresh());
            Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/orders')
                && str_contains($r['items'][0]['assets'][0]['url'], 'prints/orders/'.$order->id.'/'.$item->id.'.jpg'));
        }
    }

    public function test_clients_cannot_download_personalised_images_for_free(): void
    {
        $this->post(route('photos.download-with-template', [$this->photo, $this->template]), ['fields' => ['names' => 'x']])->assertNotFound();
        $this->get(route('photos.download-options', $this->photo))->assertOk()->assertDontSee('Save the date');

        $this->actingAs($this->owner)->get(route('photos.download-options', $this->photo))->assertOk()->assertSee('Save the date');
    }
}
