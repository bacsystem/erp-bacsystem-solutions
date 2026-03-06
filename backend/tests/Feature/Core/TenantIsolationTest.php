<?php

namespace Tests\Feature\Core;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_empresa_a_no_ve_usuarios_de_empresa_b(): void
    {
        [$ownerA, $empresaA, , , $tokenA] = $this->actingAsOwner();
        [$ownerB, $empresaB, , , ] = $this->actingAsOwner();

        $usuarioB = Usuario::factory()->create(['empresa_id' => $empresaB->id, 'rol' => 'empleado', 'activo' => true]);

        $response = $this->withHeader('Authorization', "Bearer $tokenA")
            ->getJson('/api/usuarios');

        $response->assertStatus(200);
        $ids = collect($response->json('data.usuarios'))->pluck('id');
        $this->assertFalse($ids->contains($usuarioB->id));
        $this->assertTrue($ids->contains($ownerA->id));
    }

    public function test_empresa_a_no_puede_modificar_usuario_de_empresa_b(): void
    {
        [$ownerA, , , , $tokenA] = $this->actingAsOwner();
        [$ownerB, $empresaB] = $this->actingAsOwner();
        $empleadoB = Usuario::factory()->create(['empresa_id' => $empresaB->id, 'rol' => 'empleado', 'activo' => true]);

        $response = $this->withHeader('Authorization', "Bearer $tokenA")
            ->putJson("/api/usuarios/{$empleadoB->id}/rol", ['rol' => 'admin']);

        $response->assertStatus(404);
    }

    public function test_empresa_a_no_ve_suscripcion_de_empresa_b(): void
    {
        [$ownerA, $empresaA, $suscripcionA, , $tokenA] = $this->actingAsOwner();
        [$ownerB, $empresaB, $suscripcionB] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $tokenA")
            ->getJson('/api/suscripcion');

        $response->assertStatus(200);
        $this->assertEquals($suscripcionA->id, $response->json('data.id'));
        $this->assertNotEquals($suscripcionB->id, $response->json('data.id'));
    }

    public function test_empresa_a_no_ve_empresa_b(): void
    {
        [$ownerA, $empresaA, , , $tokenA] = $this->actingAsOwner();
        [$ownerB, $empresaB] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $tokenA")
            ->getJson('/api/empresa');

        $response->assertStatus(200);
        $this->assertEquals($empresaA->id, $response->json('data.id'));
        $this->assertNotEquals($empresaB->id, $response->json('data.id'));
    }
}
