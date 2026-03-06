<?php

namespace Tests\Feature\Core\Empresa;

use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class UpdateEmpresaTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_updates_empresa_and_keeps_ruc_immutable(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $originalRuc = $empresa->getRawOriginal('ruc');

        $response = $this->withToken($token)->putJson('/api/empresa', [
            'nombre_comercial' => 'Nuevo Nombre',
            'ruc'              => '99999999999', // debe ignorarse
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nombre_comercial', 'Nuevo Nombre')
            ->assertJsonPath('data.ruc', $originalRuc);

        $this->assertDatabaseHas('audit_logs', ['accion' => 'empresa_actualizada']);
    }

    public function test_rejects_empleado(): void
    {
        [$owner, $empresa, $sus, $plan] = $this->actingAsOwner();
        $empleado = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'empleado',
        ]);
        $token = $this->loginAs($empleado);

        $this->withToken($token)->putJson('/api/empresa', ['nombre_comercial' => 'Test'])
            ->assertStatus(403);
    }
}
