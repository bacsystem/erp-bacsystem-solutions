<?php

namespace App\Modules\Core\Categoria\Eliminar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class EliminarCategoriaController
{
    public function __invoke(string $categoria, EliminarCategoriaService $service): JsonResponse
    {
        $service->handle($categoria);
        return ApiResponse::success(null, 'Categoría eliminada exitosamente.');
    }
}
