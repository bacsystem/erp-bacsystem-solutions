<?php

namespace Tests\Feature\Core\Usuario;

use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class ActivarCuentaTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    private function crearInvitacion(array $overrides = []): InvitacionUsuario
    {
        [$owner, $empresa] = $this->actingAsOwner();

        return InvitacionUsuario::factory()->create(array_merge([
            'empresa_id' => $empresa->id,
            'email'      => 'invitado@test.com',
            'rol'        => 'admin',
            'token'      => 'token-valido-64chars-aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkk',
            'expires_at' => now()->addHours(48),
            'used_at'    => null,
            'invitado_por' => $owner->id,
        ], $overrides));
    }

    public function test_activar_cuenta_con_token_valido(): void
    {
        $invitacion = $this->crearInvitacion();

        $response = $this->postJson('/api/auth/activar-cuenta', [
            'token'                 => $invitacion->token,
            'nombre'                => 'Juan Invitado',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usuarios', ['email' => 'invitado@test.com', 'nombre' => 'Juan Invitado']);
        $this->assertNotNull(InvitacionUsuario::find($invitacion->id)->used_at);
    }

    public function test_falla_con_token_invalido(): void
    {
        $response = $this->postJson('/api/auth/activar-cuenta', [
            'token'                 => 'token-inexistente',
            'nombre'                => 'Juan',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_falla_con_token_expirado(): void
    {
        $invitacion = $this->crearInvitacion(['expires_at' => now()->subHour()]);

        $response = $this->postJson('/api/auth/activar-cuenta', [
            'token'                 => $invitacion->token,
            'nombre'                => 'Juan',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_falla_con_token_ya_usado(): void
    {
        $invitacion = $this->crearInvitacion(['used_at' => now()->subHour()]);

        $response = $this->postJson('/api/auth/activar-cuenta', [
            'token'                 => $invitacion->token,
            'nombre'                => 'Juan',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
    }
}
