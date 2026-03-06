<?php

namespace App\Modules\Core\Auth\Register;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegisterController
{
    public function __invoke(RegisterRequest $request, RegisterService $service): JsonResponse
    {
        $result = $service->execute($request->validated());

        $refreshToken = $result['refresh_token'];
        unset($result['refresh_token']);

        return ApiResponse::success($result, 'Empresa registrada exitosamente', 201)
            ->cookie('refresh_token', $refreshToken, 43200, '/', null, app()->isProduction(), true)
            ->cookie('has_session', '1', 43200, '/', null, app()->isProduction(), false);
    }
}
