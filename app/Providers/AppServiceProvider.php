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
        //
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
