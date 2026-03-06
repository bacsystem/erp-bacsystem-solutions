<?php

namespace App\Modules\Core\Auth\Logout;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;

class LogoutController
{
    public function __invoke(LogoutService $service): JsonResponse
    {
        $service->execute(auth()->user());

        return ApiResponse::success(null, 'Sesión cerrada')
            ->withCookie(Cookie::forget('refresh_token'))
            ->withCookie(Cookie::forget('has_session'));
    }
}
