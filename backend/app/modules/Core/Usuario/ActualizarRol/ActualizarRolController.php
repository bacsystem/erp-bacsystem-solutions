<?php

namespace App\Modules\Core\Usuario\ActualizarRol;

use App\Modules\Core\Models\Usuario;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActualizarRolController
{
    public function __invoke(Usuario $usuario, ActualizarRolRequest $request, ActualizarRolService $service): JsonResponse
    {
        $usuario = $service->execute($usuario, $request->validated());

        return ApiResponse::success([
            'id'  => $usuario->id,
            'rol' => $usuario->rol,
        ], 'Rol actualizado correctamente.');
    }
}
