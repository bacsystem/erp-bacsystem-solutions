<?php

namespace App\Console\Commands;

use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Shared\Mail\TrialVencidoMail;
use App\Shared\Mail\TrialVencimientoMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcesarSuscripcionesVencidasCommand extends Command
{
    protected $signature   = 'suscripcion:procesar-vencidas';
    protected $description = 'Procesa trials vencidos, envía avisos y cancela suscripciones sin pago';

    public function handle(): int
    {
        $this->enviarAvisosTrial();
        $this->vencerTrials();
        $this->cancelarVencidas();

        return self::SUCCESS;
    }

    private function enviarAvisosTrial(): void
    {
        $diasAviso = [5, 2];

        foreach ($diasAviso as $dias) {
            $fecha = today()->addDays($dias);

            Suscripcion::with(['empresa.usuarios'])
                ->where('estado', 'trial')
                ->whereDate('fecha_vencimiento', $fecha)
                ->each(function (Suscripcion $suscripcion) use ($dias) {
                    $owner = $suscripcion->empresa->usuarios()
                        ->where('rol', 'owner')
                        ->where('activo', true)
                        ->first();

                    if ($owner) {
                        Mail::queue(new TrialVencimientoMail($owner, $dias));
                    }
                });
        }
    }

    private function vencerTrials(): void
    {
        $suscripciones = Suscripcion::with(['empresa.usuarios'])
            ->where('estado', 'trial')
            ->whereDate('fecha_vencimiento', '<', today())
            ->get();

        foreach ($suscripciones as $suscripcion) {
            $suscripcion->update(['estado' => 'vencida']);

            $owner = $suscripcion->empresa->usuarios()
                ->where('rol', 'owner')
                ->where('activo', true)
                ->first();

            if ($owner) {
                Mail::queue(new TrialVencidoMail($owner));
            }

            Log::info("Trial vencido para empresa {$suscripcion->empresa_id}");
        }
    }

    private function cancelarVencidas(): void
    {
        // Vencidas por más de 7 días → cancelar
        $suscripciones = Suscripcion::where('estado', 'vencida')
            ->whereDate('fecha_vencimiento', '<', today()->subDays(7))
            ->get();

        foreach ($suscripciones as $suscripcion) {
            $suscripcion->update([
                'estado'            => 'cancelada',
                'fecha_cancelacion' => today(),
            ]);

            Log::info("Suscripcion cancelada para empresa {$suscripcion->empresa_id}");
        }
    }
}
