<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class CategoriasTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    public function test_crear_categoria_raiz(): void
    {
        [$_u, $token] = $this->actingAsTenant();

        $this->withToken($token)->postJson('/api/categorias', [
            'nombre' => 'Electrónica',
        ])->assertStatus(201)
          ->assertJsonPath('data.nombre', 'Electrónica');
    }

    public function test_crear_subcategoria_con_padre_valido(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $padre = $this->crearCategoria(['empresa_id' => $usuario->empresa_id, 'nombre' => 'Electrónica']);

        $this->withToken($token)->postJson('/api/categorias', [
            'nombre'             => 'Laptops',
            'categoria_padre_id' => $padre->id,
        ])->assertStatus(201)
          ->assertJsonPath('data.categoria_padre_id', $padre->id);
    }

    public function test_crear_con_padre_de_otra_empresa_retorna_422(): void
    {
        [$u1, $_t1] = $this->actingAsTenant();
        $padreAjeno = $this->crearCategoria(['empresa_id' => $u1->empresa_id]);

        [$_u2, $token2] = $this->actingAsTenant();

        $this->withToken($token2)->postJson('/api/categorias', [
            'nombre'             => 'Test',
            'categoria_padre_id' => $padreAjeno->id,
        ])->assertStatus(422);
    }

    public function test_nombre_duplicado_mismo_nivel_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $this->crearCategoria(['empresa_id' => $usuario->empresa_id, 'nombre' => 'Electrónica']);

        $this->withToken($token)->postJson('/api/categorias', [
            'nombre' => 'Electrónica',
        ])->assertStatus(422);
    }

    public function test_listar_categorias(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $this->crearCategoria(['empresa_id' => $usuario->empresa_id, 'nombre' => 'Cat A']);
        $this->crearCategoria(['empresa_id' => $usuario->empresa_id, 'nombre' => 'Cat B']);

        $this->withToken($token)->getJson('/api/categorias')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_actualizar_nombre(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        $this->withToken($token)->putJson("/api/categorias/{$cat->id}", [
            'nombre' => 'Nuevo Nombre',
        ])->assertStatus(200)
          ->assertJsonPath('data.nombre', 'Nuevo Nombre');
    }

    public function test_eliminar_con_productos_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $this->withToken($token)->deleteJson("/api/categorias/{$cat->id}")
            ->assertStatus(422);
    }

    public function test_eliminar_con_subcategorias_retorna_422(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $padre = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearCategoria([
            'empresa_id'         => $usuario->empresa_id,
            'nombre'             => 'Sub',
            'categoria_padre_id' => $padre->id,
        ]);

        $this->withToken($token)->deleteJson("/api/categorias/{$padre->id}")
            ->assertStatus(422);
    }

    public function test_eliminar_sin_dependencias(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        $this->withToken($token)->deleteJson("/api/categorias/{$cat->id}")
            ->assertStatus(200);
    }

    public function test_tenant_isolation_categorias(): void
    {
        [$u1, $_t1] = $this->actingAsTenant();
        $this->crearCategoria(['empresa_id' => $u1->empresa_id, 'nombre' => 'EMP1-CAT']);

        [$_u2, $token2] = $this->actingAsTenant();

        $res = $this->withToken($token2)->getJson('/api/categorias');
        $nombres = array_column($res->json('data'), 'nombre');
        $this->assertNotContains('EMP1-CAT', $nombres);
    }
}
