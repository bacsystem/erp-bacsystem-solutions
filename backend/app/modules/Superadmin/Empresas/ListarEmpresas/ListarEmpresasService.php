<?php

namespace App\Modules\Superadmin\Empresas\ListarEmpresas;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListarEmpresasService
{
    public function execute(Request $request): JsonResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $query = DB::table('empresas')
            ->leftJoin('suscripciones', function ($join) {
                $join->on('suscripciones.empresa_id', '=', 'empresas.id')
                    ->whereIn('suscripciones.estado', ['activa', 'trial', 'vencida', 'cancelada']);
            })
            ->leftJoin('planes', 'planes.id', '=', 'suscripciones.plan_id')
            ->select(
                'empresas.id',
                'empresas.razon_social',
                'empresas.nombre_comercial',
                DB::raw("empresas.ruc as ruc"),
                'empresas.created_at as fecha_registro',
                'planes.nombre_display as plan',
                'suscripciones.estado',
                DB::raw('COALESCE(planes.precio_mensual, 0) as mrr'),
            );

        if ($q = $request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('empresas.razon_social', 'like', "%{$q}%")
                    ->orWhere('empresas.nombre_comercial', 'like', "%{$q}%")
                    ->orWhere('empresas.ruc', 'like', "%{$q}%");
            });
        }

        if ($estado = $request->input('estado')) {
            $query->where('suscripciones.estado', $estado);
        }

        if ($plan = $request->input('plan')) {
            $query->where('planes.nombre', $plan);
        }

        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('empresas.created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('empresas.created_at', '<=', $fechaHasta);
        }

        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query->orderBy("empresas.{$sort}", $order);

        $paginator = $query->paginate(25);

        return ApiResponse::paginated($paginator);
    }
}
