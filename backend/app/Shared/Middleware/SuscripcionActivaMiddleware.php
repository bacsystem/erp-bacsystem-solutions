<?php

namespace App\Shared\Middleware;

use App\Shared\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuscripcionActivaMiddleware
{
    // Rutas siempre permitidas incluso con suscripción vencida
    private const RUTAS_PERMITIDAS_VENCIDA = [
        'POST:api/suscripcion/upgrade',
        'POST:api/auth/logout',
        'GET:api/me',
        'GET:api/suscripcion',
        'GET:api/empresa',          // necesario para /reactivar
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $suscripcion = auth()->user()->empresa->suscripcionActiva;

        if ($suscripcion?->esCancelada()) {
            // Permitir solo GET /api/empresa para la pantalla /reactivar
            if ($request->method() === 'GET' && $request->path() === 'api/empresa') {
                return $next($request);
            }

            return ApiResponse::error(
                'Tu suscripción está cancelada.',
                ['redirect' => '/reactivar'],
                402
            );
        }

        if ($suscripcion?->esVencida()) {
            $key       = $request->method() . ':' . $request->path();
            $isReadOnly = in_array($request->method(), ['GET', 'HEAD']);
            $isExcluida = in_array($key, self::RUTAS_PERMITIDAS_VENCIDA);

            if (! $isReadOnly && ! $isExcluida) {
                return ApiResponse::error(
                    'Tu suscripción ha vencido. Activa tu plan para continuar operando.',
                    ['redirect' => '/configuracion/plan'],
                    402
                );
            }
        }

        return $next($request);
    }
}
