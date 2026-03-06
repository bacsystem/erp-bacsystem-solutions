<?php

namespace App\Shared\Middleware;

use App\Modules\Core\Models\Usuario;
use App\Shared\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user instanceof Usuario) {
            return ApiResponse::error('No autenticado', [], 401);
        }

        $empresaId = $user->empresa_id;

        if (DB::getDriverName() !== 'pgsql') {
            return $next($request);
        }

        // SET LOCAL requires a transaction block to persist across queries.
        // Wrapping the request ensures app.empresa_id is visible to all RLS policies.
        return DB::transaction(function () use ($request, $next, $empresaId) {
            DB::statement("SET LOCAL app.empresa_id = '{$empresaId}'");
            return $next($request);
        });
    }
}
