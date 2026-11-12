<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Livewire\LogoUploader;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\App\CustomDomains\DnsResolver::class, \App\CustomDomains\SystemDnsResolver::class);
        // Portfolio's Connect account (App\Billing\Payouts), not the shared subscriptions account.
        $this->app->bind('stripe.connect', fn () => new \Stripe\StripeClient([
            'api_key' => config('services.portfolio_connect.secret') ?: null,
            'stripe_version' => \Laravel\Cashier\Cashier::STRIPE_VERSION,
        ]));
        $this->app->singleton(\App\CustomDomains\DomainRegistry::class);

        // routes/web.php defines Cashier's payment and webhook routes itself
        // (the webhook uses App\Http\Controllers\WebhookController). Cashier's
        // own copies duplicated the route names, which broke route:cache.
        \Laravel\Cashier\Cashier::ignoreRoutes();

        // A Stripe client with Cashier's key and API version. Cashier::stripe()
        // itself resolves StripeClient from the container with a "config"
        // parameter, so this binding must build the client directly and never
        // call Cashier::stripe() (that recursed until memory ran out).
        $this->app->bind(\Stripe\StripeClient::class, fn ($app, array $params = []) => new \Stripe\StripeClient(
            $params['config'] ?? [
                'api_key' => config('cashier.secret'),
                'stripe_version' => \Laravel\Cashier\Cashier::STRIPE_VERSION,
            ]
        ));
        $this->app->bind(\App\Billing\StripeCatalog::class, fn () => new \App\Billing\StripeApiCatalog(\Laravel\Cashier\Cashier::stripe()));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        \Laravel\Cashier\Subscription::observe(\App\Observers\SubscriptionObserver::class);

        // Share currency symbol with all views
        // Resolved lazily and memoised, so views don't each hit the database.
        view()->composer('*', function ($view) {
            $view->with('currencySymbol', \App\Support\Currency::symbol());
        });

        Livewire::component('logo-uploader', LogoUploader::class);
    }
}
