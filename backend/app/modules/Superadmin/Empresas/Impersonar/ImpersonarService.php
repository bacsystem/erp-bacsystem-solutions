<?php

namespace App\Modules\Superadmin\Empresas\Impersonar;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Usuario;
use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ImpersonarService
{
    public function execute(string $empresaId, Superadmin $superadmin): JsonResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $empresa = Empresa::withoutGlobalScope('empresa')->findOrFail($empresaId);

        $owner = Usuario::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->where('rol', 'owner')
            ->where('activo', true)
            ->first();

        if (! $owner) {
            return ApiResponse::error('No hay un owner activo en esta empresa.', [], 422);
        }

        // Create impersonation token (2h expiry)
        $tokenResult = $owner->createToken(
            'impersonation',
            ['impersonated'],
            now()->addHours(2)
        );

        $plainToken = $tokenResult->plainTextToken;

        // Extract token without prefix (Sanctum format: id|token)
        $tokenValue = explode('|', $plainToken, 2)[1] ?? $plainToken;
        $tokenHash = hash('sha256', $tokenValue);

        // Save to impersonation_logs
        DB::table('impersonation_logs')->insert([
            'id'            => (string) \Illuminate\Support\Str::uuid(),
            'superadmin_id' => $superadmin->id,
            'empresa_id'    => $empresaId,
            'token_hash'    => $tokenHash,
            'started_at'    => now(),
            'ended_at'      => null,
            'ip'            => request()->ip(),
        ]);

        AuditLog::create([
            'empresa_id'    => $empresaId,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_impersonation_start',
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        return ApiResponse::success([
            'token'   => $plainToken,
            'empresa' => ['id' => $empresa->id, 'razon_social' => $empresa->razon_social],
            'owner'   => ['id' => $owner->id, 'nombre' => $owner->nombre, 'email' => $owner->email],
        ]);
    }
}
