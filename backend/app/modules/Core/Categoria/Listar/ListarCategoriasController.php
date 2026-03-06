<?php

namespace App\Modules\Core\Categoria\Listar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ListarCategoriasController
{
    public function __invoke(ListarCategoriasService $service): JsonResponse
    {
        return ApiResponse::success($service->handle(), 'Categorías obtenidas exitosamente.');
    }
}
