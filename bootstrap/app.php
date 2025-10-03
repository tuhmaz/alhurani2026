<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\VisitorTrackingMiddleware;
use App\Http\Middleware\UpdateUserLastActivity;
use App\Http\Middleware\LogLastActivity;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\RequestMonitorMiddleware;
use App\Http\Middleware\SecurityScanMiddleware;
use App\Http\Middleware\StripContentEncodingHeader;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
      api: __DIR__ . '/../routes/api.php',
      web: __DIR__ . '/../routes/web.php',
      commands: __DIR__ . '/../routes/console.php',
      health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middlewares
        $middleware->use([\Illuminate\Http\Middleware\HandleCors::class]);
        // Web middlewares
        $middleware->web([
            LocaleMiddleware::class,
            CompressResponse::class,
            VisitorTrackingMiddleware::class,
            UpdateUserLastActivity::class,
            LogLastActivity::class,
            // Security monitoring middlewares
            RequestMonitorMiddleware::class,
            SecurityScanMiddleware::class,
            SecurityHeaders::class,
            StripContentEncodingHeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
