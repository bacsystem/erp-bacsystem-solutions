<?php

namespace Tests\Feature\Superadmin\Logs;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class LogsGlobalesTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createLog(array $attrs = []): void
    {
        AuditLog::create(array_merge([
            'empresa_id' => null,
            'usuario_id' => null,
            'accion'     => 'login',
            'ip'         => '127.0.0.1',
            'created_at' => now(),
        ], $attrs));
    }

    public function test_returns_all_logs_without_tenant_filter(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 100]);
        $empresa1 = Empresa::factory()->create();
        $empresa2 = Empresa::factory()->create();

        Suscripcion::factory()->create(['empresa_id' => $empresa1->id, 'plan_id' => $plan->id, 'estado' => 'activa', 'fecha_vencimiento' => today()->addDays(30)]);
        Suscripcion::factory()->create(['empresa_id' => $empresa2->id, 'plan_id' => $plan->id, 'estado' => 'activa', 'fecha_vencimiento' => today()->addDays(30)]);

        $this->createLog(['empresa_id' => $empresa1->id, 'accion' => 'login']);
        $this->createLog(['empresa_id' => $empresa2->id, 'accion' => 'logout']);
        $this->createLog(['empresa_id' => null, 'accion' => 'superadmin_login']);

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'accion', 'ip', 'created_at']],
                'meta' => ['page', 'per_page', 'total'],
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_filter_by_empresa_id(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'basic', 'nombre_display' => 'Basic', 'precio_mensual' => 50]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create(['empresa_id' => $empresa->id, 'plan_id' => $plan->id, 'estado' => 'activa', 'fecha_vencimiento' => today()->addDays(30)]);

        $this->createLog(['empresa_id' => $empresa->id, 'accion' => 'login']);
        $this->createLog(['empresa_id' => null, 'accion' => 'superadmin_login']);

        $response = $this->withToken($token)
            ->getJson("/superadmin/api/logs?empresa_id={$empresa->id}");

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_filter_by_accion(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $this->createLog(['accion' => 'login']);
        $this->createLog(['accion' => 'login_failed']);
        $this->createLog(['accion' => 'login_failed']);

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/logs?accion=login_failed');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_includes_logs_with_null_empresa_id(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $this->createLog(['empresa_id' => null, 'accion' => 'superadmin_login']);

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/logs');

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_pagination_returns_50_per_page(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/logs');

        $response->assertOk();
        $this->assertEquals(50, $response->json('meta.per_page'));
    }

    public function test_ordered_by_created_at_desc(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $this->createLog(['accion' => 'login', 'created_at' => now()->subHours(2)]);
        $this->createLog(['accion' => 'logout', 'created_at' => now()]);

        $response = $this->withToken($token)
            ->getJson('/superadmin/api/logs');

        $response->assertOk();
        $this->assertEquals('logout', $response->json('data.0.accion'));
    }
}
