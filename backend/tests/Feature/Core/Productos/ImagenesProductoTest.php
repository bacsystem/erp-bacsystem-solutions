<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class ImagenesProductoTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('r2');
    }

    public function test_subir_imagen_valida_retorna_201(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat      = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->postJson("/api/productos/{$producto->id}/imagenes", [
            'imagen' => UploadedFile::fake()->image('test.jpg', 100, 100)->size(500),
        ]);

        $res->assertStatus(201)
            ->assertJsonStructure(['data' => ['url']]);
    }

    public function test_formato_invalido_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat      = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $this->withToken($token)->postJson("/api/productos/{$producto->id}/imagenes", [
            'imagen' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_tamanio_mayor_5mb_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat      = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $this->withToken($token)->postJson("/api/productos/{$producto->id}/imagenes", [
            'imagen' => UploadedFile::fake()->image('big.jpg')->size(6000),
        ])->assertStatus(422);
    }

    public function test_limite_5_imagenes_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat      = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        // Create 5 imagenes directly
        for ($i = 1; $i <= 5; $i++) {
            $producto->imagenes()->create([
                'url'   => "https://r2.example.com/img-{$i}.jpg",
                'orden' => $i,
            ]);
        }

        $this->withToken($token)->postJson("/api/productos/{$producto->id}/imagenes", [
            'imagen' => UploadedFile::fake()->image('extra.jpg')->size(500),
        ])->assertStatus(422);
    }

    public function test_eliminar_imagen(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat      = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $imagen = $producto->imagenes()->create([
            'url'     => 'https://r2.example.com/img-1.jpg',
            'path_r2' => 'empresas/test/productos/test/img-1.jpg',
            'orden'   => 1,
        ]);

        $this->withToken($token)->deleteJson("/api/productos/{$producto->id}/imagenes/{$imagen->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('producto_imagenes', ['id' => $imagen->id]);
    }
}
