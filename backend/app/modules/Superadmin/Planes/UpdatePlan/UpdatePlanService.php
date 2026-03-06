<?php

namespace App\Modules\Superadmin\Planes\UpdatePlan;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Plan;
use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpdatePlanService
{
    public function execute(string $planId, array $data, Superadmin $superadmin): JsonResponse
    {
        $plan = Plan::findOrFail($planId);

        $datosAnteriores = $plan->only(array_keys($data));
        $plan->update($data);

        AuditLog::create([
            'empresa_id'       => null,
            'usuario_id'       => null,
            'superadmin_id'    => $superadmin->id,
            'accion'           => 'superadmin_update_plan',
            'tabla_afectada'   => 'planes',
            'registro_id'      => $planId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos'     => $data,
            'ip'               => request()->ip(),
            'created_at'       => now(),
        ]);

        return ApiResponse::success($plan->fresh(), 'Plan actualizado correctamente.');
    }
}
