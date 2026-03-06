<?php

namespace Tests\Feature\Core\Usuario;

use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Shared\Mail\InvitacionUsuarioMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class InviteUsuarioTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_owner_puede_invitar_usuario(): void
    {
        Mail::fake();
        [$owner, $empresa, $suscripcion, $plan, $token] = $this->actingAsOwner(['max_usuarios' => 15]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/usuarios/invitar', [
                'email' => 'nuevo@empresa.com',
                'rol'   => 'admin',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invitaciones_usuario', [
            'empresa_id' => $empresa->id,
            'email'      => 'nuevo@empresa.com',
            'rol'        => 'admin',
        ]);
        Mail::assertQueued(InvitacionUsuarioMail::class);
    }

    public function test_falla_si_limite_usuarios_alcanzado(): void
    {
        $plan = Plan::factory()->create(['max_usuarios' => 1]);
        $empresa = \App\Modules\Core\Models\Empresa::factory()->create();
        Suscripcion::factory()->create(['empresa_id' => $empresa->id, 'plan_id' => $plan->id, 'estado' => 'activa']);
        $owner = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner', 'activo' => true]);
        $token = $this->loginAs($owner);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/usuarios/invitar', ['email' => 'nuevo@test.com', 'rol' => 'empleado']);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', fn ($v) => str_contains($v, 'límite'));
    }

    public function test_falla_si_email_ya_es_usuario_activo(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        Usuario::factory()->create(['empresa_id' => $empresa->id, 'email' => 'existente@test.com', 'activo' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/usuarios/invitar', ['email' => 'existente@test.com', 'rol' => 'empleado']);

        $response->assertStatus(422);
    }

    public function test_falla_si_invitacion_pendiente_duplicada(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        InvitacionUsuario::factory()->create([
            'empresa_id' => $empresa->id,
            'email'      => 'pendiente@test.com',
            'used_at'    => null,
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/usuarios/invitar', ['email' => 'pendiente@test.com', 'rol' => 'empleado']);

        $response->assertStatus(422);
    }

    public function test_falla_si_rol_es_owner(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/usuarios/invitar', ['email' => 'nuevo@test.com', 'rol' => 'owner']);

        $response->assertStatus(422);
    }

    public function test_empleado_no_puede_invitar(): void
    {
        [$owner, $empresa, , , ] = $this->actingAsOwner();
        $empleado = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'empleado', 'activo' => true]);
        $token = $this->loginAs($empleado);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/usuarios/invitar', ['email' => 'nuevo@test.com', 'rol' => 'empleado']);

        $response->assertStatus(403);
    }
}
