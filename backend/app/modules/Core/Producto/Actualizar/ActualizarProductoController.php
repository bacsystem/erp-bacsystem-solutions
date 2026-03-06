<?php

namespace App\Modules\Core\Producto\Actualizar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActualizarProductoController
{
    public function __invoke(ActualizarProductoRequest $request, string $producto, ActualizarProductoService $service): JsonResponse
    {
        $usuario       = auth()->user();
        $productoModel = $service->handle($producto, $request->validated(), $usuario->empresa_id, $usuario->id);

        return ApiResponse::success($productoModel, 'Producto actualizado exitosamente.');
    }
}
