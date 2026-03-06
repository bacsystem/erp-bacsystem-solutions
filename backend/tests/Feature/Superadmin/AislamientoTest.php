<?php

namespace Tests\Feature\Superadmin;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Modules\Superadmin\Models\Superadmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class AislamientoTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createTenantToken(): string
    {
        $plan = Plan::factory()->create(['nombre' => 'pyme_aislamiento', 'nombre_display' => 'PYME']);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        $owner = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner', 'activo' => true]);
        return $owner->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;
    }

    public function test_tenant_token_on_superadmin_dashboard_returns_403(): void
    {
        $tenantToken = $this->createTenantToken();

        $this->withToken($tenantToken)
            ->getJson('/superadmin/api/dashboard')
            ->assertStatus(403);
    }

    public function test_superadmin_token_on_tenant_me_returns_401(): void
    {
        [, $saToken] = $this->actingAsSuperadmin();

        $this->withToken($saToken)
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_superadmin_token_on_tenant_empresa_returns_401(): void
    {
        [, $saToken] = $this->actingAsSuperadmin();

        $this->withToken($saToken)
            ->getJson('/api/empresa')
            ->assertStatus(401);
    }

    public function test_no_token_on_protected_superadmin_route_returns_401(): void
    {
        $this->getJson('/superadmin/api/dashboard')
            ->assertStatus(401);
    }

    public function test_no_token_on_superadmin_empresas_returns_401(): void
    {
        $this->getJson('/superadmin/api/empresas')
            ->assertStatus(401);
    }
}
