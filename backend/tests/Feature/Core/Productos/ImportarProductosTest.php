<?php

namespace Tests\Feature\Core\Productos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\Feature\Core\Helpers\ProductoHelper;
use Tests\TestCase;

class ImportarProductosTest extends TestCase
{
    use RefreshDatabase, AuthHelper, ProductoHelper;

    public function test_descargar_template_retorna_200(): void
    {
        [$_u, $token] = $this->actingAsTenant();

        $res = $this->withToken($token)->getJson('/api/productos/importar/template');

        $res->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $res->headers->get('Content-Type') ?? '');
    }

    public function test_subir_csv_valido_retorna_preview(): void
    {
        [$usuario, $token] = $this->actingAsTenant();
        $cat = $this->crearCategoria(['empresa_id' => $usuario->empresa_id]);

        $csv = "nombre,sku,unidad_medida_principal,precio_venta,igv_tipo,tipo,categoria_id\n";
        $csv .= "Producto A,SKU-A,NIU,100.00,gravado,simple,{$cat->id}\n";
        $csv .= "Producto B,SKU-B,NIU,200.00,gravado,simple,{$cat->id}\n";

        $tmpPath = tempnam(sys_get_temp_dir(), 'import_') . '.csv';
        file_put_contents($tmpPath, $csv);
        $file = new UploadedFile($tmpPath, 'import.csv', 'text/csv', null, true);

        $res = $this->withToken($token)->postJson('/api/productos/importar', [
            'archivo' => $file,
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure(['data' => ['import_token', 'total', 'validos', 'errores', 'filas']]);
    }

    public function test_formato_invalido_retorna_422(): void
    {
        [$_u, $token] = $this->actingAsTenant();

        $this->withToken($token)->postJson('/api/productos/importar', [
            'archivo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_confirmar_import_token_invalido_retorna_422(): void
    {
        [$_u, $token] = $this->actingAsTenant();

        $this->withToken($token)->postJson('/api/productos/importar', [
            'import_token' => 'token-invalido-12345',
        ])->assertStatus(422);
    }
}
