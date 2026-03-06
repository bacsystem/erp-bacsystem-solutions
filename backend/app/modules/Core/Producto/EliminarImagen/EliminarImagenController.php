<?php

namespace App\Modules\Core\Producto\EliminarImagen;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class EliminarImagenController
{
    public function __invoke(string $producto, string $imagen, EliminarImagenService $service): JsonResponse
    {
        $service->handle($producto, $imagen);

        return ApiResponse::success(null, 'Imagen eliminada exitosamente.');
    }
}
