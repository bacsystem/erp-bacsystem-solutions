<?php

namespace App\Modules\Core\Suscripcion\DowngradePlan;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Plan;
use Illuminate\Validation\ValidationException;

class DowngradePlanService
{
    public function execute(array $data): array
    {
        $usuario     = auth()->user();
        $suscripcion = $usuario->empresa->suscripcionActiva;
        $planNuevo   = Plan::findOrFail($data['plan_id']);

        if ($suscripcion->esCancelada()) {
            throw ValidationException::withMessages([
                'plan_id' => ['No puedes cambiar el plan de una suscripción cancelada.'],
            ]);
        }

        if ($planNuevo->id === $suscripcion->plan_id) {
            throw ValidationException::withMessages([
                'plan_id' => ['Ya estás en este plan.'],
            ]);
        }

        if (! $planNuevo->esDowngradeDe($suscripcion->plan)) {
            throw ValidationException::withMessages([
                'plan_id' => ['El plan seleccionado no es inferior al actual.'],
            ]);
        }

        $suscripcion->update(['downgrade_plan_id' => $planNuevo->id]);

        AuditLog::registrar('plan_downgrade', [
            'datos_nuevos' => ['plan_nuevo' => $planNuevo->nombre],
        ]);

        $modulosActuales = $suscripcion->plan->modulos;
        $modulosNuevos   = $planNuevo->modulos;
        $modulosPerdera  = array_values(array_diff($modulosActuales, $modulosNuevos));

        return [
            'plan_actual'         => $suscripcion->plan->nombre,
            'plan_nuevo'          => $planNuevo->nombre,
            'efectivo_desde'      => $suscripcion->fecha_vencimiento->addDay()->toDateString(),
            'modulos_que_perdera' => $modulosPerdera,
            'nuevo_max_usuarios'  => $planNuevo->max_usuarios,
        ];
    }
}
