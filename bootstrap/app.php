<?php

use App\Http\Middleware\EnsureIsDeveloper;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureStoreHasAccess;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Session\Middleware\AuthenticatesSessions;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'store.access' => EnsureStoreHasAccess::class,
            'permission' => EnsurePermission::class,
            'developer' => EnsureIsDeveloper::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'midtrans/notification',
        ]);

        // Laravel's new bootstrap style ships with an empty middleware
        // priority list, so route middleware runs in attachment order
        // instead of this safe default order. Without this, implicit route
        // model binding (SubstituteBindings) resolves BEFORE auth:sanctum
        // authenticates the request, so store-scoped models' BelongsToStore
        // global scope (which reads Auth::user()) silently finds no
        // authenticated user yet and returns any store's record.
        $middleware->priority([
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            AuthenticatesRequests::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            AuthenticatesSessions::class,
            SubstituteBindings::class,
            Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
