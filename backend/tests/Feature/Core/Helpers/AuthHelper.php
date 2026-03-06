<?php

namespace Tests\Feature\Core\Helpers;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;

trait AuthHelper
{
    protected function actingAsOwner(array $planData = []): array
    {
        static $planCounter = 0;
        $planCounter++;
        $plan = Plan::factory()->create(array_merge([
            'nombre'         => substr('plan_' . uniqid(), 0, 20),
            'nombre_display' => 'PYME',
            'precio_mensual' => 129.00,
            'max_usuarios'   => 15,
            'modulos'        => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia'],
        ], $planData));

        $empresa = Empresa::factory()->create();

        $suscripcion = Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'trial',
            'fecha_vencimiento' => today()->addDays(30),
        ]);

        $owner = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        $token = $owner->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;

        return [$owner, $empresa, $suscripcion, $plan, $token];
    }

    public function loginAs(Usuario $usuario): string
    {
        return $usuario->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;
    }
}
