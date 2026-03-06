<?php

namespace App\Modules\Superadmin\Dashboard;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function execute(): JsonResponse
    {
        // Bypass RLS — superadmin sees all tenants (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        // MRR: sum of precio_mensual for activa + trial subscriptions
        $mrrTotal = (float) DB::table('suscripciones')
            ->join('planes', 'suscripciones.plan_id', '=', 'planes.id')
            ->whereIn('suscripciones.estado', ['activa', 'trial'])
            ->sum('planes.precio_mensual');

        // Totals by status
        $rawTotales = DB::table('suscripciones')
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $totalesPorEstado = [];
        foreach (['activa', 'trial', 'vencida', 'cancelada'] as $estado) {
            $totalesPorEstado[$estado] = (int) ($rawTotales[$estado] ?? 0);
        }

        // New today / new this month (empresas created)
        $nuevosHoy = DB::table('empresas')
            ->whereDate('created_at', today())
            ->count();

        $nuevosMes = DB::table('empresas')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // Tasa de conversión: activas / (activas + trial) * 100
        $activasTrial = $totalesPorEstado['activa'] + $totalesPorEstado['trial'];
        $tasaConversion = $activasTrial > 0
            ? round(($totalesPorEstado['activa'] / $activasTrial) * 100, 1)
            : 0;

        // Churn: canceladas este mes
        $churn = DB::table('suscripciones')
            ->where('estado', 'cancelada')
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->count();

        // MRR histórico: last 6 months
        $mrrHistorico = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i);
            $mrr = (float) DB::table('suscripciones')
                ->join('planes', 'suscripciones.plan_id', '=', 'planes.id')
                ->whereIn('suscripciones.estado', ['activa', 'trial'])
                ->whereYear('suscripciones.fecha_inicio', '<=', $mes->year)
                ->whereMonth('suscripciones.fecha_inicio', '<=', $mes->month)
                ->sum('planes.precio_mensual');

            $mrrHistorico[] = [
                'mes' => $mes->format('Y-m'),
                'mrr' => $mrr,
            ];
        }

        return ApiResponse::success([
            'mrr_total'          => $mrrTotal,
            'totales_por_estado' => $totalesPorEstado,
            'nuevos_hoy'         => $nuevosHoy,
            'nuevos_mes'         => $nuevosMes,
            'tasa_conversion'    => $tasaConversion,
            'churn'              => $churn,
            'mrr_historico'      => $mrrHistorico,
        ]);
    }
}
