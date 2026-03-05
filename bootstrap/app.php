<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Daftarkan alias middleware SSO eligibility check
        $middleware->alias([
            'sso.eligible' => \App\Http\Middleware\CheckSsoEligible::class,
        ]);

        // Tambahkan middleware ke web group — akan berjalan di semua request web
        // termasuk route /oauth/authorize Passport
        // Middleware sendiri hanya memblokir pada path /oauth/authorize
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckSsoEligible::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
