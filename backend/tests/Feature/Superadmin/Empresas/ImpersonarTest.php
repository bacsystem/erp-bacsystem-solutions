<?php

namespace Tests\Feature\Superadmin\Empresas;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class ImpersonarTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createEmpresaConOwner(): array
    {
        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 100]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        $owner = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'owner',
            'activo'     => true,
        ]);
        return [$empresa, $owner];
    }

    public function test_impersonation_returns_token_with_impersonated_ability(): void
    {
        [$superadmin, $saToken] = $this->actingAsSuperadmin();
        [$empresa, $owner] = $this->createEmpresaConOwner();

        $response = $this->withToken($saToken)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/impersonar");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'empresa', 'owner']]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Verify log was created
        $this->assertDatabaseHas('impersonation_logs', [
            'empresa_id'    => $empresa->id,
            'superadmin_id' => $superadmin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'empresa_id'    => $empresa->id,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_impersonation_start',
        ]);
    }

    public function test_impersonation_token_expires_in_2_hours(): void
    {
        [$superadmin, $saToken] = $this->actingAsSuperadmin();
        [$empresa, $owner] = $this->createEmpresaConOwner();

        $this->withToken($saToken)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/impersonar")
            ->assertOk();

        $token = $owner->tokens()->where('name', 'impersonation')->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->diffInHours(now()) <= 2);
    }

    public function test_saves_token_hash_in_impersonation_logs(): void
    {
        [$superadmin, $saToken] = $this->actingAsSuperadmin();
        [$empresa, $owner] = $this->createEmpresaConOwner();

        $this->withToken($saToken)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/impersonar")
            ->assertOk();

        $log = \DB::table('impersonation_logs')
            ->where('empresa_id', $empresa->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->token_hash);
        $this->assertEquals(64, strlen($log->token_hash)); // SHA-256 hex
    }

    public function test_empresa_without_active_owner_returns_422(): void
    {
        [$superadmin, $saToken] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'basic', 'nombre_display' => 'Basic', 'precio_mensual' => 50]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        // No owner created

        $this->withToken($saToken)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/impersonar")
            ->assertStatus(422);
    }

    public function test_end_impersonation_invalidates_token_and_updates_ended_at(): void
    {
        [$superadmin, $saToken] = $this->actingAsSuperadmin();
        [$empresa, $owner] = $this->createEmpresaConOwner();

        // Start impersonation
        $this->withToken($saToken)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/impersonar")
            ->assertOk();

        $this->assertDatabaseHas('impersonation_logs', [
            'empresa_id' => $empresa->id,
            'ended_at'   => null,
        ]);

        // End impersonation
        $this->withToken($saToken)
            ->deleteJson("/superadmin/api/empresas/{$empresa->id}/impersonar")
            ->assertOk();

        $this->assertDatabaseMissing('impersonation_logs', [
            'empresa_id' => $empresa->id,
            'ended_at'   => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'empresa_id' => $empresa->id,
            'accion'     => 'superadmin_impersonation_end',
        ]);
    }
}
