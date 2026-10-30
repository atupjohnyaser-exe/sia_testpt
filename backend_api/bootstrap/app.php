<?php

use App\Http\Middleware\ApiSecurityHeaders;
use App\Http\Middleware\EnsureClearance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'clearance' => EnsureClearance::class,
        ]);

        $middleware->api(append: [
            ApiSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This app only serves an API, so every error is JSON.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })
    ->create();
