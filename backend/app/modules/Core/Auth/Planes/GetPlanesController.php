<?php

namespace App\Modules\Core\Auth\Planes;

use App\Modules\Core\Models\Plan;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class GetPlanesController
{
    public function __invoke(): JsonResponse
    {
        $planes = Plan::activos()->get()->map(fn (Plan $p) => [
            'id'             => $p->id,
            'nombre'         => $p->nombre,
            'nombre_display' => $p->nombre_display,
            'precio_mensual' => $p->precio_mensual,
            'max_usuarios'   => $p->max_usuarios,
            'modulos'        => $p->modulos,
            'recomendado'    => $p->nombre === 'pyme',
        ]);

        return ApiResponse::success($planes->values());
    }
}
