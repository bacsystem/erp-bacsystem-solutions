<?php

namespace Tests\Feature\Core\Usuario;

use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class ActualizarRolTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_owner_puede_cambiar_rol_de_usuario(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $empleado = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'empleado', 'activo' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$empleado->id}/rol", ['rol' => 'admin']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('usuarios', ['id' => $empleado->id, 'rol' => 'admin']);
    }

    public function test_admin_no_puede_asignar_rol_owner(): void
    {
        [$owner, $empresa, , , ] = $this->actingAsOwner();
        $admin = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'admin', 'activo' => true]);
        $empleado = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'empleado', 'activo' => true]);
        $token = $this->loginAs($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$empleado->id}/rol", ['rol' => 'owner']);

        $response->assertStatus(403);
    }

    public function test_usuario_no_puede_cambiar_su_propio_rol(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$owner->id}/rol", ['rol' => 'admin']);

        $response->assertStatus(403);
    }

    public function test_usuario_de_otra_empresa_retorna_404(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();
        $otraEmpresa = \App\Modules\Core\Models\Empresa::factory()->create();
        $usuarioOtro = Usuario::factory()->create(['empresa_id' => $otraEmpresa->id, 'rol' => 'empleado']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$usuarioOtro->id}/rol", ['rol' => 'admin']);

        $response->assertStatus(404);
    }

    public function test_rol_invalido_retorna_422(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $empleado = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'empleado']);
        $token = $this->loginAs($owner);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/usuarios/{$empleado->id}/rol", ['rol' => 'superadmin']);

        $response->assertStatus(422);
    }
}
