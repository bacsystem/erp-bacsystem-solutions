<?php

namespace App\Shared\Middleware;

use App\Shared\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array(auth()->user()->rol, $roles)) {
            return ApiResponse::error('No tienes permiso para realizar esta acción.', [], 403);
        }

        return $next($request);
    }
}
