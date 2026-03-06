<?php

namespace App\Modules\Core\Jobs;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Shared\Contracts\PaymentGateway;
use App\Shared\Mail\UpgradePlanFallidoMail;
use App\Shared\Mail\UpgradePlanMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UpgradePlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [0, 120, 600]; // 0s, 2min, 10min

    public function __construct(
        private string $suscripcionId,
        private string $planId,
        private string $culqiToken,
        private string $usuarioId,
        private string $jobId,
    ) {}

    public function handle(PaymentGateway $gateway): void
    {
        $suscripcion = Suscripcion::withoutGlobalScope('empresa')->findOrFail($this->suscripcionId);
        $plan        = Plan::findOrFail($this->planId);
        $usuario     = Usuario::withoutGlobalScope('empresa')->findOrFail($this->usuarioId);

        $monto  = $suscripcion->calcularMontoProrrateo($plan);
        $charge = $gateway->charge([
            'amount'        => (int) ($monto * 100),
            'currency_code' => 'PEN',
            'source_id'     => $this->culqiToken,
            'email'         => $usuario->email,
        ]);

        DB::transaction(function () use ($suscripcion, $plan, $usuario, $charge) {
            $suscripcion->update([
                'plan_id'             => $plan->id,
                'estado'              => 'activa',
                'fecha_vencimiento'   => today()->addMonth(),
                'fecha_proximo_cobro' => today()->addMonth(),
                'downgrade_plan_id'   => null,
                'card_last4'          => $charge['source']['number_last4'] ?? null,
                'card_brand'          => $charge['source']['brand'] ?? null,
            ]);

            $usuario->tokens()->delete();

            AuditLog::create([
                'empresa_id' => $suscripcion->empresa_id,
                'usuario_id' => $this->usuarioId,
                'accion'     => 'plan_upgrade',
                'ip'         => 'background-job',
                'created_at' => now(),
            ]);

            Mail::to($usuario->email)->queue(new UpgradePlanMail($usuario, $plan));
        });
    }

    public function failed(\Throwable $e): void
    {
        $usuario = Usuario::withoutGlobalScope('empresa')->find($this->usuarioId);

        AuditLog::create([
            'empresa_id' => Suscripcion::withoutGlobalScope('empresa')
                ->find($this->suscripcionId)?->empresa_id,
            'usuario_id' => $this->usuarioId,
            'accion'     => 'plan_upgrade_failed',
            'ip'         => 'background-job',
            'created_at' => now(),
        ]);

        if ($usuario) {
            Mail::to($usuario->email)->queue(new UpgradePlanFallidoMail($usuario));
        }
    }
}
