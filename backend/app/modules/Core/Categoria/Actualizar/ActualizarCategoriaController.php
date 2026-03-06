<?php

namespace App\Modules\Core\Categoria\Actualizar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActualizarCategoriaController
{
    public function __invoke(ActualizarCategoriaRequest $request, string $categoria, ActualizarCategoriaService $service): JsonResponse
    {
        $categoriaModel = $service->handle($categoria, $request->validated());
        return ApiResponse::success($categoriaModel, 'Categoría actualizada exitosamente.');
    }
}
