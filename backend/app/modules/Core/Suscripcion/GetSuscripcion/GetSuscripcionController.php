<?php

namespace App\Modules\Core\Suscripcion\GetSuscripcion;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class GetSuscripcionController
{
    public function __invoke(): JsonResponse
    {
        $usuario     = auth()->user();
        $empresa     = $usuario->empresa;
        $suscripcion = $empresa->suscripcionActiva;
        $plan        = $suscripcion->plan;

        $diasRestantes = max(0, now()->diffInDays($suscripcion->fecha_vencimiento, false));

        return ApiResponse::success([
            'id'                   => $suscripcion->id,
            'plan'                 => [
                'id'             => $plan->id,
                'nombre'         => $plan->nombre,
                'nombre_display' => $plan->nombre_display,
                'precio_mensual' => $plan->precio_mensual,
                'max_usuarios'   => $plan->max_usuarios,
                'modulos'        => $plan->modulos,
            ],
            'estado'               => $suscripcion->estado,
            'fecha_inicio'         => $suscripcion->fecha_inicio->toDateString(),
            'fecha_vencimiento'    => $suscripcion->fecha_vencimiento->toDateString(),
            'fecha_proximo_cobro'  => $suscripcion->fecha_proximo_cobro?->toDateString(),
            'dias_restantes'       => (int) $diasRestantes,
            'culqi_subscription_id'=> $suscripcion->culqi_subscription_id,
            'datos_pago'           => [
                'tiene_tarjeta' => $suscripcion->culqi_card_id !== null,
                'card_last4'    => $suscripcion->card_last4,
                'card_brand'    => $suscripcion->card_brand,
            ],
        ]);
    }
}
