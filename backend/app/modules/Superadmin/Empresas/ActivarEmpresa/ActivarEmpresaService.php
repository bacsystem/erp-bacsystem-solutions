<?php

namespace App\Modules\Superadmin\Empresas\ActivarEmpresa;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use App\Shared\Mail\ReactivacionMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ActivarEmpresaService
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

        if (in_array($suscripcion->estado, ['activa', 'trial'])) {
            return ApiResponse::error('La empresa ya está activa.', [], 422);
        }

        $suscripcion->update([
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);

        // Send reactivation email to owner
        $owner = Usuario::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->where('rol', 'owner')
            ->where('activo', true)
            ->first();

        if ($owner) {
            Mail::to($owner->email)->send(new ReactivacionMail($empresa));
        }

        AuditLog::create([
            'empresa_id'    => $empresaId,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_activate',
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        return ApiResponse::success(['empresa_id' => $empresaId], 'Empresa reactivada correctamente.');
    }
}
