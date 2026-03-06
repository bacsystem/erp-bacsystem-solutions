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

class SuspenderEmpresaTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createEmpresaActiva(): array
    {
        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 100]);
        $empresa = Empresa::factory()->create();
        $suscripcion = Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        $owner = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner', 'activo' => true]);
        return [$empresa, $suscripcion, $owner];
    }

    public function test_suspends_active_empresa_and_revokes_tokens(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa, $suscripcion, $owner] = $this->createEmpresaActiva();

        // Owner has an active token
        $owner->createToken('access', ['*'], now()->addMinutes(15));
        $this->assertEquals(1, $owner->tokens()->count());

        $response = $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/suspender");

        $response->assertOk();

        $suscripcion->refresh();
        $this->assertEquals('cancelada', $suscripcion->estado);
        $this->assertEquals(0, $owner->tokens()->count());

        $this->assertDatabaseHas('audit_logs', [
            'empresa_id'    => $empresa->id,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_suspend',
        ]);
    }

    public function test_returns_422_if_empresa_already_suspended(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'basic', 'nombre_display' => 'Basic', 'precio_mensual' => 50]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'cancelada',
            'fecha_vencimiento' => today()->subDays(1),
        ]);

        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/suspender")
            ->assertStatus(422);
    }

    public function test_owner_login_redirects_to_reactivar_after_suspension(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa, $suscripcion, $owner] = $this->createEmpresaActiva();

        // Give owner a password
        $owner->update(['password' => bcrypt('password123')]);

        // Suspend the empresa
        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/suspender")
            ->assertOk();

        // Owner login returns redirect to plan page (subscription cancelada)
        $this->postJson('/api/auth/login', [
            'email'    => $owner->email,
            'password' => 'password123',
        ])->assertOk()
          ->assertJsonPath('data.user.suscripcion.redirect', '/configuracion/plan');
    }
}
