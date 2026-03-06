<?php

namespace App\Modules\Core\Producto\Desactivar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DesactivarProductoController
{
    public function __invoke(string $producto, DesactivarProductoService $service): JsonResponse
    {
        $usuario       = auth()->user();
        $productoModel = $service->handle($producto, $usuario->empresa_id, $usuario->id);

        return ApiResponse::success($productoModel, 'Producto desactivado exitosamente.');
    }
}
