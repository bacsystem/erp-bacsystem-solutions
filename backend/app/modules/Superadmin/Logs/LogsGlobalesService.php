<?php

namespace App\Modules\Superadmin\Logs;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsGlobalesService
{
    public function execute(Request $request): JsonResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $query = DB::table('audit_logs')
            ->leftJoin('empresas', 'empresas.id', '=', 'audit_logs.empresa_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'audit_logs.usuario_id')
            ->select(
                'audit_logs.id',
                'audit_logs.empresa_id',
                'empresas.razon_social as empresa',
                'audit_logs.usuario_id',
                'usuarios.nombre as usuario',
                'usuarios.email as email',
                'audit_logs.superadmin_id',
                'audit_logs.accion',
                'audit_logs.ip',
                'audit_logs.created_at',
                'audit_logs.datos_anteriores',
                'audit_logs.datos_nuevos',
            )
            ->orderByDesc('audit_logs.created_at');

        if ($empresaId = $request->input('empresa_id')) {
            $query->where('audit_logs.empresa_id', $empresaId);
        }

        if ($usuarioId = $request->input('usuario_id')) {
            $query->where('audit_logs.usuario_id', $usuarioId);
        }

        if ($accion = $request->input('accion')) {
            $query->where('audit_logs.accion', $accion);
        }

        if ($superadminId = $request->input('superadmin_id')) {
            $query->where('audit_logs.superadmin_id', $superadminId);
        }

        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('audit_logs.created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('audit_logs.created_at', '<=', $fechaHasta);
        }

        $paginator = $query->paginate(50);

        return ApiResponse::paginated($paginator);
    }
}
