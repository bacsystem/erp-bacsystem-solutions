<?php

namespace App\Shared\Middleware;

use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('sanctum')->user();

        if (! $user instanceof Superadmin) {
            return ApiResponse::error('Acceso no autorizado', [], 403);
        }

        if (! $user->activo) {
            return ApiResponse::error('Tu cuenta de superadmin está desactivada.', [], 401);
        }

        return $next($request);
    }
}
