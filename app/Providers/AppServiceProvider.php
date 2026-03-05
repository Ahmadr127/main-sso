<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom middleware
        $this->app['router']->aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);
        $this->app['router']->aliasMiddleware('sso.eligible', \App\Http\Middleware\CheckSsoEligible::class);

        // Laravel Passport configuration
        // loadKeysFrom WAJIB dipanggil di Passport 13 untuk inisialisasi $keyPath
        // (tanpa ini akan error "Typed static property must not be accessed before initialization")
        \Laravel\Passport\Passport::loadKeysFrom(storage_path());
        \Laravel\Passport\Passport::authorizationView('sso.authorize');
        \Laravel\Passport\Passport::tokensExpireIn(now()->addDays(1));
        \Laravel\Passport\Passport::refreshTokensExpireIn(now()->addDays(30));
        \Laravel\Passport\Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
