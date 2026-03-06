<?php

namespace App\Modules\Superadmin\Empresas\GetEmpresaDetalle;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GetEmpresaDetalleService
{
    public function execute(string $empresaId): JsonResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $empresa = Empresa::withoutGlobalScope('empresa')
            ->find($empresaId);

        if (! $empresa) {
            return ApiResponse::error('Empresa no encontrada', [], 404);
        }

        $usuarios = Usuario::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->get(['id', 'nombre', 'email', 'rol', 'activo', 'last_login']);

        $suscripciones = Suscripcion::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->with('plan:id,nombre,nombre_display,precio_mensual')
            ->orderByDesc('created_at')
            ->get();

        $auditLogs = AuditLog::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'usuario_id', 'accion', 'ip', 'created_at', 'datos_anteriores', 'datos_nuevos']);

        $suscripcionActiva = $suscripciones->first();
        $diasActivo = $empresa->created_at
            ? (int) $empresa->created_at->diffInDays(now())
            : 0;

        $metricas = [
            'mrr'           => $suscripcionActiva?->plan?->precio_mensual ?? 0,
            'dias_activo'   => $diasActivo,
            'total_usuarios' => $usuarios->count(),
        ];

        return ApiResponse::success([
            'id'             => $empresa->id,
            'razon_social'   => $empresa->razon_social,
            'nombre_comercial' => $empresa->nombre_comercial,
            'ruc'            => $empresa->getRawOriginal('ruc'),
            'logo_url'       => $empresa->logo_url,
            'created_at'     => $empresa->created_at,
            'suscripcion'    => $suscripcionActiva,
            'suscripciones'  => $suscripciones,
            'usuarios'       => $usuarios,
            'audit_logs'     => $auditLogs,
            'metricas'       => $metricas,
        ]);
    }
}
