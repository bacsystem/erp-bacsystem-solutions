<?php

namespace App\Modules\Core\Usuario\ListarUsuarios;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ListarUsuariosController
{
    public function __invoke(ListarUsuariosService $service): JsonResponse
    {
        return ApiResponse::success($service->execute());
    }
}
