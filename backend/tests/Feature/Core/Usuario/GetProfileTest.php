<?php

namespace Tests\Feature\Core\Usuario;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class GetProfileTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_retorna_perfil_del_usuario_autenticado(): void
    {
        [$owner, $empresa, $suscripcion, $plan, $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'nombre', 'email', 'rol',
                    'empresa' => ['id', 'razon_social'],
                    'suscripcion' => ['estado', 'plan'],
                ],
            ]);

        $this->assertEquals($owner->id, $response->json('data.id'));
        $this->assertEquals($owner->email, $response->json('data.email'));
    }

    public function test_sin_auth_retorna_401(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }
}
