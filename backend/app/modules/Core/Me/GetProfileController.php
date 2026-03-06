<?php

namespace App\Modules\Core\Me;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class GetProfileController
{
    public function __invoke(): JsonResponse
    {
        $usuario     = auth()->user();
        $empresa     = $usuario->empresa;
        $suscripcion = $empresa->suscripcionActiva;

        return ApiResponse::success([
            'id'     => $usuario->id,
            'nombre' => $usuario->nombre,
            'email'  => $usuario->getRawOriginal('email'),
            'rol'    => $usuario->rol,
            'empresa' => [
                'id'               => $empresa->id,
                'razon_social'     => $empresa->razon_social,
                'nombre_comercial' => $empresa->nombre_comercial,
                'ruc'              => $empresa->getRawOriginal('ruc'),
                'logo_url'         => $empresa->logo_url,
            ],
            'suscripcion' => [
                'estado'   => $suscripcion?->estado,
                'plan'     => $suscripcion?->plan?->nombre,
                'modulos'  => $suscripcion?->estado === 'cancelada' ? [] : ($suscripcion?->plan?->modulos ?? []),
                'redirect' => $suscripcion?->estado === 'cancelada' ? '/configuracion/plan' : null,
            ],
        ]);
    }
}
