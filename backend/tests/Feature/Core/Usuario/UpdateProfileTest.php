<?php

namespace Tests\Feature\Core\Usuario;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_actualizar_solo_nombre(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/me', ['nombre' => 'Nuevo Nombre']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('usuarios', ['id' => $owner->id, 'nombre' => 'Nuevo Nombre']);
    }

    public function test_cambiar_password_valido(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/me', [
                'password_actual'       => 'password',
                'password'              => 'NuevoPassword123!',
                'password_confirmation' => 'NuevoPassword123!',
            ]);

        $response->assertStatus(200);
        $owner->refresh();
        $this->assertTrue(Hash::check('NuevoPassword123!', $owner->password));
        // Tokens deben haberse eliminado
        $this->assertEquals(0, $owner->tokens()->count());
    }

    public function test_password_actual_incorrecto(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/me', [
                'password_actual'       => 'password_incorrecto',
                'password'              => 'NuevoPassword123!',
                'password_confirmation' => 'NuevoPassword123!',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password_actual.0', fn ($v) => str_contains($v, 'incorrecta') || str_contains($v, 'incorrectos'));
    }

    public function test_nueva_password_igual_a_actual(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/me', [
                'password_actual'       => 'password',
                'password'              => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertStatus(422);
    }

    public function test_nueva_password_menor_a_8_chars(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/me', [
                'password_actual'       => 'password',
                'password'              => 'abc',
                'password_confirmation' => 'abc',
            ]);

        $response->assertStatus(422);
    }
}
