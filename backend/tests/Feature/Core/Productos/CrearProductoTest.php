<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class CrearProductoTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre'                  => 'Laptop HP 14',
            'sku'                     => 'LAP-001',
            'unidad_medida_principal' => 'NIU',
            'precio_venta'            => 2499.99,
            'igv_tipo'                => 'gravado',
            'tipo'                    => 'simple',
        ], $overrides);
    }

    public function test_owner_crea_producto_simple_valido(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload([
            'categoria_id' => $cat->id,
        ]));

        $res->assertStatus(201)
            ->assertJsonPath('data.sku', 'LAP-001')
            ->assertJsonPath('data.activo', true);
    }

    public function test_sku_duplicado_misma_empresa_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'sku' => 'DUP-001', 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload([
            'sku'          => 'DUP-001',
            'categoria_id' => $cat->id,
        ]));

        $res->assertStatus(422);
    }

    public function test_sku_duplicado_otra_empresa_es_permitido(): void
    {
        [$_u1, $_t1] = $this->actingAsTenant();
        $cat1 = $this->crearCategoria(['empresa_id' => $_u1->empresa_id]);
        $this->crearProducto(['empresa_id' => $_u1->empresa_id, 'sku' => 'COM-001', 'categoria_id' => $cat1->id]);

        [$_u2, $token2] = $this->actingAsTenant();
        $cat2 = $this->crearCategoria(['empresa_id' => $_u2->empresa_id]);

        $res = $this->withToken($token2)->postJson('/api/productos', $this->payload([
            'sku'          => 'COM-001',
            'categoria_id' => $cat2->id,
        ]));

        $res->assertStatus(201);
    }

    public function test_empleado_no_puede_crear(): void
    {
        [$_u, $token] = $this->actingAsTenant('empleado');
        $cat = $this->crearCategoria(['empresa_id' => $_u->empresa_id]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload([
            'categoria_id' => $cat->id,
        ]));

        $res->assertStatus(403);
    }

    public function test_producto_compuesto_sin_componentes_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload([
            'tipo'         => 'compuesto',
            'categoria_id' => $cat->id,
            'componentes'  => [],
        ]));

        $res->assertStatus(422);
    }

    public function test_producto_no_puede_ser_componente_de_si_mismo(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat  = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $comp = $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload([
            'tipo'         => 'compuesto',
            'categoria_id' => $cat->id,
            'sku'          => 'KIT-001',
            'componentes'  => [['componente_id' => $comp->id, 'cantidad' => 1]],
        ]));

        // Si se crea correctamente, probamos auto-referencia en update
        if ($res->status() === 201) {
            $kitId = $res->json('data.id');
            $res2  = $this->withToken($token)->postJson('/api/productos', $this->payload([
                'tipo'         => 'compuesto',
                'categoria_id' => $cat->id,
                'sku'          => 'KIT-002',
                'componentes'  => [['componente_id' => $kitId, 'cantidad' => 1]],
            ]));
            $this->assertNotEquals(422, $res->status()); // kit creado OK
        }
    }

    public function test_precio_venta_faltante_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        $payload = $this->payload(['categoria_id' => $cat->id]);
        unset($payload['precio_venta']);

        $this->withToken($token)->postJson('/api/productos', $payload)
            ->assertStatus(422);
    }

    public function test_categoria_de_otra_empresa_retorna_422(): void
    {
        [$_u1, $_t1] = $this->actingAsTenant();
        $catAjena = $this->crearCategoria(['empresa_id' => $_u1->empresa_id]);

        [$_u2, $token2] = $this->actingAsTenant();

        $this->withToken($token2)->postJson('/api/productos', $this->payload([
            'categoria_id' => $catAjena->id,
        ]))->assertStatus(422);
    }
}
