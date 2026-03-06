<?php

namespace Tests\Feature\Core\Usuario;

use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class DesactivarUsuarioTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_owner_puede_desactivar_usuario(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $empleado = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'empleado', 'activo' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$empleado->id}/desactivar");

        $response->assertStatus(200);
        $this->assertDatabaseHas('usuarios', ['id' => $empleado->id, 'activo' => false]);
    }

    public function test_no_puede_desactivar_unico_owner(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$owner->id}/desactivar");

        $response->assertStatus(422);
    }

    public function test_no_puede_auto_desactivarse(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $admin = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'admin', 'activo' => true]);
        $token = $this->loginAs($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$admin->id}/desactivar");

        $response->assertStatus(403);
    }

    public function test_usuario_ya_inactivo_retorna_422(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $inactivo = Usuario::factory()->create(['empresa_id' => $empresa->id, 'activo' => false]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$inactivo->id}/desactivar");

        $response->assertStatus(422);
    }

    public function test_usuario_de_otra_empresa_retorna_404(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();
        $otraEmpresa = \App\Modules\Core\Models\Empresa::factory()->create();
        $usuarioOtro = Usuario::factory()->create(['empresa_id' => $otraEmpresa->id, 'activo' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$usuarioOtro->id}/desactivar");

        $response->assertStatus(404);
    }
}
