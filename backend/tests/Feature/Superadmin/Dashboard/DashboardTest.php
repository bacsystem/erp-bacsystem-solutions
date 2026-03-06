<?php

namespace Tests\Feature\Superadmin\Dashboard;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    public function test_returns_dashboard_with_zeros_when_no_empresas(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'mrr_total',
                    'totales_por_estado',
                    'nuevos_hoy',
                    'nuevos_mes',
                    'tasa_conversion',
                    'churn',
                    'mrr_historico',
                ],
            ]);

        $this->assertEquals(0, $response->json('data.mrr_total'));
        $this->assertCount(6, $response->json('data.mrr_historico'));
    }

    public function test_calculates_mrr_total_from_active_subscriptions(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['precio_mensual' => 100.00, 'nombre' => 'basic', 'nombre_display' => 'Basic']);
        $plan2 = Plan::factory()->create(['precio_mensual' => 200.00, 'nombre' => 'pro', 'nombre_display' => 'Pro']);

        $empresa1 = Empresa::factory()->create();
        $empresa2 = Empresa::factory()->create();

        Suscripcion::factory()->create([
            'empresa_id'        => $empresa1->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);

        Suscripcion::factory()->create([
            'empresa_id'        => $empresa2->id,
            'plan_id'           => $plan2->id,
            'estado'            => 'trial',
            'fecha_vencimiento' => today()->addDays(14),
        ]);

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/dashboard');

        $response->assertOk();
        // MRR includes activa + trial
        $this->assertEquals(300.00, $response->json('data.mrr_total'));
    }

    public function test_counts_empresas_by_status(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['precio_mensual' => 100.00, 'nombre' => 'basic2', 'nombre_display' => 'Basic']);

        $this->createEmpresaWithSuscripcion($plan->id, 'activa');
        $this->createEmpresaWithSuscripcion($plan->id, 'trial');
        $this->createEmpresaWithSuscripcion($plan->id, 'cancelada');

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/dashboard');

        $response->assertOk();
        $totales = $response->json('data.totales_por_estado');

        $this->assertEquals(1, $totales['activa']);
        $this->assertEquals(1, $totales['trial']);
        $this->assertEquals(1, $totales['cancelada']);
    }

    private function createEmpresaWithSuscripcion(string $planId, string $estado): void
    {
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $planId,
            'estado'            => $estado,
            'fecha_vencimiento' => today()->addDays(30),
        ]);
    }
}
