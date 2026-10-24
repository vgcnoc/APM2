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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\NormalizeCoordinates::class,
            \App\Http\Middleware\AffiliatePortalSession::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\NormalizeCoordinates::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'payment-gateway/callback',
            'api/payment-gateway/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'error' => 'Sesi Anda telah diperbarui, silakan ulangi kembali.',
                ]);
            }

            return $response;
        });
    })->create();
