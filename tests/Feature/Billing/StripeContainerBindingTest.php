<?php

namespace Tests\Feature\Billing;

use App\Billing\StripeApiCatalog;
use App\Billing\StripeCatalog;
use App\Console\Commands\SyncStripePlans;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * The real container bindings, with a Stripe key set as in production.
 * Cashier::stripe() resolves StripeClient from the container, so a binding
 * that calls Cashier::stripe() recursed until PHP ran out of memory, and
 * stripe:sync-plans died before printing anything.
 */
class StripeContainerBindingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cashier.secret' => 'sk_test_binding']);
        // Fail fast (instead of running away) if the recursion ever comes back.
        ini_set('memory_limit', '256M');
    }

    public function test_stripe_clients_resolve_without_recursion(): void
    {
        $this->assertSame('sk_test_binding', app(StripeClient::class)->getApiKey());
        $this->assertSame('sk_test_binding', Cashier::stripe()->getApiKey());
        $this->assertSame('sk_other', Cashier::stripe(['api_key' => 'sk_other'])->getApiKey());
        $this->assertInstanceOf(StripeApiCatalog::class, app(StripeCatalog::class));
        $this->assertInstanceOf(SyncStripePlans::class, app(SyncStripePlans::class));
    }

    public function test_sync_plans_dry_run_resolves_and_completes(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        // The command and its dependencies come from the container as in
        // production; only the Stripe API calls are faked.
        $this->app->instance(StripeCatalog::class, new FakeStripeCatalog());

        $this->artisan('stripe:sync-plans')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();
    }
}
