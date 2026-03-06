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
        'POST:api/suscripcion/yape-token',
        'POST:api/auth/logout',
        'GET:api/me',
        'GET:api/suscripcion',
        'GET:api/empresa',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $suscripcion = auth()->user()->empresa->suscripcionActiva;

        if ($suscripcion?->esCancelada()) {
            $key      = $request->method() . ':' . $request->path();
            $isExcluida = in_array($key, self::RUTAS_PERMITIDAS_VENCIDA)
                || in_array($request->method(), ['GET', 'HEAD']);

            if (! $isExcluida) {
                return ApiResponse::error(
                    'Tu suscripción está cancelada.',
                    ['redirect' => '/configuracion/plan'],
                    402
                );
            }

            return $next($request);
        }

        // Considerar vencida cuando la fecha de vencimiento ya pasó (job diario no corrió aún)
        // Se usa lt (estrictamente menor): el día de vencimiento el cobro debería renovarla.
        // Si no tiene tarjeta o el cobro falló, el job la marca 'vencida' con fecha_vencimiento = hoy
        // y esVencida() la captura en la condición anterior.
        $estaVencida = $suscripcion?->esVencida()
            || ($suscripcion?->esActiva() && $suscripcion->fecha_vencimiento->lt(today()));

        if ($estaVencida) {
            $key        = $request->method() . ':' . $request->path();
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
