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

    /**
     * Creates a tenant context (empresa + suscripcion + usuario) and returns [usuario, token].
     */
    protected function actingAsTenant(string $rol = 'owner'): array
    {
        $plan = Plan::factory()->create([
            'nombre'         => substr('plan_' . uniqid(), 0, 20),
            'nombre_display' => 'PYME',
            'precio_mensual' => 129.00,
            'max_usuarios'   => 15,
            'modulos'        => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia'],
        ]);

        $empresa = Empresa::factory()->create();

        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);

        $usuario = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => $rol,
            'activo'     => true,
        ]);

        $token = $usuario->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;

        return [$usuario, $token];
    }

    /**
     * Creates a user with the given role in an existing empresa, returns [usuario, token].
     */
    protected function actingAsTenantWithSameEmpresa(string $empresaId, string $rol = 'empleado'): array
    {
        $usuario = Usuario::factory()->create([
            'empresa_id' => $empresaId,
            'rol'        => $rol,
            'activo'     => true,
        ]);

        $token = $usuario->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;

        return [$usuario, $token];
    }
}
