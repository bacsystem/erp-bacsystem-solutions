<?php

namespace App\Modules\Superadmin\Auth\Logout;

use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutSuperadminController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Superadmin $superadmin */
        $superadmin = $request->user();
        (new LogoutSuperadminService())->execute($superadmin);
        return ApiResponse::success(null, 'Sesión cerrada correctamente.');
    }
}
