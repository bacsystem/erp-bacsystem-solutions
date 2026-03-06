<?php

namespace App\Shared\Middleware;

use App\Modules\Core\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next, string $accion): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() && auth()->check()) {
            AuditLog::registrar($accion);
        }

        return $response;
    }
}
