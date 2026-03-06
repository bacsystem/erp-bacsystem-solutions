<?php

namespace Tests\Feature\Core\Auth;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createOwner(array $attrs = []): array
    {
        $plan        = Plan::factory()->pyme()->create();
        $empresa     = Empresa::factory()->create();
        $suscripcion = Suscripcion::factory()->trial()->create([
            'empresa_id' => $empresa->id,
            'plan_id'    => $plan->id,
        ]);
        $owner = Usuario::factory()->create(array_merge([
            'empresa_id' => $empresa->id,
            'email'      => 'owner@test.com',
            'password'   => Hash::make('password123'),
            'rol'        => 'owner',
            'activo'     => true,
        ], $attrs));

        return [$owner, $empresa, $suscripcion, $plan];
    }

    public function test_returns_tokens_on_valid_credentials(): void
    {
        [$owner] = $this->createOwner();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.rol', 'owner')
            ->assertJsonPath('data.user.suscripcion.estado', 'trial')
            ->assertJsonStructure(['data' => ['access_token', 'user']])
            ->assertCookie('refresh_token')
            ->assertCookie('has_session', '1', false);

        $this->assertDatabaseHas('audit_logs', ['accion' => 'login']);
        $owner->refresh();
        $this->assertNotNull($owner->last_login);
    }

    public function test_returns_401_on_wrong_password(): void
    {
        $this->createOwner();

        $this->postJson('/api/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401)
          ->assertJsonPath('message', 'Credenciales incorrectas.');

        $this->assertDatabaseHas('audit_logs', ['accion' => 'login_failed']);
    }

    public function test_returns_401_on_inactive_user(): void
    {
        $this->createOwner(['activo' => false]);

        $this->postJson('/api/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'password123',
        ])->assertStatus(401);
    }

    public function test_updates_last_login_on_success(): void
    {
        [$owner] = $this->createOwner();

        $this->postJson('/api/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'password123',
        ])->assertOk();

        $this->assertNotNull($owner->fresh()->last_login);
    }

    public function test_rate_limit_exceeded_returns_429(): void
    {
        $this->createOwner();

        // 5 intentos fallidos agotan el límite (5/15min por IP)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email'    => 'owner@test.com',
                'password' => 'wrongpassword',
            ]);
        }

        // El 6to intento debe ser bloqueado
        $this->postJson('/api/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'wrongpassword',
        ])->assertStatus(429);
    }

    public function test_login_with_cancelada_subscription_returns_redirect(): void
    {
        $plan    = Plan::factory()->pyme()->create(['nombre' => 'pyme_cancel']);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->cancelada()->create([
            'empresa_id' => $empresa->id,
            'plan_id'    => $plan->id,
        ]);
        Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'email'      => 'cancel@test.com',
            'password'   => Hash::make('password123'),
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'cancel@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.suscripcion.estado', 'cancelada')
            ->assertJsonPath('data.user.suscripcion.redirect', '/configuracion/plan');
    }
}
