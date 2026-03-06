<?php

namespace Tests\Feature\Superadmin\Planes;

use App\Modules\Core\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class UpdatePlanTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    public function test_updates_price_correctly(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 100.00]);

        $response = $this->withToken($token)
            ->putJson("/superadmin/api/planes/{$plan->id}", [
                'precio_mensual' => 150.00,
            ]);

        $response->assertOk();

        $plan->refresh();
        $this->assertEquals('150.00', $plan->precio_mensual);
    }

    public function test_updates_modules_correctly(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        $plan = Plan::factory()->create([
            'nombre'         => 'basic',
            'nombre_display' => 'Basic',
            'precio_mensual' => 50.00,
            'modulos'        => ['facturacion', 'clientes'],
        ]);

        $response = $this->withToken($token)
            ->putJson("/superadmin/api/planes/{$plan->id}", [
                'modulos' => ['facturacion', 'clientes', 'inventario'],
            ]);

        $response->assertOk();

        $plan->refresh();
        $this->assertEquals(['facturacion', 'clientes', 'inventario'], $plan->modulos);
    }

    public function test_records_audit_log_for_plan_update(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        $plan = Plan::factory()->create(['nombre' => 'pro', 'nombre_display' => 'Pro', 'precio_mensual' => 200.00]);

        $this->withToken($token)
            ->putJson("/superadmin/api/planes/{$plan->id}", [
                'precio_mensual' => 250.00,
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_update_plan',
        ]);
    }
}
