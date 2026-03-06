<?php

namespace Tests\Feature\Superadmin\Auth;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Modules\Superadmin\Models\Superadmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class LoginSuperadminTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createSuperadmin(array $attrs = []): Superadmin
    {
        return Superadmin::factory()->create(array_merge([
            'email'    => 'admin@operaai.com',
            'password' => Hash::make('secret123'),
        ], $attrs));
    }

    public function test_returns_token_on_valid_credentials(): void
    {
        $this->createSuperadmin();

        $response = $this->postJson('/superadmin/api/auth/login', [
            'email'    => 'admin@operaai.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'superadmin']])
            ->assertJsonPath('data.superadmin.email', 'admin@operaai.com');

        $this->assertDatabaseHas('audit_logs', ['accion' => 'superadmin_login']);
    }

    public function test_updates_last_login_on_success(): void
    {
        $sa = $this->createSuperadmin();

        $this->postJson('/superadmin/api/auth/login', [
            'email'    => 'admin@operaai.com',
            'password' => 'secret123',
        ])->assertOk();

        $sa->refresh();
        $this->assertNotNull($sa->last_login);
    }

    public function test_returns_401_on_wrong_password(): void
    {
        $this->createSuperadmin();

        $this->postJson('/superadmin/api/auth/login', [
            'email'    => 'admin@operaai.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function test_returns_401_on_inactive_superadmin(): void
    {
        $this->createSuperadmin(['activo' => false]);

        $this->postJson('/superadmin/api/auth/login', [
            'email'    => 'admin@operaai.com',
            'password' => 'secret123',
        ])->assertStatus(401);
    }

    public function test_rate_limit_returns_429_after_max_attempts(): void
    {
        $this->createSuperadmin();

        // SUPERADMIN_LOGIN_RATE_LIMIT=3 in phpunit.xml
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/superadmin/api/auth/login', [
                'email'    => 'admin@operaai.com',
                'password' => 'wrongpassword',
            ]);
        }

        $this->postJson('/superadmin/api/auth/login', [
            'email'    => 'admin@operaai.com',
            'password' => 'wrongpassword',
        ])->assertStatus(429);
    }

    public function test_tenant_token_returns_403_on_superadmin_route(): void
    {
        $plan        = Plan::factory()->create(['nombre' => 'pyme_plan', 'nombre_display' => 'PYME']);
        $empresa     = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id' => $empresa->id,
            'plan_id'    => $plan->id,
            'estado'     => 'activa',
        ]);
        $owner = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner', 'activo' => true]);
        $token = $owner->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;

        $this->withToken($token)
            ->getJson('/superadmin/api/dashboard')
            ->assertStatus(403);
    }
}
