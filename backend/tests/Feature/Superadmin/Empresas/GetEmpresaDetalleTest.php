<?php

namespace Tests\Feature\Superadmin\Empresas;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class GetEmpresaDetalleTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    public function test_returns_full_empresa_detail(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'basic', 'nombre_display' => 'Basic', 'precio_mensual' => 100]);
        $empresa = Empresa::factory()->create(['razon_social' => 'Empresa Test SAC']);
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner']);

        $response = $this->withToken($token)
            ->getJson("/superadmin/api/empresas/{$empresa->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'razon_social', 'ruc',
                    'suscripcion', 'usuarios', 'audit_logs', 'metricas',
                ],
            ]);

        $this->assertEquals('Empresa Test SAC', $response->json('data.razon_social'));
    }

    public function test_includes_users_and_audit_logs(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 150]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        $usuario = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner']);

        AuditLog::create([
            'empresa_id' => $empresa->id,
            'usuario_id' => $usuario->id,
            'accion'     => 'login',
            'ip'         => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->withToken($token)
            ->getJson("/superadmin/api/empresas/{$empresa->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.usuarios'));
        $this->assertCount(1, $response->json('data.audit_logs'));
    }

    public function test_returns_404_for_nonexistent_empresa(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $this->withToken($token)
            ->getJson('/superadmin/api/empresas/non-existent-id')
            ->assertNotFound();
    }
}
