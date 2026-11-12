<?php

namespace Tests\Feature\Prints;

use App\Models\Collection;
use App\Models\Order;
use App\Models\Photo;
use App\Models\ProdigiProduct;
use App\Models\Set;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeConnectStripe;

/**
 * The client pays the price; Prodigi's cost for their order (quoted for their
 * address at checkout) and the platform fee come off it; the photographer
 * receives the rest.
 */
class PrintCheckoutPayoutTest extends PrintCatalogueTestCase
{
    private Photo $photo;
    private ProdigiProduct $product;
    private FakeConnectStripe $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'GBP']);
        \App\Support\Currency::flush();

        $owner = User::factory()->create(['is_admin' => true]); // admins have every feature
        $collection = Collection::factory()->published()->create(['user_id' => $owner->id]);
        $set = Set::create(['collection_id' => $collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);
        $this->photo = Photo::create(['user_id' => $owner->id, 'set_id' => $set->id, 'url' => 'photos/a.jpg', 'caption' => 'A', 'private' => false, 'size' => 10]);
        $this->product = $this->catalogueProduct();
        $owner->prodigiProducts()->attach($this->product->id, ['is_enabled' => true, 'retail_price' => 40]);

        $owner->forceFill(['stripe_connect_id' => 'acct_photographer', 'stripe_connect_enabled' => true])->save();
        $this->stripe = FakeConnectStripe::install($this->app);
    }

    private function order(string $country = 'GB', int $quantity = 2): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('print-purchase.store', $this->photo), [
            'prodigi_product_id' => $this->product->id,
            'options' => ['color' => 'white'],
            'quantity' => $quantity,
            'customer_email' => 'client@example.com',
            'customer_phone' => '07700900000',
            'shipping_address_line1' => '1 High St',
            'shipping_city' => 'Leeds',
            'shipping_county' => 'West Yorkshire',
            'shipping_postcode' => 'LS1 1AA',
            'shipping_country_code' => $country,
        ]);
    }

    public function test_photographer_gets_price_minus_actual_cost_minus_platform_fee(): void
    {
        Http::fake([self::BASE.'/quotes' => Http::response($this->quotePayload('20.00', '6.00'))]);

        $this->order()->assertSessionHasNoErrors()->assertRedirect('https://checkout.stripe.test/cs_test');

        $order = Order::sole();
        $this->assertEquals(80.00, (float) $order->total);          // 2 × £40, delivery included
        $this->assertEquals(26.00, (float) $order->print_cost);     // Prodigi: 2 prints + delivery
        $this->assertEquals(8.00, (float) $order->platform_fee);    // 10%
        $this->assertEquals(46.00, (float) $order->photographer_amount); // 80 − 26 − 8
        $this->assertSame(['color' => 'white'], $order->items()->sole()->prodigi_attributes);
        [$session] = $this->stripe->paramsFor('checkout.sessions.create');
        $this->assertSame(8000, $session['line_items'][0]['price_data']['unit_amount'] * $session['line_items'][0]['quantity']);
        // A destination charge: the photographer's £46 goes to their Express
        // account; Portfolio keeps the print cost and its fee (£34).
        $this->assertSame('acct_photographer', $session['payment_intent_data']['transfer_data']['destination']);
        $this->assertSame(3400, $session['payment_intent_data']['application_fee_amount']);
        $this->assertSame('portfolio', $session['payment_intent_data']['metadata']['app']);

        // The quote was for this order: 2 copies, the chosen option, the client's country.
        Http::assertSent(fn (Request $r) => $r['destinationCountryCode'] === 'GB' && $r['currencyCode'] === 'GBP'
            && $r['items'][0]['copies'] === 2 && $r['items'][0]['attributes'] === ['color' => 'white']);
        $this->assertSafeProdigiCalls();
    }

    public function test_delivery_that_would_cost_more_than_the_photographer_earns_is_refused(): void
    {
        Http::fake([self::BASE.'/quotes' => Http::response($this->quotePayload('30.00', '60.00'))]);

        $this->order('US')->assertSessionHasErrors('shipping_country_code');
        $this->assertSame(0, Order::count());
        $this->assertSame([], $this->stripe->paramsFor('checkout.sessions.create'));
    }

    public function test_no_order_without_a_cost_quote(): void
    {
        Http::fake([self::BASE.'/quotes' => Http::response(['outcome' => 'InternalError'], 500)]);

        $this->order()->assertSessionHasErrors('shipping_country_code');
        $this->assertSame(0, Order::count());
    }

    public function test_photographers_without_payouts_set_up_cannot_sell(): void
    {
        $this->photo->user->forceFill(['stripe_connect_enabled' => false])->save();

        $this->get(route('print-purchase.show', $this->photo))->assertNotFound();
        $this->order()->assertNotFound();
    }
}
