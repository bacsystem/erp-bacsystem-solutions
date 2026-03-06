<?php

namespace App\Modules\Core\Categoria\Crear;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class CrearCategoriaController
{
    public function __invoke(CrearCategoriaRequest $request, CrearCategoriaService $service): JsonResponse
    {
        $categoria = $service->handle($request->validated(), auth()->user()->empresa_id);
        return ApiResponse::success($categoria, 'Categoría creada', 201);
    }
}
