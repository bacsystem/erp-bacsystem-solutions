<?php

namespace Tests\Feature\Superadmin\Planes;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class DescuentoTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createEmpresaConPlan(): array
    {
        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 100]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        return [$empresa, $plan];
    }

    public function test_creates_porcentaje_discount_correctly(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa] = $this->createEmpresaConPlan();

        $response = $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/descuento", [
                'tipo'   => 'porcentaje',
                'valor'  => 20,
                'motivo' => 'Descuento por fidelidad',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('descuentos_tenant', [
            'empresa_id'    => $empresa->id,
            'superadmin_id' => $superadmin->id,
            'tipo'          => 'porcentaje',
            'valor'         => 20,
            'activo'        => 1,
        ]);
    }

    public function test_creates_monto_fijo_discount(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa] = $this->createEmpresaConPlan();

        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/descuento", [
                'tipo'   => 'monto_fijo',
                'valor'  => 30.00,
                'motivo' => 'Descuento especial',
            ])
            ->assertOk();

        $this->assertDatabaseHas('descuentos_tenant', [
            'empresa_id' => $empresa->id,
            'tipo'       => 'monto_fijo',
            'valor'      => 30.00,
        ]);
    }

    public function test_deactivates_previous_discount_when_creating_new(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa] = $this->createEmpresaConPlan();

        // Create first discount
        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/descuento", [
                'tipo'   => 'porcentaje',
                'valor'  => 10,
                'motivo' => 'Primer descuento',
            ])
            ->assertOk();

        // Create second discount (should deactivate first)
        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/descuento", [
                'tipo'   => 'porcentaje',
                'valor'  => 20,
                'motivo' => 'Segundo descuento',
            ])
            ->assertOk();

        // Only 1 active discount
        $activeCount = \DB::table('descuentos_tenant')
            ->where('empresa_id', $empresa->id)
            ->where('activo', 1)
            ->count();

        $this->assertEquals(1, $activeCount);
    }

    public function test_rejects_porcentaje_greater_than_100(): void
    {
        [, $token] = $this->actingAsSuperadmin();
        [$empresa] = $this->createEmpresaConPlan();

        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/descuento", [
                'tipo'   => 'porcentaje',
                'valor'  => 150,
                'motivo' => 'Descuento inválido',
            ])
            ->assertStatus(422);
    }

    public function test_deactivate_discount_works(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa] = $this->createEmpresaConPlan();

        // Create discount
        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/descuento", [
                'tipo'   => 'porcentaje',
                'valor'  => 15,
                'motivo' => 'Descuento a desactivar',
            ])
            ->assertOk();

        $descuento = \DB::table('descuentos_tenant')
            ->where('empresa_id', $empresa->id)
            ->where('activo', 1)
            ->first();

        $this->withToken($token)
            ->deleteJson("/superadmin/api/empresas/{$empresa->id}/descuento/{$descuento->id}")
            ->assertOk();

        $this->assertDatabaseHas('descuentos_tenant', [
            'id'     => $descuento->id,
            'activo' => 0,
        ]);
    }
}
