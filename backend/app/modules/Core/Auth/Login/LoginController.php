<?php

namespace App\Modules\Core\Auth\Login;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class LoginController
{
    public function __invoke(LoginRequest $request, LoginService $service): JsonResponse
    {
        try {
            $result = $service->execute($request->validated());
        } catch (AuthenticationException $e) {
            return ApiResponse::error($e->getMessage(), [], 401);
        }

        $refreshToken = $result['refresh_token'];
        unset($result['refresh_token']);

        return ApiResponse::success($result, 'Sesión iniciada')
            ->cookie('refresh_token', $refreshToken, 43200, '/', null, app()->isProduction(), true)
            ->cookie('has_session', '1', 43200, '/', null, app()->isProduction(), false);
    }
}
