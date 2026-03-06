<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class ExportarProductosTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    public function test_exportar_excel_retorna_200(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->get('/api/productos/exportar');

        $res->assertStatus(200);
        $contentType = $res->headers->get('Content-Type');
        $this->assertNotNull($contentType);
    }

    public function test_exportar_csv_retorna_content_type_correcto(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->get('/api/productos/exportar?formato=csv');

        $res->assertStatus(200);
        $this->assertStringContainsString('csv', $res->headers->get('Content-Type') ?? '');
    }

    public function test_exportar_filtro_categoria(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat1 = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $cat2 = $this->crearCategoria(['empresa_id' => $usuario->empresa_id, 'nombre' => 'Otra']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat1->id, 'sku' => 'CAT1']);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat2->id, 'sku' => 'CAT2']);

        $res = $this->withToken($token)->get("/api/productos/exportar?formato=csv&categoria_id={$cat1->id}");

        $res->assertStatus(200);
        $content = $res->getContent();
        $this->assertStringContainsString('CAT1', $content);
        $this->assertStringNotContainsString('CAT2', $content);
    }

    public function test_exportar_pdf_retorna_200(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);
        $this->crearProducto(['empresa_id' => $usuario->empresa_id, 'categoria_id' => $cat->id]);

        $res = $this->withToken($token)->get('/api/productos/exportar/pdf');

        $res->assertStatus(200);
        $this->assertStringContainsString('pdf', $res->headers->get('Content-Type') ?? '');
    }

    public function test_tenant_isolation_exportacion(): void
    {
        [$u1, $_t1] = $this->actingAsTenant();
        $cat1 = $this->crearCategoria(['empresa_id' => $u1->empresa_id]);
        $this->crearProducto(['empresa_id' => $u1->empresa_id, 'categoria_id' => $cat1->id, 'sku' => 'EMP1-EXPORT']);

        [$_u2, $token2] = $this->actingAsTenant();

        $res = $this->withToken($token2)->get('/api/productos/exportar?formato=csv');
        $res->assertStatus(200);
        $this->assertStringNotContainsString('EMP1-EXPORT', $res->getContent());
    }
}
