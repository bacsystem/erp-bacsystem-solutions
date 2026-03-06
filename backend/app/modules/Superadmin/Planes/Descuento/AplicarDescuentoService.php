<?php

namespace App\Modules\Superadmin\Planes\Descuento;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AplicarDescuentoService
{
    public function execute(string $empresaId, array $data, Superadmin $superadmin): JsonResponse
    {
        // Deactivate existing active discount
        DB::table('descuentos_tenant')
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->update(['activo' => false]);

        // Create new discount
        $id = (string) Str::uuid();
        DB::table('descuentos_tenant')->insert([
            'id'            => $id,
            'empresa_id'    => $empresaId,
            'superadmin_id' => $superadmin->id,
            'tipo'          => $data['tipo'],
            'valor'         => $data['valor'],
            'motivo'        => $data['motivo'],
            'activo'        => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        AuditLog::create([
            'empresa_id'    => $empresaId,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_apply_discount',
            'datos_nuevos'  => $data,
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        return ApiResponse::success(['id' => $id], 'Descuento aplicado correctamente.');
    }
}
