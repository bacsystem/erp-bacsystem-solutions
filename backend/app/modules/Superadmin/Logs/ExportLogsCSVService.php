<?php

namespace App\Modules\Superadmin\Logs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportLogsCSVService
{
    public function execute(Request $request): StreamedResponse
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.empresa_id = ''");
        }

        $query = DB::table('audit_logs')
            ->leftJoin('empresas', 'empresas.id', '=', 'audit_logs.empresa_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'audit_logs.usuario_id')
            ->select(
                'audit_logs.id',
                'empresas.razon_social as empresa',
                'empresas.ruc',
                'usuarios.nombre as usuario',
                'usuarios.email',
                'audit_logs.accion',
                'audit_logs.ip',
                'audit_logs.created_at',
                'audit_logs.datos_anteriores',
                'audit_logs.datos_nuevos',
            )
            ->orderByDesc('audit_logs.created_at');

        if ($empresaId = $request->input('empresa_id')) {
            $query->where('audit_logs.empresa_id', $empresaId);
        }
        if ($accion = $request->input('accion')) {
            $query->where('audit_logs.accion', $accion);
        }
        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('audit_logs.created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('audit_logs.created_at', '<=', $fechaHasta);
        }

        $logs = $query->get();

        return response()->streamDownload(function () use ($logs) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['id', 'empresa', 'ruc', 'usuario', 'email', 'accion', 'ip', 'created_at', 'datos_anteriores', 'datos_nuevos']);
            foreach ($logs as $log) {
                fputcsv($fp, [
                    $log->id,
                    $log->empresa,
                    $log->ruc,
                    $log->usuario,
                    $log->email,
                    $log->accion,
                    $log->ip,
                    $log->created_at,
                    $log->datos_anteriores,
                    $log->datos_nuevos,
                ]);
            }
            fclose($fp);
        }, 'logs.csv', ['Content-Type' => 'text/csv']);
    }
}
