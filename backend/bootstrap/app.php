<?php

use App\Shared\Http\Responses\ApiResponse;
use App\Shared\Middleware\AuditLogMiddleware;
use App\Shared\Middleware\CheckPlanMiddleware;
use App\Shared\Middleware\CheckRoleMiddleware;
use App\Shared\Middleware\SuscripcionActivaMiddleware;
use App\Shared\Middleware\TenantMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);

        $middleware->alias([
            'tenant'             => TenantMiddleware::class,
            'suscripcion.activa' => SuscripcionActivaMiddleware::class,
            'check.plan'         => CheckPlanMiddleware::class,
            'audit'              => AuditLogMiddleware::class,
            'role'               => CheckRoleMiddleware::class,
        ]);

        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: ['api/*']);

        // has_session: read client-side by Next.js middleware (no encryption needed)
        // refresh_token: Sanctum PAT is cryptographically random, no double-encryption needed
        $middleware->encryptCookies(['has_session', 'refresh_token']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $_, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('No autenticado', [], 401);
            }
        });
    })->create();
