<?php

namespace App\Modules\Core\Producto\GetDetalle;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class GetProductoDetalleController
{
    public function __invoke(string $producto, GetProductoDetalleService $service): JsonResponse
    {
        $productoModel = $service->handle($producto);

        return ApiResponse::success($productoModel, 'Producto obtenido exitosamente.');
    }
}
