<?php

namespace Tests\Feature\Core\Productos;

use App\Modules\Core\Producto\Models\PrecioHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class ActualizarProductoTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    public function test_actualizar_nombre_retorna_200(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat     = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->putJson("/api/productos/{$producto->id}", [
            'nombre' => 'Nuevo Nombre',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.nombre', 'Nuevo Nombre');
    }

    public function test_cambiar_precio_venta_registra_historial(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat     = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto([
            'empresa_id'  => $usuario->empresa_id,
            'categoria_id'=> $cat->id,
            'precio_venta'=> 100.00,
        ]);

        $res = $this->withToken($token)->putJson("/api/productos/{$producto->id}", [
            'precio_venta' => 150.00,
        ]);

        $res->assertStatus(200);
        $this->assertEquals(150.0, $res->json('data.precio_venta'));

        $this->assertDatabaseHas('precio_historial', [
            'producto_id'    => $producto->id,
            'precio_anterior'=> 100.00,
            'precio_nuevo'   => 150.00,
        ]);
    }

    public function test_enviar_sku_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat     = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $this->withToken($token)->putJson("/api/productos/{$producto->id}", [
            'sku' => 'NEWSKU',
        ])->assertStatus(422);
    }

    public function test_empleado_no_puede_actualizar(): void
    {
        [$owner, $_t] = $this->actingAsTenant();
        $cat     = $this->crearCategoria(['empresa_id' => $owner->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $owner->empresa_id, 'categoria_id' => $cat->id]);

        [$empleado, $tokenEmpleado] = $this->actingAsTenantWithSameEmpresa($owner->empresa_id, 'empleado');

        $this->withToken($tokenEmpleado)->putJson("/api/productos/{$producto->id}", [
            'nombre' => 'Hack',
        ])->assertStatus(403);
    }

    public function test_producto_de_otra_empresa_retorna_404(): void
    {
        [$u1, $_t1] = $this->actingAsTenant();
        $cat1     = $this->crearCategoria(['empresa_id' => $u1->empresa_id]);
        $producto = $this->crearProducto(['empresa_id' => $u1->empresa_id, 'categoria_id' => $cat1->id]);

        [$_u2, $token2] = $this->actingAsTenant();

        $this->withToken($token2)->putJson("/api/productos/{$producto->id}", [
            'nombre' => 'Hack',
        ])->assertStatus(404);
    }
}
