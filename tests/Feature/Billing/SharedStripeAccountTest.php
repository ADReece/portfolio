<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Portfolio shares its Stripe account with other Ascend Digital apps, so the
 * webhook sees their events too. They must be acknowledged and ignored.
 */
class SharedStripeAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cashier.webhook.secret' => null]);
    }

    public function test_empty_or_malformed_posts_get_400_not_500(): void
    {
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], '')->assertStatus(400);
        $this->call('POST', '/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'not json')->assertStatus(400);
        $this->postJson('/stripe/webhook', ['id' => 'evt_1'])->assertStatus(400);
    }

    public function test_bad_signature_is_refused_when_a_secret_is_set(): void
    {
        config(['cashier.webhook.secret' => 'whsec_test']);

        $this->withHeaders(['Stripe-Signature' => 't=1,v1=forged'])
            ->postJson('/stripe/webhook', ['type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_x']]])
            ->assertStatus(403);
    }

    public function test_events_for_unknown_customers_get_200(): void
    {
        foreach (['customer.subscription.updated', 'customer.subscription.deleted', 'customer.updated', 'invoice.payment_succeeded'] as $type) {
            $this->postJson('/stripe/webhook', ['type' => $type, 'data' => ['object' => [
                'id' => 'sub_other', 'customer' => 'cus_from_another_app', 'items' => ['data' => []],
            ]]])->assertOk();
        }
    }

    public function test_payment_intents_from_other_apps_are_ignored(): void
    {
        $this->postJson('/stripe/webhook', ['type' => 'payment_intent.succeeded', 'data' => ['object' => [
            'id' => 'pi_bookit', 'metadata' => ['app' => 'bookit', 'order_id' => '1'],
        ]]])->assertOk()->assertSee('Webhook Handled');

        $this->postJson('/stripe/webhook', ['type' => 'payment_intent.payment_failed', 'data' => ['object' => [
            'id' => 'pi_untagged',
        ]]])->assertOk();
    }

    public function test_portfolio_customers_are_tagged(): void
    {
        $user = User::factory()->create();

        $this->assertSame('portfolio', $user->stripeMetadata()['app']);
    }
}
