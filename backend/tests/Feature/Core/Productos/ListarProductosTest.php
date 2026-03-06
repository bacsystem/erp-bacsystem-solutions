<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class ListarProductosTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    public function test_listado_paginado_retorna_200(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        for ($i = 1; $i <= 5; $i++) {
            $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'sku' => "SKU-{$i}"]);
        }

        $res = $this->withToken($token)->getJson('/api/productos');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_busqueda_por_nombre_parcial(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'nombre' => 'Laptop HP 14', 'sku' => 'LAP-001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'nombre' => 'Mouse Logitech', 'sku' => 'MOU-001']);

        $res = $this->withToken($token)->getJson('/api/productos?q=Laptop');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Laptop HP 14', $data[0]['nombre']);
    }

    public function test_busqueda_por_sku(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'sku' => 'FIND-001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'sku' => 'OTHER-002']);

        $res = $this->withToken($token)->getJson('/api/productos?q=FIND');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('FIND-001', $data[0]['sku']);
    }

    public function test_filtro_categoria_id(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat1 = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $cat2 = $this->crearCategoria(['empresa_id' => $usuario->empresa_id, 'nombre' => 'Otra Categoria']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat1->id, 'sku' => 'CAT1-001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat2->id, 'sku' => 'CAT2-001']);

        $res = $this->withToken($token)->getJson("/api/productos?categoria_id={$cat1->id}");

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('CAT1-001', $data[0]['sku']);
    }

    public function test_filtro_estado_inactivo(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'activo' => true, 'sku' => 'ACT-001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'activo' => false, 'sku' => 'INACT-001']);

        $res = $this->withToken($token)->getJson('/api/productos?estado=inactivo');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('INACT-001', $data[0]['sku']);
    }

    public function test_filtro_rango_precio(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'precio_venta' => 100, 'sku' => 'CHEAP-001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'precio_venta' => 500, 'sku' => 'MID-001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'precio_venta' => 1000, 'sku' => 'EXPN-001']);

        $res = $this->withToken($token)->getJson('/api/productos?precio_min=200&precio_max=600');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('MID-001', $data[0]['sku']);
    }

    public function test_sort_precio_desc(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'precio_venta' => 100, 'sku' => 'A001']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id, 'precio_venta' => 500, 'sku' => 'B001']);

        $res = $this->withToken($token)->getJson('/api/productos?sort=precio_venta&order=desc');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertEquals('B001', $data[0]['sku']);
    }

    public function test_tenant_isolation(): void
    {
        [$u1, $_t1] = $this->actingAsTenant();
        $cat1 = $this->crearCategoria(['empresa_id' => $u1->empresa_id]);
        $this->crearProducto(['empresa_id' => $u1->empresa_id, 'categoria_id' => $cat1->id, 'sku' => 'EMP1-001']);

        [$_u2, $token2] = $this->actingAsTenant();

        $res = $this->withToken($token2)->getJson('/api/productos');

        $res->assertStatus(200);
        $data = $res->json('data');
        $skus = array_column($data, 'sku');
        $this->assertNotContains('EMP1-001', $skus);
    }
}
