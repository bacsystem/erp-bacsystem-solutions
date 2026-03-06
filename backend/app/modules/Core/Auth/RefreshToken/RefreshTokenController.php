<?php

namespace App\Modules\Core\Auth\RefreshToken;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshTokenController
{
    public function __invoke(Request $request, RefreshTokenService $service): JsonResponse
    {
        try {
            $result = $service->execute($request);
        } catch (AuthenticationException $e) {
            return ApiResponse::error($e->getMessage(), [], 401);
        }

        $refreshToken = $result['refresh_token'];
        unset($result['refresh_token']);

        return ApiResponse::success($result)
            ->cookie('refresh_token', $refreshToken, 43200, '/', null, app()->isProduction(), true)
            ->cookie('has_session', '1', 43200, '/', null, app()->isProduction(), false);
    }
}
