<?php

namespace App\Modules\Core\Usuario\InviteUsuario;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class InviteUsuarioController
{
    public function __invoke(InviteUsuarioRequest $request, InviteUsuarioService $service): JsonResponse
    {
        $invitacion = $service->execute($request->validated());

        return ApiResponse::success([
            'id'         => $invitacion->id,
            'email'      => $invitacion->email,
            'rol'        => $invitacion->rol,
            'expires_at' => $invitacion->expires_at->toDateTimeString(),
        ], 'Invitación enviada correctamente.', 201);
    }
}
