<?php

namespace App\Modules\Core\Usuario\DesactivarUsuario;

use App\Modules\Core\Models\Usuario;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DesactivarUsuarioController
{
    public function __invoke(Usuario $usuario, DesactivarUsuarioService $service): JsonResponse
    {
        $service->execute($usuario);

        return ApiResponse::success([], 'Usuario desactivado correctamente.');
    }
}
