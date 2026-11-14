<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::group([], base_path('routes/internal.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // Client favourites: hearts and sends counted separately, per visitor.
        RateLimiter::for('favourites', fn (Request $request) => Limit::perMinute(120)->by('fav:'.$request->ip()));
        RateLimiter::for('favourites-submit', fn (Request $request) => Limit::perMinute(6)->by('fav-submit:'.$request->ip()));

        // Downloading several photos as a ZIP: per visitor, and per gallery.
        RateLimiter::for('bulk-downloads', fn (Request $request) => [
            Limit::perMinute(10)->by('dl:'.$request->ip()),
            Limit::perHour(60)->by('dl:'.$request->ip().'|'.($request->input('collection_id') ?: $request->input('set_id') ?: 'photos')),
        ]);
        RateLimiter::for('bulk-downloads-stream', fn (Request $request) => Limit::perMinute(10)->by('dl-stream:'.$request->ip()));

        // Previews of personalised prints are drawn on the server.
        RateLimiter::for('print-preview', fn (Request $request) => Limit::perMinute(20)->by('print-preview:'.$request->ip()));

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
