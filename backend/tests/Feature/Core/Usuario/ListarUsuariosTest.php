<?php

namespace Tests\Feature\Core\Usuario;

use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class ListarUsuariosTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_lista_usuarios_y_invitaciones_del_tenant(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $admin = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'admin', 'activo' => true]);
        InvitacionUsuario::factory()->create(['empresa_id' => $empresa->id, 'used_at' => null, 'expires_at' => now()->addHours(24)]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/usuarios');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('usuarios', $data);
        $this->assertArrayHasKey('invitaciones', $data);
        $this->assertCount(2, $data['usuarios']); // owner + admin
        $this->assertCount(1, $data['invitaciones']);
    }

    public function test_solo_muestra_usuarios_del_propio_tenant(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();

        // Usuario de otra empresa
        $otraEmpresa = \App\Modules\Core\Models\Empresa::factory()->create();
        Usuario::factory()->create(['empresa_id' => $otraEmpresa->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/usuarios');

        $response->assertStatus(200);
        $usuarios = $response->json('data.usuarios');
        foreach ($usuarios as $u) {
            $this->assertEquals($empresa->id, $u['empresa_id'] ?? $empresa->id);
        }
        $this->assertCount(1, $usuarios); // solo el owner
    }

    public function test_sin_auth_devuelve_401(): void
    {
        $this->getJson('/api/usuarios')->assertStatus(401);
    }
}
