<?php

namespace App\Modules\Superadmin\Planes\ListarPlanes;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ListarPlanesService
{
    public function execute(): JsonResponse
    {
        $planes = DB::table('planes')
            ->leftJoin('suscripciones', function ($join) {
                $join->on('suscripciones.plan_id', '=', 'planes.id')
                    ->whereIn('suscripciones.estado', ['activa', 'trial']);
            })
            ->select(
                'planes.id',
                'planes.nombre',
                'planes.nombre_display',
                'planes.precio_mensual',
                'planes.modulos',
                'planes.activo',
                DB::raw('count(suscripciones.id) as tenants_activos'),
                DB::raw('coalesce(sum(planes.precio_mensual), 0) as mrr_plan'),
            )
            ->groupBy('planes.id', 'planes.nombre', 'planes.nombre_display', 'planes.precio_mensual', 'planes.modulos', 'planes.activo')
            ->get()
            ->map(function ($plan) {
                $plan->modulos = json_decode($plan->modulos ?? '[]', true);
                return $plan;
            });

        return ApiResponse::success($planes);
    }
}
