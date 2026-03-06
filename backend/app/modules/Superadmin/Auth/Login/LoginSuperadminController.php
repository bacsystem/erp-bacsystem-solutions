<?php

namespace App\Modules\Superadmin\Auth\Login;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class LoginSuperadminController
{
    public function __invoke(LoginSuperadminRequest $request): JsonResponse
    {
        try {
            $result = (new LoginSuperadminService())->execute($request->validated());
            return ApiResponse::success($result);
        } catch (AuthenticationException $e) {
            return ApiResponse::error($e->getMessage(), [], 401);
        }
    }
}
