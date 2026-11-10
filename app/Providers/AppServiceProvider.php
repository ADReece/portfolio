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
        // routes/web.php defines Cashier's payment and webhook routes itself
        // (the webhook uses App\Http\Controllers\WebhookController). Cashier's
        // own copies duplicated the route names, which broke route:cache.
        \Laravel\Cashier\Cashier::ignoreRoutes();

        $this->app->bind(\Stripe\StripeClient::class, fn () => \Laravel\Cashier\Cashier::stripe());
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

        // Share currency symbol with all views
        // Resolved lazily and memoised, so views don't each hit the database.
        view()->composer('*', function ($view) {
            $view->with('currencySymbol', \App\Support\Currency::symbol());
        });

        Livewire::component('logo-uploader', LogoUploader::class);
    }
}
