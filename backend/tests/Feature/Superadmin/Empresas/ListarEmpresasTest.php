<?php

namespace Tests\Feature\Superadmin\Empresas;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class ListarEmpresasTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createEmpresaConPlan(string $estado = 'activa', array $planData = []): array
    {
        $plan = Plan::factory()->create(array_merge([
            'nombre'         => 'plan_' . uniqid(),
            'nombre_display' => 'Plan',
            'precio_mensual' => 100.00,
        ], $planData));
        $empresa = Empresa::factory()->create();
        $suscripcion = Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => $estado,
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        return [$empresa, $plan, $suscripcion];
    }

    public function test_returns_all_empresas_without_tenant_filter(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $this->createEmpresaConPlan('activa');
        $this->createEmpresaConPlan('trial');

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/empresas');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'razon_social', 'plan', 'estado', 'mrr', 'fecha_registro']],
                'meta' => ['page', 'per_page', 'total'],
            ]);

        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_filter_by_estado_works(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $this->createEmpresaConPlan('activa');
        $this->createEmpresaConPlan('cancelada');

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/empresas?estado=activa');

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('activa', $response->json('data.0.estado'));
    }

    public function test_search_by_name_works(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        Empresa::factory()->create(['razon_social' => 'Empresa ABC SAC']);
        Empresa::factory()->create(['razon_social' => 'Empresa XYZ SRL']);

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/empresas?q=ABC');

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertStringContainsString('ABC', $response->json('data.0.razon_social'));
    }

    public function test_pagination_returns_25_per_page(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/empresas');

        $response->assertOk();
        $this->assertEquals(25, $response->json('meta.per_page'));
    }
}
