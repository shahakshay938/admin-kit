<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use App\Http\Middleware\AlwaysAcceptJson;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        /*
        |--------------------------------------------------------------------------
        | API Routes and Mappings
        |--------------------------------------------------------------------------
        |
        | Here is where you can create and map API related routes and it's files.
        |
        */

        // API Routes Mappings
        $this->mapApiRoutes();

        /*
        |--------------------------------------------------------------------------
        | Web Routes and Mappings
        |--------------------------------------------------------------------------
        |
        | Here is where you can create and map Web related routes and it's files.
        |
        */
        $this->routes(function () {
            // Web Routes Mapping
            $this->mapWebRoutes();
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    // API Routes Mappings
    protected function mapApiRoutes()
    {
        Route::middleware(['api', AlwaysAcceptJson::class])
            ->prefix('api/v1')
            ->name('api.')
            ->group(base_path('routes/api/api.php'));

        Route::middleware(['api', AlwaysAcceptJson::class])
            ->prefix('api/v1')
            ->name('api.')
            ->group(base_path('routes/api/general.php'));
    }

    // Web Routes Mapping
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}
