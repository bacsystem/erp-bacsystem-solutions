<?php

namespace App\Modules\Core\Producto\Promocion\CrearPromocion;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class CrearPromocionController
{
    public function __invoke(CrearPromocionRequest $request, string $producto, CrearPromocionService $service): JsonResponse
    {
        $promocion = $service->handle($producto, $request->validated());
        return ApiResponse::success($promocion, 'Promoción creada exitosamente.', 201);
    }
}
