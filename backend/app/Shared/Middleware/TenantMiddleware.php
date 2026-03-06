<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresaId = auth()->user()->empresa_id;

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
