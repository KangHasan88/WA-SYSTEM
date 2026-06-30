<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Webhook routes (no CSRF, no session)
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/webhook.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add webhook to CSRF exception
        $middleware->validateCsrfTokens(except: [
            'wa-webhook/*',
            'api/wa-webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();