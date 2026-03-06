<?php

namespace App\Modules\Core\Producto\Promocion\DesactivarPromocion;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DesactivarPromocionController
{
    public function __invoke(string $producto, string $promocion, DesactivarPromocionService $service): JsonResponse
    {
        $service->handle($producto, $promocion);
        return ApiResponse::success(null, 'Promoción desactivada exitosamente.');
    }
}
