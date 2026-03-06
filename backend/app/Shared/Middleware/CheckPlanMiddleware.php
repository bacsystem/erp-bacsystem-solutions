<?php

namespace App\Shared\Middleware;

use App\Shared\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanMiddleware
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $suscripcion = auth()->user()->empresa->suscripcionActiva;

        if (! $suscripcion || $suscripcion->esCancelada()) {
            return ApiResponse::error('Tu suscripción está cancelada.', [], 402);
        }

        $plan = $suscripcion->plan;

        if (! in_array($modulo, $plan->modulos)) {
            return ApiResponse::error(
                "Tu plan no incluye el módulo '{$modulo}'. Mejora tu plan para acceder.",
                [],
                403
            );
        }

        return $next($request);
    }
}
