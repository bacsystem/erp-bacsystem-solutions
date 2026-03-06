<?php

namespace App\Modules\Superadmin\Logs;

use Illuminate\Support\Facades\DB;

class ResumenLogsService
{
    public function execute(): array
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $loginsFallidosHoy = DB::table('audit_logs')
            ->where('accion', 'login_failed')
            ->whereDate('created_at', today())
            ->count();

        $upgradesMes = DB::table('audit_logs')
            ->where('accion', 'suscripcion_upgrade')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $downgradesMes = DB::table('audit_logs')
            ->where('accion', 'suscripcion_downgrade')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $suspensionesActivas = DB::table('suscripciones')
            ->where('estado', 'cancelada')
            ->count();

        $topEmpresas = DB::table('audit_logs')
            ->join('empresas', 'empresas.id', '=', 'audit_logs.empresa_id')
            ->select('empresas.id', 'empresas.razon_social', DB::raw('count(*) as total_actividad'))
            ->whereNotNull('audit_logs.empresa_id')
            ->groupBy('empresas.id', 'empresas.razon_social')
            ->orderByDesc('total_actividad')
            ->limit(5)
            ->get();

        return [
            'logins_fallidos_hoy'  => $loginsFallidosHoy,
            'upgrades_mes'         => $upgradesMes,
            'downgrades_mes'       => $downgradesMes,
            'suspensiones_activas' => $suspensionesActivas,
            'top_empresas'         => $topEmpresas,
        ];
    }
}
