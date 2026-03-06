<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class DesactivarProductoTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    public function test_owner_desactiva_producto(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat     = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'activo' => true]);

        $res = $this->withToken($token)->deleteJson("/api/productos/{$producto->id}");

        $res->assertStatus(200)
            ->assertJsonPath('data.activo', false);
    }

    public function test_producto_inexistente_retorna_404(): void
    {
        [$_u, $token] = $this->actingAsTenant();

        $this->withToken($token)->deleteJson('/api/productos/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_producto_de_otra_empresa_retorna_404(): void
    {
        [$u1, $_t1] = $this->actingAsTenant();
        $cat1     = $this->crearCategoria(['empresa_id' => $u1->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $u1->empresa_id, 'categoria_id' => $cat1->id]);

        [$_u2, $token2] = $this->actingAsTenant();

        $this->withToken($token2)->deleteJson("/api/productos/{$producto->id}")
            ->assertStatus(404);
    }
}
