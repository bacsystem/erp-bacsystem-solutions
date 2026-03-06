<?php

namespace App\Modules\Core\Producto\Activar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActivarProductoController
{
    public function __invoke(string $producto, ActivarProductoService $service): JsonResponse
    {
        $usuario       = auth()->user();
        $productoModel = $service->handle($producto, $usuario->empresa_id, $usuario->id);

        return ApiResponse::success($productoModel, 'Producto activado exitosamente.');
    }
}
