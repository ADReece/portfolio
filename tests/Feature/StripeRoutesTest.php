<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class StripeRoutesTest extends TestCase
{
    public function test_routes_can_be_cached(): void
    {
        // Throws if two routes share a name, which makes route:cache fail.
        $this->app['router']->getRoutes()->compile();

        $this->assertSame(
            'App\Http\Controllers\WebhookController@handleWebhook',
            $this->app['router']->getRoutes()->getByName('cashier.webhook')->getActionName()
        );
    }

    public function test_stripe_webhook_is_exempt_from_csrf(): void
    {
        // Laravel skips CSRF checks in tests, so check the exemption directly.
        $middleware = $this->app->make(VerifyCsrfToken::class);
        $except = (fn () => $this->except)->call($middleware);

        $this->assertContains('stripe/webhook', $except);
    }
}
