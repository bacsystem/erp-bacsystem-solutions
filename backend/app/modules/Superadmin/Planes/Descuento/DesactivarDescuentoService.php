<?php

namespace App\Modules\Superadmin\Planes\Descuento;

use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DesactivarDescuentoService
{
    public function execute(string $empresaId, string $descuentoId, Superadmin $superadmin): JsonResponse
    {
        $affected = DB::table('descuentos_tenant')
            ->where('id', $descuentoId)
            ->where('empresa_id', $empresaId)
            ->update(['activo' => false, 'updated_at' => now()]);

        if (! $affected) {
            return ApiResponse::error('Descuento no encontrado.', [], 404);
        }

        return ApiResponse::success(null, 'Descuento desactivado correctamente.');
    }
}
