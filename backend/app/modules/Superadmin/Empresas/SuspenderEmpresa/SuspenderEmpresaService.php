<?php

namespace App\Modules\Superadmin\Empresas\SuspenderEmpresa;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class SuspenderEmpresaService
{
    public function execute(string $empresaId, Superadmin $superadmin): JsonResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $empresa = Empresa::withoutGlobalScope('empresa')->findOrFail($empresaId);

        $suscripcion = Suscripcion::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->latest()
            ->firstOrFail();

        if ($suscripcion->estado === 'cancelada') {
            return ApiResponse::error('La empresa ya está suspendida.', [], 422);
        }

        // Suspend subscription
        $suscripcion->update(['estado' => 'cancelada']);

        // Revoke all tokens of users in this empresa
        $userIds = Usuario::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->pluck('id');

        PersonalAccessToken::whereIn('tokenable_id', $userIds)->delete();

        // Audit log
        AuditLog::create([
            'empresa_id'    => $empresaId,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_suspend',
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        return ApiResponse::success(['empresa_id' => $empresaId], 'Empresa suspendida correctamente.');
    }
}
