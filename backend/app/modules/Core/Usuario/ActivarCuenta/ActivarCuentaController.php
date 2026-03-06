<?php

namespace App\Modules\Core\Usuario\ActivarCuenta;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActivarCuentaController
{
    public function __invoke(ActivarCuentaRequest $request, ActivarCuentaService $service): JsonResponse
    {
        $result       = $service->execute($request->validated());
        $refreshToken = $result['refresh_token'];
        unset($result['refresh_token']);

        return ApiResponse::success($result, '¡Cuenta activada! Ya puedes acceder a tu equipo.', 201)
            ->cookie('refresh_token', $refreshToken, 43200, '/', null, app()->isProduction(), true)
            ->cookie('has_session', '1', 43200, '/', null, false, false);
    }
}
