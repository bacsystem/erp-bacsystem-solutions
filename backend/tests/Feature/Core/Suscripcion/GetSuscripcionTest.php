<?php

namespace Tests\Feature\Core\Suscripcion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class GetSuscripcionTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_returns_suscripcion_with_plan_and_datos_pago(): void
    {
        [, , , , $token] = $this->actingAsOwner();

        $response = $this->withToken($token)->getJson('/api/suscripcion');

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'plan', 'estado', 'fecha_inicio', 'fecha_vencimiento',
                'dias_restantes', 'datos_pago',
            ]]);
    }

    public function test_returns_401_without_auth(): void
    {
        $this->getJson('/api/suscripcion')->assertStatus(401);
    }
}
