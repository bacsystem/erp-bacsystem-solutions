<?php

namespace Tests\Feature\Core\Empresa;

use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class UploadLogoTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_owner_puede_subir_logo(): void
    {
        Storage::fake('r2');

        [$owner, $empresa, , , $token] = $this->actingAsOwner();

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->withToken($token)
            ->postJson('/api/empresa/logo', ['logo' => $file]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['logo_url']]);

        $logoUrl = $response->json('data.logo_url');
        $this->assertNotEmpty($logoUrl);

        $this->assertDatabaseHas('audit_logs', [
            'accion'         => 'logo_actualizado',
            'tabla_afectada' => 'empresas',
            'registro_id'    => $empresa->id,
        ]);

        $empresa->refresh();
        $this->assertNotNull($empresa->logo_url);
    }

    public function test_logo_anterior_se_elimina_al_subir_nuevo(): void
    {
        Storage::fake('r2');

        [$owner, $empresa, , , $token] = $this->actingAsOwner();

        // Simular logo existente en R2 — URL con formato completo para que
        // parse_url() extraiga el path correctamente en el service
        $oldPath = "logos/{$empresa->id}/old.png";
        Storage::disk('r2')->put($oldPath, 'fake-content');
        $empresa->update(['logo_url' => 'https://r2.example.com/' . $oldPath]);

        $file = UploadedFile::fake()->image('nuevo.jpg', 100, 100);

        $this->withToken($token)
            ->postJson('/api/empresa/logo', ['logo' => $file])
            ->assertOk();

        Storage::disk('r2')->assertMissing($oldPath);
    }

    public function test_rechaza_archivo_mayor_a_2mb(): void
    {
        Storage::fake('r2');

        [, , , , $token] = $this->actingAsOwner();

        $file = UploadedFile::fake()->create('logo.png', 2049, 'image/png');

        $this->withToken($token)
            ->postJson('/api/empresa/logo', ['logo' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('logo');
    }

    public function test_rechaza_formato_invalido(): void
    {
        Storage::fake('r2');

        [, , , , $token] = $this->actingAsOwner();

        $file = UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf');

        $this->withToken($token)
            ->postJson('/api/empresa/logo', ['logo' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('logo');
    }

    public function test_empleado_no_puede_subir_logo(): void
    {
        Storage::fake('r2');

        [$owner, $empresa, , ,] = $this->actingAsOwner();

        $empleado = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'empleado',
            'activo'     => true,
        ]);
        $tokenEmpleado = $this->loginAs($empleado);

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $this->withToken($tokenEmpleado)
            ->postJson('/api/empresa/logo', ['logo' => $file])
            ->assertStatus(403);
    }

    public function test_sin_auth_retorna_401(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $this->postJson('/api/empresa/logo', ['logo' => $file])
            ->assertStatus(401);
    }
}
