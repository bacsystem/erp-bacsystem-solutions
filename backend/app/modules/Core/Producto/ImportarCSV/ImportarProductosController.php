<?php

namespace App\Modules\Core\Producto\ImportarCSV;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Excel;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportarProductosController
{
    public function __invoke(ImportarProductosRequest $request, ImportarProductosService $service): JsonResponse
    {
        $usuario = auth()->user();

        // Step 2: confirm import
        if ($request->has('import_token')) {
            $result = $service->confirmar(
                $request->input('import_token'),
                $usuario->empresa_id,
                $usuario->id,
            );
            return ApiResponse::success($result, 'Importación completada.', 201);
        }

        // Step 1: preview
        $result = $service->preview($request->file('archivo'), $usuario->empresa_id);
        return ApiResponse::success($result, 'Vista previa generada.');
    }

    public function template(): Response
    {
        $headers = ['nombre', 'sku', 'unidad_medida_principal', 'precio_venta', 'igv_tipo', 'tipo', 'categoria_id', 'descripcion', 'codigo_barras', 'precio_compra'];
        $csv     = implode(',', $headers) . "\n";
        $csv    .= "Ejemplo Producto,PROD-001,NIU,99.90,gravado,simple,,Descripción opcional,,\n";

        return response($csv, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template-productos.csv"',
        ]);
    }
}
