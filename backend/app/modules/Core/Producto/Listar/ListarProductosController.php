<?php

namespace App\Modules\Core\Producto\Listar;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListarProductosController
{
    public function __invoke(Request $request, ListarProductosService $service): JsonResponse
    {
        $paginator = $service->handle($request->query());

        return ApiResponse::paginated($paginator, 'Productos obtenidos exitosamente.');
    }
}
