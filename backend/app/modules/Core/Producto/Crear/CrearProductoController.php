<?php

namespace App\Modules\Core\Producto\Crear;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class CrearProductoController
{
    public function __invoke(CrearProductoRequest $request, CrearProductoService $service): JsonResponse
    {
        $usuario = auth()->user();
        $producto = $service->handle(
            $request->validated(),
            $usuario->empresa_id,
            $usuario->id,
        );

        return ApiResponse::success($producto, 'Producto creado exitosamente.', 201);
    }
}
