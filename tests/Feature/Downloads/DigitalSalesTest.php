<?php

namespace Tests\Feature\Downloads;

use App\Downloads\DigitalPricing;
use App\Http\Controllers\WebhookController;
use App\Jobs\DeliverDigitalOrder;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Photo;
use App\Models\Set;
use App\Models\Setting;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeConnectStripe;
use Tests\TestCase;

class DigitalSalesTest extends TestCase
{
    use RefreshDatabase;

    private FakeConnectStripe $stripe;


    private User $owner;

    private Collection $collection;

    /** @var list<Photo> */
    private array $photos = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.platform_fee_percent' => 10, 'prints.stripe_fee_percent' => 2.5, 'prints.stripe_fee_fixed' => 0.20]);
        Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'GBP']);
        Currency::flush();
        Storage::fake('s3');

        $this->owner = User::factory()->create(['is_admin' => true, 'digital_photo_price' => 5, 'digital_bundles' => [['count' => 5, 'price' => 20]]]);
        $this->owner->forceFill(['stripe_connect_id' => 'acct_1', 'stripe_connect_enabled' => true])->save();
        $this->collection = Collection::factory()->published()->create(['user_id' => $this->owner->id, 'name' => 'Smith Wedding', 'digital_sales' => true, 'digital_collection_price' => 40]);
        $set = Set::create(['collection_id' => $this->collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);
        foreach (range(1, 12) as $i) {
            Storage::disk('s3')->put("photos/p{$i}.jpg", "original-{$i}");
            $this->photos[] = Photo::create(['user_id' => $this->owner->id, 'set_id' => $set->id, 'url' => "photos/p{$i}.jpg", 'private' => false, 'size' => 10]);
        }

        $this->stripe = FakeConnectStripe::install($this->app);
    }

    private function buy(array $photos, array $extra = []): TestResponse
    {
        return $this->post(route('download-purchase.store', $this->collection), $extra + [
            'photo_ids' => array_map(fn (Photo $p) => $p->id, $photos),
            'customer_email' => 'client@example.com',
            'customer_name' => 'Client',
        ]);
    }

    public function test_quotes_use_the_cheapest_mix_and_the_whole_collection_when_cheaper(): void
    {
        $pricing = app(DigitalPricing::class);
        $offer = ['photo' => 5.0, 'bundles' => [['count' => 5, 'price' => 20.0], ['count' => 10, 'price' => 35.0]], 'collection' => 60.0];

        $this->assertSame(15.0, (float) $pricing->quote($offer, 3, 40)['total']);
        $this->assertSame(20.0, (float) $pricing->quote($offer, 5, 40)['total']);
        $seven = $pricing->quote($offer, 7, 40);
        $this->assertSame(30.0, (float) $seven['total']);
        $this->assertSame(['Single photo', 'Bundle of 5 photos'], array_column($seven['lines'], 'label'));
        $this->assertSame(35.0, (float) $pricing->quote($offer, 9, 40)['total'], 'a bundle of 10 beats 5 + 4 singles');
        $all = $pricing->quote($offer, 20, 40);
        $this->assertTrue($all['all']);
        $this->assertSame(60.0, (float) $all['total']);

        // Bundles only: a bundle can cover fewer photos than its size.
        $this->assertSame(20.0, (float) $pricing->quote(['photo' => null, 'bundles' => [['count' => 5, 'price' => 20.0]], 'collection' => null], 2, 40)['total']);
        $this->assertNull($pricing->quote(['photo' => null, 'bundles' => [], 'collection' => null], 2, 40));
        $this->assertSame(2.67, $pricing->minimumPrice(), 'platform fee covers the 2.5% + 20p card fee');
    }

    public function test_photographer_sets_default_prices_and_bundles(): void
    {
        $this->actingAs($this->owner)->get(route('profile.digital-sales.edit'))->assertOk()->assertSee('Smith Wedding');

        $this->actingAs($this->owner)->patch(route('profile.digital-sales.update'), [
            'digital_photo_price' => '6.50',
            'bundles' => [['count' => 10, 'price' => '45'], ['count' => '', 'price' => ''], ['count' => 3, 'price' => '15']],
        ])->assertSessionHasNoErrors();
        $this->owner->refresh();
        $this->assertSame('6.50', $this->owner->digital_photo_price);
        $this->assertSame([['count' => 3, 'price' => 15], ['count' => 10, 'price' => 45]], $this->owner->digital_bundles);

        $this->actingAs($this->owner)->patch(route('profile.digital-sales.update'), ['digital_photo_price' => '1'])
            ->assertSessionHasErrors('digital_photo_price');
        $this->actingAs($this->owner)->patch(route('profile.digital-sales.update'), ['bundles' => [['count' => 5, 'price' => '']]])
            ->assertSessionHasErrors('bundles.0.count');
    }

    public function test_collection_needs_a_price_before_going_on_sale(): void
    {
        $this->owner->update(['digital_photo_price' => null, 'digital_bundles' => null]);
        $other = Collection::factory()->create(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner)->patch(route('collections.digital-sales.update', $other->id), ['digital_sales' => '1'])
            ->assertSessionHasErrors('digital_photo_price');
        $this->actingAs($this->owner)->patch(route('collections.digital-sales.update', $other->id), ['digital_sales' => '1', 'digital_collection_price' => '99'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($other->fresh()->digital_sales);
        $this->actingAs(User::factory()->create())->patch(route('collections.digital-sales.update', $other->id), ['digital_sales' => '0'])->assertNotFound();
    }

    public function test_buy_page_shows_photos_and_prices_and_preselects_the_photo(): void
    {
        $this->get(route('download-purchase.show', ['collection' => $this->collection, 'photo' => $this->photos[2]->id]))
            ->assertOk()
            ->assertSee('Buy photos from Smith Wedding')
            ->assertSee('£5.00 per photo')
            ->assertSee('Any 5 for £20.00')
            ->assertSee('All 12 for £40.00')
            ->assertSee((string) $this->photos[2]->id);
    }

    public function test_buying_creates_an_order_and_a_stripe_checkout(): void
    {
        $this->buy(array_slice($this->photos, 0, 7))->assertRedirect('https://checkout.stripe.test/cs_test');

        $order = Order::sole();
        $this->assertTrue($order->isDigital());
        $this->assertSame('30.00', $order->total); // a bundle of 5 + 2 singles
        $this->assertSame('3.00', $order->platform_fee);
        $this->assertSame('27.00', $order->photographer_amount);
        $this->assertSame('cs_test', $order->stripe_checkout_session_id);
        $this->assertSame(7, $order->items()->count());
        $this->assertSame('30.00', number_format((float) $order->items()->sum('price'), 2));

        [$session] = $this->stripe->paramsFor('checkout.sessions.create');
        // A destination charge: £27 to the photographer's Express account, £3 kept as our fee.
        $this->assertSame('acct_1', $session['payment_intent_data']['transfer_data']['destination']);
        $this->assertSame(300, $session['payment_intent_data']['application_fee_amount']);
        $this->assertSame(3000, $session['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('portfolio', $session['payment_intent_data']['metadata']['app']);
        $this->assertStringContainsString('7 full-resolution photos from Smith Wedding', $session['line_items'][0]['price_data']['product_data']['name']);
    }

    public function test_when_the_whole_collection_is_cheaper_the_client_gets_every_photo(): void
    {
        $this->buy(array_slice($this->photos, 0, 11))->assertRedirect();

        $order = Order::sole();
        $this->assertSame('40.00', $order->total);
        $this->assertSame(12, $order->items()->count());
    }

    public function test_hidden_photos_and_other_collections_photos_cannot_be_bought(): void
    {
        $this->photos[0]->update(['private' => true]);
        $stranger = Photo::create(['user_id' => $this->owner->id, 'set_id' => Set::create(['collection_id' => Collection::factory()->published()->create(['user_id' => $this->owner->id])->id, 'name' => 'X'])->id, 'url' => 'photos/x.jpg', 'private' => false, 'size' => 1]);

        $this->buy([$this->photos[0], $stranger])->assertSessionHasErrors('photo_ids');
        $this->assertSame(0, Order::count());
    }

    public function test_not_on_sale_without_payouts_and_private_galleries_need_unlocking(): void
    {
        $this->owner->forceFill(['stripe_connect_enabled' => false])->save();
        $this->get(route('download-purchase.show', $this->collection))->assertNotFound();

        $this->owner->forceFill(['stripe_connect_enabled' => true])->save();
        $this->collection->update(['private' => true, 'password' => bcrypt('secret')]);
        $this->get(route('download-purchase.show', $this->collection))->assertRedirect(route('profile.collection', ['username' => $this->owner->username, 'collection_id' => $this->collection->id]));
        $this->buy([$this->photos[0]])->assertRedirect();
        $this->assertSame(0, Order::count());
    }

    public function test_free_downloads_are_off_while_downloads_are_on_sale(): void
    {
        $photo = $this->photos[0];
        $this->get(route('photos.download-options', $photo))->assertRedirect(route('download-purchase.show', ['collection' => $this->collection, 'photo' => $photo->id]));
        $this->postJson(route('photos.request-download', $photo), ['email' => 'a@example.com'])->assertForbidden()->assertJsonPath('buy_url', route('download-purchase.show', ['collection' => $this->collection, 'photo' => $photo->id]));

        $this->collection->update(['private' => true, 'password' => bcrypt('secret')]);
        $this->withSession(['unlocked_collections' => [(string) $this->collection->id]])
            ->postJson(route('collections.request-archive', $this->collection->id), ['email' => 'a@example.com'])->assertForbidden();

        // The photographer can still download their own photos.
        $this->actingAs($this->owner)->get(route('photos.download-options', $photo))->assertOk();

        // And when sales are off, free downloads come back.
        $this->collection->update(['digital_sales' => false]);
        $this->get(route('profile.collection', ['username' => $this->owner->username, 'collection_id' => $this->collection->id]))->assertOk()->assertDontSee('Buy photos');
    }

    public function test_gallery_shows_buy_photos(): void
    {
        $this->get(route('profile.collection', ['username' => $this->owner->username, 'collection_id' => $this->collection->id]))
            ->assertOk()->assertSee('Buy photos')->assertSee(route('download-purchase.show', $this->collection));
    }

    private function paid(int $photos = 3): Order
    {
        $this->buy(array_slice($this->photos, 0, $photos));
        $order = Order::latest()->first();
        Bus::fake([DeliverDigitalOrder::class]);
        \App\Billing\SaleEvents::paid(['id' => 'pi_dl', 'transfer' => 'tr_dl', 'metadata' => ['app' => 'portfolio', 'order_id' => $order->id]]);

        return $order->fresh();
    }

    public function test_payment_pays_the_photographer_and_queues_delivery(): void
    {
        $order = $this->paid();

        $this->assertSame('completed', $order->status);
        Bus::assertDispatched(DeliverDigitalOrder::class, fn ($job) => $job->orderId === $order->id);
        $this->assertSame('tr_dl', $order->stripe_transfer_id); // Stripe's transfer of the photographer's share
    }

    public function test_delivery_emails_the_buyer_their_link_once_and_tells_the_photographer(): void
    {
        $order = $this->paid();

        (new DeliverDigitalOrder($order->id))->handle();
        (new DeliverDigitalOrder($order->id))->handle(); // a retry sends nothing more

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(2, $messages);
        $toBuyer = $messages[0]->getOriginalMessage();
        $this->assertSame('client@example.com', $toBuyer->getTo()[0]->getAddress());
        $this->assertStringContainsString('/downloads/'.$order->id, $toBuyer->getHtmlBody());
        $this->assertSame($this->owner->email, $messages[1]->getOriginalMessage()->getTo()[0]->getAddress());
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_download_page_gives_the_unwatermarked_originals_and_a_zip(): void
    {
        $this->collection->update(['watermarked' => true]);
        $order = $this->paid(2);

        $this->get(route('downloads.show', $order))->assertForbidden(); // unsigned
        $page = $this->get($order->downloadUrl())->assertOk()->assertSee('Download all (2 photos, ZIP)');

        $photoLink = URL::temporarySignedRoute('downloads.photo', now()->addHour(), ['order' => $order->id, 'photo' => $this->photos[0]->id]);
        $this->get($photoLink)->assertRedirect()->assertRedirectContains('photos/p1.jpg');
        $other = URL::temporarySignedRoute('downloads.photo', now()->addHour(), ['order' => $order->id, 'photo' => $this->photos[5]->id]);
        $this->get($other)->assertNotFound();

        $zip = $this->get(URL::temporarySignedRoute('downloads.zip', now()->addHour(), ['order' => $order->id]));
        $zip->assertOk()->assertHeader('Content-Type', 'application/zip');
        $path = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($path, $zip->streamedContent());
        $archive = new \ZipArchive;
        $this->assertTrue($archive->open($path));
        $this->assertSame(2, $archive->numFiles);
        $this->assertSame('smith-wedding-001.jpg', $archive->getNameIndex(0));
        $this->assertContains($archive->getFromIndex(0), ['original-1', 'original-2']);
        $archive->close();
    }

    public function test_refunded_or_unpaid_orders_have_no_downloads(): void
    {
        $this->buy([$this->photos[0]]);
        $unpaid = Order::sole();
        $this->get(URL::temporarySignedRoute('downloads.show', now()->addDay(), ['order' => $unpaid->id]))->assertNotFound();

        $order = $this->paid(1);
        $order->update(['stripe_payment_intent_id' => 'pi_dl']);
        (new WebhookController)->handleChargeRefunded(['data' => ['object' => ['payment_intent' => 'pi_dl', 'refunded' => true]]]);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->get($order->downloadUrl())->assertNotFound();
    }

    public function test_success_page_shows_the_link_only_to_the_buyer_once_paid(): void
    {
        $order = $this->paid(1);

        $this->get(route('download-purchase.success', $order).'?session_id=cs_test')->assertOk()->assertSee('Download your photos');
        $this->get(route('download-purchase.success', $order).'?session_id=cs_other')->assertOk()->assertDontSee('Download your photos')->assertSee('emailed your download link');
    }
}
