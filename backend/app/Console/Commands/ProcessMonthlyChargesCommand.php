<?php

namespace App\Console\Commands;

use App\Modules\Core\Models\Suscripcion;
use App\Shared\Contracts\PaymentGateway;
use App\Shared\Exceptions\PaymentException;
use App\Shared\Mail\UpgradePlanMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessMonthlyChargesCommand extends Command
{
    protected $signature   = 'suscripcion:cobrar-mensual';
    protected $description = 'Cobra las suscripciones activas con fecha_proximo_cobro = hoy y aplica downgrades pendientes';

    public function handle(PaymentGateway $gateway): int
    {
        $suscripciones = Suscripcion::with(['plan', 'downgradePlan', 'empresa.usuarios'])
            ->whereIn('estado', ['activa'])
            ->whereDate('fecha_proximo_cobro', today())
            ->get();

        foreach ($suscripciones as $suscripcion) {
            try {
                $this->procesarSuscripcion($suscripcion, $gateway);
            } catch (\Throwable $e) {
                Log::error("Error procesando suscripcion {$suscripcion->id}: " . $e->getMessage());
            }
        }

        $this->info("Procesadas {$suscripciones->count()} suscripciones.");
        return self::SUCCESS;
    }

    private function procesarSuscripcion(Suscripcion $suscripcion, PaymentGateway $gateway): void
    {
        // Aplicar downgrade si hay uno pendiente
        if ($suscripcion->downgrade_plan_id) {
            $suscripcion->update([
                'plan_id'           => $suscripcion->downgrade_plan_id,
                'downgrade_plan_id' => null,
            ]);
        }

        // Si no tiene tarjeta registrada, marcar como vencida
        if (! $suscripcion->culqi_card_id) {
            $suscripcion->update([
                'estado'            => 'vencida',
                'fecha_vencimiento' => today(),
            ]);
            return;
        }

        // Cobrar via Culqi con la tarjeta guardada
        try {
            $monto = (int) round($suscripcion->plan->precio_mensual * 100);
            $owner = $suscripcion->empresa->usuarios()->where('rol', 'owner')->first();

            $gateway->charge([
                'amount'        => $monto,
                'currency_code' => 'PEN',
                'email'         => $owner?->email,
                'source_id'     => $suscripcion->culqi_card_id,
            ]);

            // Cobro exitoso — renovar período
            $suscripcion->update([
                'estado'              => 'activa',
                'fecha_vencimiento'   => today()->addMonth(),
                'fecha_proximo_cobro' => today()->addMonth(),
            ]);

            if ($owner) {
                Mail::queue(new UpgradePlanMail($owner, $suscripcion->plan));
            }
        } catch (PaymentException $e) {
            Log::warning("Cobro fallido para suscripcion {$suscripcion->id}: " . $e->getMessage());
        }
    }
}

