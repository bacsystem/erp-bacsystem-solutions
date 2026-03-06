<?php

namespace App\Modules\Superadmin\Empresas\Impersonar;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class TerminarImpersonacionService
{
    public function execute(string $empresaId, Superadmin $superadmin): JsonResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $log = DB::table('impersonation_logs')
            ->where('empresa_id', $empresaId)
            ->where('superadmin_id', $superadmin->id)
            ->whereNull('ended_at')
            ->first();

        if (! $log) {
            return ApiResponse::error('No hay una sesión de impersonación activa.', [], 404);
        }

        // Delete the impersonation token by matching the hash
        // Sanctum tokens are hashed with SHA-256 in the database
        PersonalAccessToken::where('name', 'impersonation')
            ->where('tokenable_type', \App\Modules\Core\Models\Usuario::class)
            ->get()
            ->each(function ($token) use ($log) {
                if (hash('sha256', $token->token) === $log->token_hash) {
                    $token->delete();
                }
            });

        // Update ended_at
        DB::table('impersonation_logs')
            ->where('id', $log->id)
            ->update(['ended_at' => now()]);

        AuditLog::create([
            'empresa_id'    => $empresaId,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_impersonation_end',
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        return ApiResponse::success(null, 'Sesión de impersonación terminada.');
    }
}
