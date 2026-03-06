<?php

namespace App\Modules\Core\Auth\RecuperarPassword;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ResetPasswordController
{
    public function __invoke(ResetPasswordRequest $request, RecuperarPasswordService $service): JsonResponse
    {
        $service->resetPassword($request->validated());

        return ApiResponse::success(
            null,
            'Contraseña actualizada exitosamente. Inicia sesión con tu nueva contraseña'
        );
    }
}
