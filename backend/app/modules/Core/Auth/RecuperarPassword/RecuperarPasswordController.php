<?php

namespace App\Modules\Core\Auth\RecuperarPassword;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class RecuperarPasswordController
{
    public function __invoke(RecuperarPasswordRequest $request, RecuperarPasswordService $service): JsonResponse
    {
        $service->solicitarReset($request->validated('email'));

        return ApiResponse::success(
            null,
            'Si el email existe, recibirás un link de recuperación en los próximos minutos'
        );
    }
}
